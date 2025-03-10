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
        $currentYear = Carbon::now()->year;
        $years = [$currentYear, $currentYear + 1];
        $months = [
            'January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December'
        ];

        foreach ($years as $year) {
            foreach ($months as $index => $month) {
                $startDate = Carbon::create($year, $index + 1, 1);
                $endDate = $startDate->copy()->endOfMonth();

                TwentyTwoDayInterval::create([
                    'start_date' => $startDate->toDateString(),
                    'end_date' => $endDate->toDateString(),
                ]);
            }
        }
    }
}
