<?php

namespace Database\Seeders;

use App\Models\ChartOfAccount;
use Illuminate\Database\Seeder;

class ChartOfAccountsSeeder extends Seeder
{
    public function run(): void
    {
        if (ChartOfAccount::count() > 0) {
            $this->command->warn('Chart of accounts already seeded. Skipping.');
            return;
        }

        $accounts = [
            // Assets (1000-1999)
            ['code' => '1000', 'name' => 'Current Assets', 'type' => 'asset'],
            ['code' => '1100', 'name' => 'Cash & Bank', 'type' => 'asset', 'parent' => '1000'],
            ['code' => '1200', 'name' => 'Accounts Receivable', 'type' => 'asset', 'parent' => '1000'],
            ['code' => '1300', 'name' => 'Fuel Inventory', 'type' => 'asset', 'parent' => '1000'],
            ['code' => '1400', 'name' => 'Spare Parts Inventory', 'type' => 'asset', 'parent' => '1000'],
            ['code' => '1500', 'name' => 'Prepaid Expenses', 'type' => 'asset', 'parent' => '1000'],
            ['code' => '1600', 'name' => 'Fixed Assets', 'type' => 'asset'],
            ['code' => '1610', 'name' => 'Vehicles & Equipment', 'type' => 'asset', 'parent' => '1600'],
            ['code' => '1620', 'name' => 'Buildings & Land', 'type' => 'asset', 'parent' => '1600'],
            ['code' => '1630', 'name' => 'Accumulated Depreciation', 'type' => 'asset', 'parent' => '1600'],

            // Liabilities (2000-2999)
            ['code' => '2000', 'name' => 'Current Liabilities', 'type' => 'liability'],
            ['code' => '2100', 'name' => 'Accounts Payable', 'type' => 'liability', 'parent' => '2000'],
            ['code' => '2200', 'name' => 'Accrued Expenses', 'type' => 'liability', 'parent' => '2000'],
            ['code' => '2300', 'name' => 'Tax Payable', 'type' => 'liability', 'parent' => '2000'],
            ['code' => '2400', 'name' => 'Driver Deposits', 'type' => 'liability', 'parent' => '2000'],
            ['code' => '2500', 'name' => 'Long-Term Liabilities', 'type' => 'liability'],
            ['code' => '2510', 'name' => 'Bank Loans', 'type' => 'liability', 'parent' => '2500'],

            // Equity (3000-3999)
            ['code' => '3000', 'name' => 'Equity', 'type' => 'equity'],
            ['code' => '3100', 'name' => 'Share Capital', 'type' => 'equity', 'parent' => '3000'],
            ['code' => '3200', 'name' => 'Retained Earnings', 'type' => 'equity', 'parent' => '3000'],
            ['code' => '3300', 'name' => 'Current Year Earnings', 'type' => 'equity', 'parent' => '3000'],

            // Revenue (4000-4999)
            ['code' => '4000', 'name' => 'Operating Revenue', 'type' => 'revenue'],
            ['code' => '4100', 'name' => 'Freight Revenue', 'type' => 'revenue', 'parent' => '4000'],
            ['code' => '4200' , 'name' => 'Demurrage Revenue', 'type' => 'revenue', 'parent' => '4000'],
            ['code' => '4300', 'name' => 'Other Operating Revenue', 'type' => 'revenue', 'parent' => '4000'],

            // Expenses (5000-5999)
            ['code' => '5000', 'name' => 'Operating Expenses', 'type' => 'expense'],
            ['code' => '5100', 'name' => 'Fuel Expense', 'type' => 'expense', 'parent' => '5000'],
            ['code' => '5200', 'name' => 'Driver Wages & Allowances', 'type' => 'expense', 'parent' => '5000'],
            ['code' => '5300', 'name' => 'Maintenance & Repairs', 'type' => 'expense', 'parent' => '5000'],
            ['code' => '5400', 'name' => 'Spare Parts Expense', 'type' => 'expense', 'parent' => '5000'],
            ['code' => '5500', 'name' => 'Insurance Expense', 'type' => 'expense', 'parent' => '5000'],
            ['code' => '5600', 'name' => 'Administrative Expense', 'type' => 'expense'],
            ['code' => '5610', 'name' => 'Salaries & Benefits', 'type' => 'expense', 'parent' => '5600'],
            ['code' => '5620', 'name' => 'Office Rent & Utilities', 'type' => 'expense', 'parent' => '5600'],
            ['code' => '5630', 'name' => 'Depreciation Expense', 'type' => 'expense', 'parent' => '5600'],
            ['code' => '5640', 'name' => 'Bank Charges', 'type' => 'expense', 'parent' => '5600'],
            ['code' => '5700', 'name' => 'Other Expenses', 'type' => 'expense'],
        ];

        foreach ($accounts as $acc) {
            $parentId = null;
            if (isset($acc['parent'])) {
                $parent = ChartOfAccount::where('code', $acc['parent'])->first();
                $parentId = $parent?->id;
            }
            ChartOfAccount::create([
                'code' => $acc['code'],
                'name' => $acc['name'],
                'type' => $acc['type'],
                'parent_id' => $parentId,
                'is_active' => true,
            ]);
        }

        $this->command->info('Seeded standard chart of accounts (' . count($accounts) . ' accounts).');
    }
}
