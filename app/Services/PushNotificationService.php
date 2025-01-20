<?php

namespace App\Services;

use App\Models\FcmToken;
use Google\Client as GoogleClient;

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
            $response = $this->send($deviceToken, $title, $body);
            print_r($response);
        }
        die();
    }

    protected function send($deviceToken, $title, $body)
    {
        $credentialsFilePath = public_path('service-account.json');
        $client = new GoogleClient();
        $client->setAuthConfig($credentialsFilePath);
        $client->addScope('https://www.googleapis.com/auth/firebase.messaging');
        $client->refreshTokenWithAssertion();
        $token = $client->getAccessToken();

        $access_token = $token['access_token'];

        $url = 'https://fcm.googleapis.com/v1/projects/'.$this->projectId.'/messages:send';
        $data = [
            'message' => [
                'token' => $deviceToken,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                ],
            ],
        ];

        $headers = [
            'Authorization: Bearer '.$access_token,
            'Content-Type: application/json',
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true);
    }
}