<?php

namespace App\Services;

use GuzzleHttp\Client;

class GeocodingService
{
    protected $client;
    protected $apiKey;

    public function __construct()
    {
        $this->client = new Client();
        $this->apiKey = env('GOOGLE_MAPS_API_KEY'); // Store your API key in .env
    }

    public function getAddress($latitude, $longitude)
    {
        $response = $this->client->get('https://maps.googleapis.com/maps/api/geocode/json', [
            'query' => [
                'latlng' => "{$latitude},{$longitude}",
                'key' => $this->apiKey,
            ],
        ]);

        $data = json_decode($response->getBody(), true);
        
        if ($data['status'] == 'OK') {
            return $data['results'][0]['formatted_address'] ?? null;
        }

        return null;
    }

    public function haversineGreatCircleDistance($latitudeFrom, $longitudeFrom, $latitudeTo, $longitudeTo)
    {
        $earthRadius = 6371000; // Earth radius in meters

        $latFrom = deg2rad($latitudeFrom);
        $lonFrom = deg2rad($longitudeFrom);
        $latTo = deg2rad($latitudeTo);
        $lonTo = deg2rad($longitudeTo);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
             cos($latFrom) * cos($latTo) *
             sin($lonDelta / 2) * sin($lonDelta / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c; // Distance in meters
    }
}