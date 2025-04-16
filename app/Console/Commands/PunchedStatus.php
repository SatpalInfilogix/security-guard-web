<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Models\GuardRoster;
use App\Models\Punch;

class PunchedStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'publish:punched-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Late Punched Status';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $today = Carbon::parse('2025-03-17 12:50:00');
        $rosters = GuardRoster::whereDate('date', $today)->get();

        $latePunches = [];
        foreach ($rosters as $roster) {
            $scheduledTime = Carbon::parse($roster->date . ' ' . $roster->start_time);
            $lateThreshold = $scheduledTime->copy()->addMinutes(15);
            if ($roster->late_punch_sent) {
                $this->info("Late punch already sent for Guard ID: {$roster->guard_id}, skipping...");
                continue;
            }
            $punchExists = Punch::where('user_id', $roster->guard_id)->whereBetween('in_time', [$scheduledTime, $lateThreshold])->orWhere('in_time', '<', $scheduledTime)->first();

            if ($today == $lateThreshold && !$punchExists) {

                if (!$punchExists || $punchExists->in_time < $scheduledTime || $punchExists->in_time > $lateThreshold) {
                    $latePunches[] = [
                        'roster_id' => $roster->id,
                        'client_site_id' => $roster->client_site_id,
                        'time' => $scheduledTime->format('Y-m-d H:i:s'),
                        'guard_id' => $roster->guard_id,
                    ];

                    $this->info("Guard ID: {$roster->guard_id} has not punched in on time for roster ID: {$roster->id}");
                    $roster->late_punch_sent = 1;
                    $roster->save();
                }
            } else {
                echo "No late punches found.";
            }
        }
    }
}
