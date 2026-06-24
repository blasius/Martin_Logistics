<?php

namespace App\Services;

use App\Models\Approval;
use App\Models\Currency;
use App\Models\Expense;
use App\Models\ExpenseType;
use App\Models\SupportTicket;
use App\Models\User;
use DB;
use Illuminate\Http\UploadedFile;

class ExpenseManagementService
{
    public static function generateReference(): string
    {
        $year = now()->year;
        $latest = Expense::whereYear('created_at', $year)
            ->lockForUpdate()
            ->orderByDesc('id')
            ->first();

        $next = $latest
            ? ((int) substr($latest->reference, -6)) + 1
            : 1;

        return sprintf('EXP-%d-%06d', $year, $next);
    }

    public function createExpenseType(array $data, int $userId): ExpenseType
    {
        return DB::transaction(function () use ($data, $userId) {
            $data['created_by'] = $userId;
            $data['status'] = 'draft';
            $data['is_active'] = false;
            return ExpenseType::create($data);
        });
    }

    public function submitExpenseType(int $id, int $userId): ExpenseType
    {
        return DB::transaction(function () use ($id, $userId) {
            $type = ExpenseType::findOrFail($id);
            abort_if($type->status !== 'draft', 422, 'Only draft types can be submitted.');
            $type->update(['status' => 'pending_approval']);
            return $type->fresh();
        });
    }

    public function approveExpenseType(int $id, int $approverId): ExpenseType
    {
        return DB::transaction(function () use ($id, $approverId) {
            $type = ExpenseType::findOrFail($id);
            abort_if($type->status !== 'pending_approval', 422, 'Type is not pending approval.');
            $type->update([
                'status' => 'active',
                'approved_by' => $approverId,
                'approved_at' => now(),
                'is_active' => true,
            ]);
            return $type->fresh();
        });
    }

    public function rejectExpenseType(int $id, int $rejectedBy): ExpenseType
    {
        return DB::transaction(function () use ($id, $rejectedBy) {
            $type = ExpenseType::findOrFail($id);
            abort_if($type->status !== 'pending_approval', 422, 'Type is not pending approval.');
            $type->update(['status' => 'rejected']);
            return $type->fresh();
        });
    }

    public function createExpense(array $data, int $userId, ?UploadedFile $proof = null): Expense
    {
        return DB::transaction(function () use ($data, $userId, $proof) {
            $data['reference'] = self::generateReference();
            $data['created_by'] = $userId;

            if ($proof) {
                $data['proof_of_payment_path'] = $proof->store('expenses/proofs', 'public');
            }

            if (empty($data['expense_class'])) {
                $data['expense_class'] = 'variable';
            }

            if (empty($data['status'])) {
                $data['status'] = $data['expense_class'] === 'fixed' ? 'approved' : 'pending';
            }

            if (empty($data['currency_id'])) {
                $data['currency_id'] = Currency::where('is_default', true)->value('id');
            }

            $expense = Expense::create($data);

            if ($data['status'] === 'pending') {
                $this->createApprovalChain($expense, $userId);
            }

            return $expense->fresh()->load([
                'expenseType', 'vehicle:id,plate_number,make,model',
                'driver:id,name', 'trip:id,reference', 'currency:id,code',
                'creator:id,name', 'approvals.approver:id,name',
            ]);
        });
    }

