<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SupportCategory;

class SupportCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Fuel', 'description' => 'Fuel requests and shortages'],
            ['name' => 'Breakdown', 'description' => 'Vehicle breakdown or mechanical issue'],
            ['name' => 'Documents', 'description' => 'Missing or incorrect documents'],
            ['name' => 'Accident', 'description' => 'Accident or incident reporting'],
            ['name' => 'General', 'description' => 'Other support requests'],
        ];

        foreach ($categories as $category) {
            SupportCategory::firstOrCreate(
                ['name' => $category['name']],
                $category
            );
        }
    }
}
