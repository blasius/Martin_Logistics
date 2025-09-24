<?php

namespace App\Services;

use App\Models\Requisition;
use App\Models\Payment;
use App\Models\RequisitionSignature;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class RequisitionService
{
    public function financeApprove(Requisition $req, $financeUser)
    {
        if ($req->status !== Requisition::STATUS_PENDING_FINANCE) {
            throw new \Exception('Requisition not in pending finance state');
        }

        DB::transaction(function () use ($req, $financeUser) {
            $req->update([
                'assigned_finance_user_id' => $financeUser->id,
                'status' => Requisition::STATUS_PENDING_MANAGEMENT,
            ]);
            // TODO: notify management
        });

        return $req->fresh();
    }

    public function managerApprove(Requisition $req, $managerUser)
    {
        if ($req->status !== Requisition::STATUS_PENDING_MANAGEMENT) {
            throw new \Exception('Requisition not in pending management state');
        }

        DB::transaction(function () use ($req, $managerUser) {
            $voucher = $this->generateVoucherNumber();

            $req->update([
                'assigned_manager_user_id' => $managerUser->id,
                'status' => Requisition::STATUS_APPROVED,
                'voucher_number' => $voucher,
            ]);

            // optionally set to pending_payment and notify cashier
        });

        return $req->fresh();
    }

    public function cashierProcess(Requisition $req, $cashierUser, array $paymentData, ?array $signatureData = null)
    {
        if ($req->status !== Requisition::STATUS_APPROVED) {
            throw new \Exception('Requisition not approved for payment');
        }

        return DB::transaction(function () use ($req, $cashierUser, $paymentData, $signatureData) {

            $payment = Payment::create([
                'requisition_id' => $req->id,
                'paid_by_user_id' => $cashierUser->id,
                'amount' => $paymentData['amount'] ?? $req->amount,
                'currency_id' => $paymentData['currency_id'] ?? $req->currency_id,
                'method' => $paymentData['method'] ?? 'cash',
                'tx_reference' => $paymentData['tx_reference'] ?? null,
                'paid_at' => $paymentData['paid_at'] ?? now(),
                'notes' => $paymentData['notes'] ?? null,
            ]);

            // Mark requisition paid and set cashier
            $req->update([
                'assigned_cashier_user_id' => $cashierUser->id,
                'status' => Requisition::STATUS_PAID,
            ]);

            // store signatures if provided
            if (!empty($signatureData)) {
                foreach ($signatureData as $sig) {
                    RequisitionSignature::create([
                        'requisition_id' => $req->id,
                        'signed_by' => $sig['signed_by'] ?? $cashierUser->id,
                        'role' => $sig['role'] ?? 'cashier',
                        'signature_type' => $sig['signature_type'] ?? 'image',
                        'signature_data' => $sig['signature_data'] ?? '',
                        'signed_at' => $sig['signed_at'] ?? now(),
                    ]);
                }
            }

            // TODO: create audit log, notify requester

            return $payment;
        });
    }

    protected function generateVoucherNumber(): string
    {
        return 'VCH-' . now()->format('Ymd') . '-' . strtoupper(Str::substr(Str::uuid()->toString(), 0, 6));
    }
}
