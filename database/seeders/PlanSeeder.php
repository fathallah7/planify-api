<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        Plan::create([
            'name'          => 'free',
            'price'         => 0,
            'billing_cycle' => 'monthly',
            'max_projects'  => 1,
            'max_members'   => 5,
        ]);

        Plan::create([
            'name'          => 'basic',
            'price'         => 9,
            'billing_cycle' => 'monthly',
            'max_projects'  => 3,
            'max_members'   => 5,
        ]);

        Plan::create([
            'name'          => 'pro',
            'price'         => 29,
            'billing_cycle' => 'monthly',
            'max_projects'  => null,
            'max_members'   => null,
        ]);
    }
}
