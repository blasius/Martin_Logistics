<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class MockDispatchController extends Controller
{
    /**
     * Search for Clients with active, unfulfilled orders.
     */
    public function searchClients(Request $request)
    {
        $q = strtolower($request->query('q'));

        $clients = [
            ['id' => 1, 'name' => 'Bakhresa Group', 'active_orders_count' => 3],
            ['id' => 2, 'name' => 'MTN Rwanda', 'active_orders_count' => 1],
            ['id' => 3, 'name' => 'IKEA Logistics', 'active_orders_count' => 5],
            ['id' => 4, 'name' => 'World Food Programme', 'active_orders_count' => 12],
        ];

        return collect($clients)->filter(fn($c) => str_contains(strtolower($c['name']), $q))->values();
    }

    /**
     * Get the most recent pending order for a client.
     */
    public function getClientOrder($clientId)
    {
        // Mocking a multi-vehicle order: 100 Tons total, 40 already dispatched.
        return response()->json([
            'id' => 101,
            'reference' => 'ORD-2026-0045',
            'goods_type' => 'Wheat Flour',
            'tonnage' => 100,
            'remaining_tonnage' => 60,
            'origin' => 'Mombasa, KE',
            'destination' => 'Kigali, RW',
        ]);
    }

    /**
     * Get available Routes for the dropdown.
     */
    public function getRoutes()
    {
        return response()->json([
            [
                'id' => 1,
                'name' => 'Northern Corridor (Kigali - Mombasa)',
                'total_distance_km' => 1150,
                'geometry' => ['type' => 'LineString', 'coordinates' => [[30.06, -1.94], [34.76, -0.06], [39.66, -4.04]]]
            ],
            [
                'id' => 2,
                'name' => 'Central Corridor (Kigali - Dar Es Salaam)',
                'total_distance_km' => 1450,
                'geometry' => ['type' => 'LineString', 'coordinates' => [[30.06, -1.94], [32.89, -2.51], [39.20, -6.79]]]
            ]
        ]);
    }

    /**
     * Search for Vehicles/Drivers.
     */
    public function searchAssignments(Request $request)
    {
        $q = strtolower($request->query('q'));
        $data = [
            [
                'id' => 'vehicle-1',
                'label' => 'RAD 123 A (SinoTruck)',
                'type' => 'vehicle',
                'ratio' => 32.5,
                'age' => 2,
                'safety' => [
                    'insurance_expired' => false,
                    'inspection_expired' => false,
                    'has_unpaid_fines' => false,
                    'service_due' => false
                ]
            ],
            [
                'id' => 'vehicle-2',
                'label' => 'RAE 445 B (Mercedes Actros)',
                'type' => 'vehicle',
                'ratio' => 44.0,
                'age' => 6,
                'safety' => [
                    'insurance_expired' => true, // Simulate failure
                    'inspection_expired' => false,
                    'has_unpaid_fines' => false,
                    'service_due' => false
                ]
            ]
        ];

        return collect($data)->filter(fn($d) => str_contains(strtolower($d['label']), $q))->values();
    }
}
