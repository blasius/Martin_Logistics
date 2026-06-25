<?php

namespace App\Http\Controllers\Api\Accounting;

use App\Http\Controllers\Controller;
use App\Services\AccountingService;
use Illuminate\Http\Request;

class FinancialReportController extends Controller
{
    public function __construct(protected AccountingService $accountingService) {}

    public function trialBalance(Request $request)
    {
        $validated = $request->validate([
            'fiscal_year_id' => 'nullable|exists:fiscal_years,id',
            'end_date' => 'nullable|date',
        ]);

        return response()->json(
            $this->accountingService->trialBalance(
                $validated['fiscal_year_id'] ?? null,
                $validated['end_date'] ?? null
            )
        );
    }

    public function profitLoss(Request $request)
    {
        $validated = $request->validate([
            'fiscal_year_id' => 'nullable|exists:fiscal_years,id',
            'end_date' => 'nullable|date',
        ]);

        return response()->json(
            $this->accountingService->profitLoss(
                $validated['fiscal_year_id'] ?? null,
                $validated['end_date'] ?? null
            )
        );
    }

    public function balanceSheet(Request $request)
    {
        $validated = $request->validate([
            'fiscal_year_id' => 'nullable|exists:fiscal_years,id',
            'end_date' => 'nullable|date',
        ]);

        return response()->json(
            $this->accountingService->balanceSheet(
                $validated['fiscal_year_id'] ?? null,
                $validated['end_date'] ?? null
            )
        );
    }
}
