<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\TwentyTwoDayInterval;
use Carbon\Carbon;

class TwentyTwoDaysCycleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $startDate = Carbon::now();

        $endDate = $startDate->copy()->addYear();

        while ($startDate->lessThanOrEqualTo($endDate)) {
            $intervalEndDate = $startDate->copy()->addDays(21);

            TwentyTwoDayInterval::create([
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $intervalEndDate->format('Y-m-d'),
            ]);

            $startDate = $intervalEndDate->copy()->addDay();
        }
    }
}