    public function convertFromTicket(int $ticketId, array $data, int $userId): Expense
    {
        return DB::transaction(function () use ($ticketId, $data, $userId) {
            $ticket = SupportTicket::with('subject')->findOrFail($ticketId);
            abort_if($ticket->status === 'resolved' || $ticket->status === 'closed',
                422, 'Cannot convert a resolved or closed ticket.');

            $expenseData = [
                'support_ticket_id' => $ticket->id,
                'expense_type_id' => $data['expense_type_id'] ?? null,
                'vehicle_id' => $data['vehicle_id'] ?? $this->resolveSubjectId($ticket->subject_type, $ticket->subject_id, 'vehicle'),
                'driver_id' => $data['driver_id'] ?? $ticket->user_id,
                'trip_id' => $data['trip_id'] ?? $this->resolveSubjectId($ticket->subject_type, $ticket->subject_id, 'trip'),
                'route_id' => $data['route_id'] ?? $this->resolveSubjectId($ticket->subject_type, $ticket->subject_id, 'route'),
                'name' => $data['name'],
                'description' => $data['description'] ?? $ticket->description,
                'category' => $data['category'] ?? null,
                'amount' => $data['amount'],
                'currency_id' => $data['currency_id'] ?? Currency::where('is_default', true)->value('id'),
                'location' => $data['location'] ?? null,
                'odometer' => $data['odometer'] ?? null,
                'expense_class' => $data['expense_class'] ?? 'variable',
                'status' => 'pending',
            ];

            $expense = $this->createExpense($expenseData, $userId);

            $ticket->update(['status' => 'waiting']);

            return $expense;
        });
    }

    public function approve(Expense $expense, int $approverId, ?string $comment = null): Expense
    {
        return DB::transaction(function () use ($expense, $approverId, $comment) {
            abort_if($expense->status !== 'pending', 422, 'Expense is not pending approval.');
            abort_if($expense->expense_class !== 'variable', 422, 'Only variable expenses need approval.');

            $approver = User::findOrFail($approverId);
            $role = $approver->getRoleNames()->first();

            $pendingApprovals = $expense->approvals()->where('status', 'pending')->count();
            $nextStage = $expense->approvals()->max('stage') ?? 0;

            Approval::create([
                'approvable_type' => Expense::class,
                'approvable_id' => $expense->id,
                'approver_id' => $approverId,
                'approver_role' => $role,
                'stage' => $nextStage + 1,
                'status' => 'approved',
                'comment' => $comment,
                'decided_at' => now(),
            ]);

            $levelsRemaining = $this->getRemainingApprovalLevels($expense, $approverId);

            if (empty($levelsRemaining)) {
                $expense->update(['status' => 'approved']);
            }

            return $expense->fresh()->load('approvals.approver:id,name');
        });
    }

    public function reject(Expense $expense, int $rejectedBy, string $reason): Expense
    {
        return DB::transaction(function () use ($expense, $rejectedBy, $reason) {
            abort_if($expense->status !== 'pending', 422, 'Expense is not pending.');

            $rejector = User::findOrFail($rejectedBy);
            $role = $rejector->getRoleNames()->first();

            $nextStage = $expense->approvals()->max('stage') ?? 0;

            Approval::create([
                'approvable_type' => Expense::class,
                'approvable_id' => $expense->id,
                'approver_id' => $rejectedBy,
                'approver_role' => $role,
                'stage' => $nextStage + 1,
                'status' => 'rejected',
                'comment' => $reason,
                'decided_at' => now(),
            ]);

            $expense->update([
                'status' => 'rejected',
                'rejection_reason' => $reason,
            ]);

            return $expense->fresh()->load('approvals.approver:id,name');
        });
    }

    public function recordPayment(Expense $expense, array $data, ?UploadedFile $proof = null): Expense
    {
        return DB::transaction(function () use ($expense, $data, $proof) {
            abort_if($expense->status !== 'approved', 422, 'Only approved expenses can be paid.');

            $update = [
                'status' => 'paid',
                'paid_at' => $data['paid_at'] ?? now(),
                'payment_method' => $data['payment_method'] ?? null,
                'payment_reference' => $data['payment_reference'] ?? null,
            ];

            if ($proof) {
                $update['proof_of_payment_path'] = $proof->store('expenses/proofs', 'public');
            }

            $expense->update($update);

            if ($expense->support_ticket_id) {
                $ticket = SupportTicket::find($expense->support_ticket_id);
                if ($ticket && $ticket->isOpen()) {
                    $ticket->update([
                        'status' => SupportTicket::STATUS_RESOLVED,
                        'resolved_at' => now(),
                    ]);
                }
            }

            return $expense->fresh()->load('supportTicket');
        });
    }

