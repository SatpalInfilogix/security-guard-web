<?php
 
namespace App\Services;
 
use GuzzleHttp\Client;
use Log;
 
class GeocodingService
{
    protected $client;
    protected $apiKey;
 
    public function __construct()
    {
        $this->client = new Client();
        $this->apiKey = env('GOOGLE_MAPS_API_KEY');
      	
        if(!$this->apiKey){
          $this->apiKey = 'AIzaSyDH1SI6dbHkWwNjDk6OCH4szg5KTlZiycA';
        }
    }
 
    public function getAddress($latitude, $longitude)
    {
        $response = $this->client->get('https://maps.googleapis.com/maps/api/geocode/json', [
            'query' => [
                'latlng' => "$latitude,$longitude",
                'key' => $this->apiKey,
            ],
        ]);
      
 
        $data = json_decode($response->getBody(), true);
        
        if ($data['status'] == 'OK') {
            return $data['results'][0] ?? null;
        }
 
        return null;
    }
 
    public function trackUserDistanceFromSite($clientLat, $clientLong, $userLat, $userLong)
    {              
        $response = $this->client->get('https://maps.googleapis.com/maps/api/distancematrix/json',[
            'query' =>[
                'origins' =>"$userLat,$userLong",
                'destinations' =>"$clientLat,$clientLong",
                'key' =>$this->apiKey,
            ],
        ]);
 
        $data = json_decode($response->getBody(), true);
 
        if (isset($data['rows'][0]['elements'][0])) {
            return $data['rows'][0]['elements'][0];
        }
 
        return null;
    }
}