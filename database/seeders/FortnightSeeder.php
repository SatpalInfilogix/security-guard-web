<?php

namespace Database\Seeders;

use App\Models\FortnightDates;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class FortnightSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $startDate = Carbon::now();

        $endDate = $startDate->copy()->addYear();

        while ($startDate->lessThanOrEqualTo($endDate)) {
            $fortnightEndDate = $startDate->copy()->addDays(13);

            FortnightDates::create([
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $fortnightEndDate->format('Y-m-d'),
            ]);

            $startDate = $fortnightEndDate->copy()->addDay();
        }
    }
}
