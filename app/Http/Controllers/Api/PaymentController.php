<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\PaymentService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(protected PaymentService $paymentService) {}

    public function index(Request $request)
    {
        $query = Payment::with([
            'invoice:id,reference,client_id,total,status',
            'invoice.client:id,name',
            'requisition',
            'cashier:id,name',
        ]);

        if ($request->filled('invoice_id')) {
            $query->where('invoice_id', $request->invoice_id);
        }

        if ($request->filled('client_id')) {
            $query->whereHas('invoice', fn($q) => $q->where('client_id', $request->client_id));
        }

        if ($request->filled('method')) {
            $query->where('method', $request->method);
        }

        if ($request->filled('date_from')) {
            $query->where('paid_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('paid_at', '<=', $request->date_to . ' 23:59:59');
        }

        return $query->orderBy('created_at', 'desc')->paginate($request->per_page ?? 50);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'invoice_id' => 'required|exists:invoices,id',
            'amount' => 'required|numeric|min:0.01',
            'method' => 'nullable|string|max:50',
            'tx_reference' => 'nullable|string|max:255',
            'paid_at' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        $invoice = Invoice::findOrFail($validated['invoice_id']);

        if (!in_array($invoice->status, ['sent', 'overdue'])) {
            return response()->json(['message' => 'Can only record payments against sent or overdue invoices'], 422);
        }

        $payment = $this->paymentService->recordPayment($invoice, $validated);

        return $payment->load(['invoice:id,reference,total,status', 'cashier:id,name']);
    }

    public function show(Payment $payment)
    {
        return $payment->load([
            'invoice:id,reference,client_id,total,status,currency_id',
            'invoice.client:id,name',
            'invoice.currency:id,code,symbol',
            'requisition',
            'cashier:id,name',
        ]);
    }

    public function update(Request $request, Payment $payment)
    {
        $validated = $request->validate([
            'amount' => 'sometimes|numeric|min:0.01',
            'method' => 'nullable|string|max:50',
            'tx_reference' => 'nullable|string|max:255',
            'paid_at' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        $payment->update($validated);

        return $payment->load(['invoice:id,reference,total,status', 'cashier:id,name']);
    }

    public function destroy(Payment $payment)
    {
        $payment->delete();
        return response()->json(['message' => 'Payment deleted']);
    }

    public function aging()
    {
        return response()->json($this->paymentService->getAgingReport());
    }

    public function clientStatement(int $clientId)
    {
        $statement = $this->paymentService->getClientStatement($clientId);
        return response()->json($statement);
    }

    public function downloadClientStatement(int $clientId)
    {
        $statement = $this->paymentService->getClientStatement($clientId);

        $pdf = Pdf::loadView('pdf.client-statement', [
            'statement' => $statement,
        ]);

        return $pdf->download('statement-' . ($statement['client']?->name ?? $clientId) . '.pdf');
    }
}
