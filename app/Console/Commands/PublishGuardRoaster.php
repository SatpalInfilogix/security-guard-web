<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\FortnightDates;
use App\Models\GuardRoster;
use Carbon\Carbon;
use Spatie\Permission\Models\Role;

class PublishGuardRoaster extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'publish:guard-roaster';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Guard Roaster';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $today = Carbon::now()->startOfDay();
        $fortnightDays = FortnightDates::whereDate('start_date', '<=', $today)->whereDate('end_date', '>=', $today)->first();
        $endDate = Carbon::parse($fortnightDays->end_date)->startOfDay();

        $differenceInDays = $today->diffInDays($endDate, false); 

        $nextStartDate = Carbon::parse($fortnightDays->end_date)->addDay();
        $nextEndDate = $nextStartDate->copy()->addDays(13);
       
        if ($differenceInDays == 2) {
            $roster = GuardRoster::where('date', '>=', $fortnightDays->start_date)->where('end_date', '<=', $fortnightDays->end_date)->get();
            
            $nextFortnightRoster = GuardRoster::whereDate('date', '>=', $nextStartDate)->whereDate('end_date', '<=', $nextEndDate)->get();
            if ($nextFortnightRoster->isEmpty()) {
                foreach ($roster as $currentRoster) {
                    $shiftedDate = Carbon::parse($currentRoster->date)->addDays(14);  // Shifted date by 14 days
                    $startTime = Carbon::parse($currentRoster->start_time);
                    $endTime = Carbon::parse($currentRoster->end_time);
                    
                    $existingRoster = GuardRoster::where('guard_id', $currentRoster->guard_id)->where('client_site_id', $currentRoster->client_site_id)->where('date', '=', $shiftedDate->format('Y-m-d'))->first();
                    
                    if ($existingRoster) {
                        continue;
                    }

                    $endDate = $shiftedDate->copy();
                    if ($endTime->lessThan($startTime)) {
                        $endDateForNextRoster = $endDate->addDay();
                    } else {
                        $endDateForNextRoster = $endDate;
                    }

                    GuardRoster::create([
                        'guard_id' => $currentRoster->guard_id,
                        'client_id' => $currentRoster->client_id,
                        'client_site_id' => $currentRoster->client_site_id,
                        'date' => $shiftedDate->format('Y-m-d'),
                        'end_date' => $endDateForNextRoster->format('Y-m-d'),
                        'start_time' => $currentRoster->start_time,
                        'end_time' => $currentRoster->end_time,
                    ]);
                }
            }
        } else if ($differenceInDays == 1) {
            $newFortnightRoster = GuardRoster::whereDate('date', '>=', $nextStartDate)->whereDate('end_date', '<=', $nextEndDate)->get();
            foreach ($newFortnightRoster as $currentRoster) {
                $currentRoster->update([
                    'is_publish' => 1
                ]);
            }
        }
    }

}
