<?php

namespace App\Services;

use App\Models\FcmToken;
use Google\Client as GoogleClient;
use Exception;
use Illuminate\Support\Facades\Log;

class PushNotificationService
{
    protected $projectId;

    public function __construct()
    {
        $this->projectId = env('FIREBASE_PROJECT_ID');
    }

    public function sendNotification($userId, $title, $body)
    {
        $deviceTokens = FcmToken::where('user_id', $userId)->pluck('fcm_token')->toArray();
        foreach($deviceTokens as $deviceToken) {
            $this->send($deviceToken, $title, $body);
        }
    }

    protected function send($deviceToken, $title, $body)
    {
        try {
            $credentialsFilePath = public_path('service-account.json');
            $client = new GoogleClient();
            $client->setAuthConfig($credentialsFilePath);
            $client->addScope('https://www.googleapis.com/auth/firebase.messaging');
            $client->refreshTokenWithAssertion();
            $token = $client->getAccessToken();
            $access_token = $token['access_token'];

            $headers = [
                "Authorization: Bearer $access_token",
                'Content-Type: application/json'
            ];

            $data = [
                "message" => [
                    "token" => $deviceToken,
                    "notification" => [
                        "title" => $title,
                        "body" => $body,
                    ],
                ]
            ];
            $payload = json_encode($data);

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send");
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($ch, CURLOPT_VERBOSE, true); // Enable verbose output for debugging
            $response = curl_exec($ch);
            curl_close($ch);

            $decodedResponse = json_decode($response, true);
            if (isset($decodedResponse['error'])) {
                throw new Exception('FCM Error: ' . $decodedResponse['error']);
            }

            return $decodedResponse;
        } catch (Exception $e) {
            // Log the error or handle it accordingly
            Log::error('Error sending push notification: ' . $e->getMessage());
            return null;
        }
    }
}