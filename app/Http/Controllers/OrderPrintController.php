<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;

class OrderPrintController extends Controller
{
    public function __invoke(Order $order)
    {
        $pdf = Pdf::loadView('reports.order', compact('order'));

        return $pdf->download("order-{$order->reference}.pdf");
    }
}
