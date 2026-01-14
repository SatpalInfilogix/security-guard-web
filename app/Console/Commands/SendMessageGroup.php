<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Models\GuardRoster;
use App\Models\Punch;

class SendMessageGroup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send-sms:send-sms-in-group';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {  
        //$today = Carbon::parse('2025-11-04 22:25:00');
      	$today = Carbon::now();
        $rosters = GuardRoster::whereDate('date', $today)->get();

        $latePunches = [];
        foreach ($rosters as $roster) {
            $scheduledTime = Carbon::parse($roster->date . ' ' . $roster->start_time);
            $lateThreshold = $scheduledTime->copy()->addMinutes(15);
            if ($roster->late_punch_sent) {
                	$message = "Late punch already sent for Guard ID: {$roster->guard_id}, skipping...";
                    //$message = "Guard ID: {$roster->guard_id} has not punched in on time for roster ID: {$roster->id}";
                  	$response = $this->sendSmsInGroup($message);
                    if ($response) {
                        $this->info('Message sent successfully: ' . $response);
                    } else {
                        $this->error('Failed to send message. skipping');
                    }
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
                  
                  	$message = "Guard ID: {$roster->guard_id} has not punched in on time for roster ID: {$roster->id}";
                  	$response = $this->sendSmsInGroup($message);
                    if ($response) {
                        $this->info('Message sent successfully: ' . $response);
                    } else {
                        $this->error('Failed to send message.');
                    }

                    //$this->info($message);
                    $roster->late_punch_sent = 1;
                    $roster->save();
                }
            } else {
                //echo "No late punches found.";
                $situationText = "Situation 1\n".
                "Client Site Name: NWC Mona\n".
                "Guards Rostered: 3\n".
                "Shift Start Time: 4:00 AM\n".
                "Last Checked Time: 4:15 AM\n".
                "Guards Checked-in: 2\n".
                "SITE NOT COVERED";
               $this->sendSmsInGroup($situationText);
               $this->info('No late punches found.');
            }
        }
    }
    public function sendSmsInGroup($message)
    {
        $whats_group_id = env('WHATSAPP_GROUP_ID');
        $whats_api_url = env('WHATSAPP_API_URL');
        $whats_api_token = env('WHATSAPP_API_TOKEN');  

        $payload = json_encode([
            'to' => $whats_group_id,
            'body' => $message,
        ]);

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $whats_api_url.'/messages/text',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Content-Type: application/json',
                'Authorization: Bearer ' . $whats_api_token,
            ],
        ]);

        $response = curl_exec($curl);

        if (curl_errno($curl)) {
            \Log::error('cURL error: ' . curl_error($curl));
            curl_close($curl);
            return false;
        }

        curl_close($curl);

        \Log::info('WhatsApp response: ' . $response);
        return $response;
    }
}