    public function dashboardStats(): array
    {
        $pendingCount = Expense::where('status', 'pending')->count();
        $approvedPendingPayment = Expense::where('status', 'approved')->count();
        $paidThisMonth = Expense::where('status', 'paid')
            ->whereMonth('paid_at', now()->month)
            ->whereYear('paid_at', now()->year)
            ->sum('amount');
        $totalExpenses = Expense::sum('amount');
        $fixedCount = Expense::where('expense_class', 'fixed')->count();
        $variableCount = Expense::where('expense_class', 'variable')->count();

        $byCategory = Expense::select('category')
            ->selectRaw('COUNT(*) as count')
            ->selectRaw('SUM(amount) as total')
            ->groupBy('category')
            ->get();

        $pendingPaymentAging = Expense::where('status', 'approved')
            ->selectRaw('AVG(DATEDIFF(NOW(), updated_at)) as avg_days')
            ->value('avg_days');

        return [
            'pending_count' => $pendingCount,
            'approved_pending_payment' => $approvedPendingPayment,
            'paid_this_month' => (float) $paidThisMonth,
            'total_expenses' => (float) $totalExpenses,
            'fixed_count' => $fixedCount,
            'variable_count' => $variableCount,
            'by_category' => $byCategory,
            'pending_payment_avg_days' => round((float) $pendingPaymentAging, 1),
        ];
    }

    protected function createApprovalChain(Expense $expense, int $createdBy): void
    {
        $creator = User::find($createdBy);
        $roles = $creator ? $creator->getRoleNames() : collect();

        $levels = $this->determineApprovalLevels($roles);

        foreach ($levels as $i => $level) {
            Approval::create([
                'approvable_type' => Expense::class,
                'approvable_id' => $expense->id,
                'approver_id' => null,
                'approver_role' => $level['role'],
                'stage' => $i + 1,
                'status' => 'pending',
            ]);
        }
    }

    protected function determineApprovalLevels($creatorRoles): array
    {
        if ($creatorRoles->contains('Managing Director')) {
            return [];
        }

        if ($creatorRoles->contains('Director of Operations')) {
            return [
                ['role' => 'Managing Director'],
            ];
        }

        if ($creatorRoles->contains('Logistics Manager')) {
            return [
                ['role' => 'Director of Operations'],
                ['role' => 'Managing Director'],
            ];
        }

        return [
            ['role' => 'Logistics Manager'],
            ['role' => 'Director of Operations'],
        ];
    }

    protected function getRemainingApprovalLevels(Expense $expense, int $approvedBy): array
    {
        $approvedRoles = $expense->approvals()
            ->where('status', 'approved')
            ->pluck('approver_role')
            ->toArray();

        $creator = User::find($expense->created_by);
        $creatorRoles = $creator ? $creator->getRoleNames() : collect();

        $allLevels = $this->determineApprovalLevels($creatorRoles);

        return array_values(array_filter($allLevels, function ($level) use ($approvedRoles) {
            return !in_array($level['role'], $approvedRoles);
        }));
    }

    private function resolveSubjectId(?string $subjectType, ?int $subjectId, string $target): ?int
    {
        if (!$subjectType || !$subjectId) {
            return null;
        }

        $model = app($subjectType);
        if (!$model) {
            return null;
        }

        $record = $model::find($subjectId);
        if (!$record) {
            return null;
        }

        if ($target === 'vehicle' && isset($record->vehicle_id)) {
            return $record->vehicle_id;
        }

        if ($target === 'trip' && $record instanceof \App\Models\Trip) {
            return $record->id;
        }

        if ($target === 'route' && isset($record->route_id)) {
            return $record->route_id;
        }

        return null;
    }
}
