<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\ExpenseType;
use App\Models\Requisition;
use App\Services\RequisitionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RequisitionWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_requisition_workflow()
    {
        // create users
        $requester = User::factory()->create();
        $finance = User::factory()->create();
        $manager = User::factory()->create();
        $cashier = User::factory()->create();

        // create an expense type
        $etype = ExpenseType::create(['name' => 'Fuel', 'description' => 'Fuel expenses']);

        // create a requisition (simulating ops create)
        $req = Requisition::create([
            'requester_id' => $requester->id,
            'expense_type_id' => $etype->id,
            'amount' => 1350.00,
            'description' => 'Fuel for trip #1245',
            'status' => Requisition::STATUS_PENDING_FINANCE,
        ]);

        $this->assertDatabaseHas('requisitions', [
            'id' => $req->id,
            'status' => Requisition::STATUS_PENDING_FINANCE,
        ]);

        $svc = $this->app->make(RequisitionService::class);

        // Finance approves -> pending management
        $req = $svc->financeApprove($req, $finance);
        $this->assertEquals(Requisition::STATUS_PENDING_MANAGEMENT, $req->status);
        $this->assertEquals($finance->id, $req->assigned_finance_user_id);

        // Manager approves -> approved + voucher number
        $req = $svc->managerApprove($req, $manager);
        $this->assertEquals(Requisition::STATUS_APPROVED, $req->status);
        $this->assertEquals($manager->id, $req->assigned_manager_user_id);
        $this->assertNotNull($req->voucher_number);
        $this->assertStringStartsWith('VCH-', $req->voucher_number);

        // Cashier processes payment
        $paymentData = [
            'amount' => 1350.00,
            'currency_id' => null,
            'method' => 'cash',
            'tx_reference' => null,
        ];

        $payment = $svc->cashierProcess($req, $cashier, $paymentData, [
            [
                'signed_by' => $cashier->id,
                'role' => 'cashier',
                'signature_type' => 'text',
                'signature_data' => 'Cashier Signature',
                'signed_at' => now(),
            ],
        ]);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'requisition_id' => $req->id,
            'amount' => 1350.00,
        ]);

        $req->refresh();
        $this->assertEquals(Requisition::STATUS_PAID, $req->status);
        $this->assertEquals($cashier->id, $req->assigned_cashier_user_id);
    }
}
