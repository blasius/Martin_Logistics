<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Order;
use App\Services\InvoiceService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function __construct(protected InvoiceService $invoiceService) {}

    public function index(Request $request)
    {
        $query = Invoice::with(['client:id,name', 'currency:id,code', 'order:id,reference']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        return $query->orderBy('created_at', 'desc')->paginate($request->per_page ?? 50);
    }

    public function show(Invoice $invoice)
    {
        return $invoice->load([
            'client:id,name,address,phone,email',
            'contract:id,reference',
            'order:id,reference',
            'currency:id,code,symbol',
            'items',
            'payments',
            'createdBy:id,name',
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'contract_id' => 'nullable|exists:contracts,id',
            'order_id' => 'nullable|exists:orders,id',
            'type' => 'sometimes|in:invoice,credit_note,debit_note',
            'issue_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:issue_date',
            'currency_id' => 'required|exists:currencies,id',
            'tax_total' => 'nullable|numeric|min:0',
            'discount_total' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string|max:255',
            'items.*.charge_type' => 'nullable|string|max:50',
            'items.*.quantity' => 'required|numeric|min:0',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        $invoice = \DB::transaction(function () use ($validated) {
            $totalItems = 0;
            foreach ($validated['items'] as &$item) {
                $item['total'] = round($item['quantity'] * $item['unit_price'], 2);
                $totalItems += $item['total'];
            }

            $taxTotal = $validated['tax_total'] ?? 0;
            $discountTotal = $validated['discount_total'] ?? 0;
            $total = round($totalItems + $taxTotal - $discountTotal, 2);

            $invoice = Invoice::create([
                'reference' => $this->invoiceService->generateReference($validated['type'] ?? 'invoice'),
                'client_id' => $validated['client_id'],
                'contract_id' => $validated['contract_id'] ?? null,
                'order_id' => $validated['order_id'] ?? null,
                'type' => $validated['type'] ?? 'invoice',
                'issue_date' => $validated['issue_date'],
                'due_date' => $validated['due_date'],
                'status' => 'draft',
                'subtotal' => $totalItems,
                'tax_total' => $taxTotal,
                'discount_total' => $discountTotal,
                'total' => $total,
                'currency_id' => $validated['currency_id'],
                'notes' => $validated['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            foreach ($validated['items'] as $item) {
                $invoice->items()->create($item);
            }

            return $invoice;
        });

        return $invoice->load(['client:id,name', 'currency:id,code,symbol', 'items']);
    }

    public function update(Request $request, Invoice $invoice)
    {
        if (!in_array($invoice->status, ['draft'])) {
            return response()->json(['message' => 'Only draft invoices can be updated'], 422);
        }

        $validated = $request->validate([
            'issue_date' => 'sometimes|date',
            'due_date' => 'sometimes|date|after_or_equal:issue_date',
            'currency_id' => 'sometimes|exists:currencies,id',
            'tax_total' => 'nullable|numeric|min:0',
            'discount_total' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'status' => 'sometimes|in:draft,sent,paid,overdue,cancelled',
            'items' => 'sometimes|array|min:1',
            'items.*.id' => 'nullable|exists:invoice_items,id',
            'items.*.description' => 'required_with:items|string|max:255',
            'items.*.charge_type' => 'nullable|string|max:50',
            'items.*.quantity' => 'required_with:items|numeric|min:0',
            'items.*.unit_price' => 'required_with:items|numeric|min:0',
        ]);

        \DB::transaction(function () use ($validated, $invoice) {
            $invoice->update($validated);

            if (isset($validated['items'])) {
                $incomingIds = collect($validated['items'])->pluck('id')->filter();
                $invoice->items()->whereNotIn('id', $incomingIds)->delete();

                $totalItems = 0;
                foreach ($validated['items'] as $item) {
                    $itemData = [
                        'description' => $item['description'],
                        'charge_type' => $item['charge_type'] ?? null,
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['unit_price'],
                        'total' => round($item['quantity'] * $item['unit_price'], 2),
                    ];
                    $totalItems += $itemData['total'];

                    if (isset($item['id'])) {
                        $invoice->items()->where('id', $item['id'])->update($itemData);
                    } else {
                        $invoice->items()->create($itemData);
                    }
                }

                $this->invoiceService->recalculateTotals($invoice);
            }
        });

        return $invoice->fresh()->load(['client:id,name', 'currency:id,code,symbol', 'items']);
    }

    public function destroy(Invoice $invoice)
    {
        if (!in_array($invoice->status, ['draft', 'cancelled'])) {
            return response()->json(['message' => 'Only draft or cancelled invoices can be deleted'], 422);
        }
        $invoice->delete();
        return response()->json(['message' => 'Invoice deleted']);
    }

    public function generateFromOrder(Order $order)
    {
        try {
            $invoice = $this->invoiceService->generateFromOrder($order);
            return $invoice->load(['client:id,name', 'currency:id,code,symbol', 'items']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to generate invoice: ' . $e->getMessage()], 422);
        }
    }

    public function markSent(Invoice $invoice)
    {
        $invoice->update(['status' => 'sent']);
        return response()->json($invoice);
    }

    public function markPaid(Invoice $invoice)
    {
        $invoice->update(['status' => 'paid']);
        return response()->json($invoice);
    }

    public function markOverdue(Invoice $invoice)
    {
        $invoice->update(['status' => 'overdue']);
        return response()->json($invoice);
    }

    public function markCancelled(Invoice $invoice)
    {
        $invoice->update(['status' => 'cancelled']);
        return response()->json($invoice);
    }

    public function downloadPdf(Invoice $invoice)
    {
        $invoice->load([
            'client',
            'currency',
            'items',
            'order',
            'contract',
        ]);

        $pdf = Pdf::loadView('pdf.invoice', compact('invoice'));
        return $pdf->download($invoice->reference . '.pdf');
    }
}
