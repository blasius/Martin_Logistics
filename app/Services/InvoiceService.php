<?php

namespace App\Services;

use App\Models\Currency;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Order;
use App\Models\Trip;
use App\Services\RateCalculatorService;
use App\Models\RateCalculatorRequest;
use Illuminate\Support\Facades\DB;

class InvoiceService
{
    public function __construct(protected RateCalculatorService $rateService) {}

    public function generateReference(string $type = 'invoice'): string
    {
        $year = now()->year;
        $prefix = match ($type) {
            'credit_note' => 'CN-',
            'debit_note' => 'DN-',
            default => 'INV-',
        };
        $count = Invoice::whereYear('created_at', $year)->count() + 1;
        return $prefix . $year . '-' . str_pad($count, 5, '0', STR_PAD_LEFT);
    }

    public function generateFromOrder(Order $order): Invoice
    {
        return DB::transaction(function () use ($order) {
            $trip = $order->trips()->latest()->first();
            $contract = $order->contract_id
                ? \App\Models\Contract::find($order->contract_id)
                : \App\Models\Contract::where('client_id', $order->client_id)
                    ->where('status', 'active')
                    ->first();

            $currencyId = $order->currency_id ?? Currency::where('is_default', true)->value('id') ?? Currency::value('id');
            $items = [];
            $subtotal = 0;

            if ($trip && $contract?->rate_card_id) {
                $calcRequest = new RateCalculatorRequest(
                    distanceKm: $trip->planned_distance_km,
                    weightKg: $order->weight_kg,
                    rateCardId: $contract->rate_card_id,
                );

                $calcResult = $this->rateService->calculate($calcRequest);

                foreach ($calcResult['line_items'] as $line) {
                    $items[] = [
                        'description' => $line['charge_name'],
                        'charge_type' => $line['charge_type'],
                        'quantity' => 1,
                        'unit_price' => $line['calculated'],
                        'total' => $line['calculated'],
                    ];
                    $subtotal += $line['calculated'];
                }
                $currencyId = $calcResult['currency_id'] ?? $currencyId;
            }

            if (empty($items)) {
                $items[] = [
                    'description' => 'Freight charge — ' . ($order->reference ?? 'Order'),
                    'charge_type' => 'flat',
                    'quantity' => 1,
                    'unit_price' => $order->price ?? 0,
                    'total' => $order->price ?? 0,
                ];
                $subtotal = $order->price ?? 0;
            }

            $taxTotal = round($subtotal * 0.18, 2);
            $total = round($subtotal + $taxTotal, 2);

            $invoice = Invoice::create([
                'reference' => $this->generateReference(),
                'client_id' => $order->client_id,
                'contract_id' => $contract?->id,
                'order_id' => $order->id,
                'type' => 'invoice',
                'issue_date' => now(),
                'due_date' => now()->addDays(30),
                'status' => 'draft',
                'subtotal' => $subtotal,
                'tax_total' => $taxTotal,
                'discount_total' => 0,
                'total' => $total,
                'currency_id' => $currencyId,
                'created_by' => auth()->id(),
            ]);

            foreach ($items as $item) {
                $invoice->items()->create($item);
            }

            return $invoice;
        });
    }

    public function recalculateTotals(Invoice $invoice): Invoice
    {
        $subtotal = $invoice->items()->sum('total');
        $total = round($subtotal + $invoice->tax_total - $invoice->discount_total, 2);

        $invoice->update([
            'subtotal' => $subtotal,
            'total' => $total,
        ]);

        return $invoice->fresh();
    }
}
