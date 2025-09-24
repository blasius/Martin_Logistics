<?php

namespace Tests\Feature;

use App\Models\ExpenseType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseTypeTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_an_expense_type()
    {
        $type = ExpenseType::create([
            'name' => 'Fuel',
            'description' => 'Diesel and petrol refills',
        ]);

        $this->assertDatabaseHas('expense_types', [
            'name' => 'Fuel',
        ]);
    }
}
