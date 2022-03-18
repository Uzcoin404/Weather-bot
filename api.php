<?php
class Api {
    private $API_KEY;

    public function __construct($API_KEY){
        $this->API_KEY = $API_KEY;
    }

    public function getWeather($latitude, $longitude, $previous = null){
        if ($previous == null) {
            if ($latitude != null && $longitude != null) {
                $url = "https://api.openweathermap.org/data/2.5/onecall?lat=$latitude&lon=$longitude&   exclude=hourly,minutely&units=metric&appid=$this->API_KEY";
            } else {
                return false;
            }
        } else {
            $url = "https://api.openweathermap.org/data/2.5/onecall/timemachine?lat=$latitude&lon=$longitude&dt=$previous&units=metric&appid=$this->API_KEY";
        }
        
        $client = curl_init($url);
        curl_setopt($client, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($client);
        curl_close($client);
        
        if ($error = curl_error($client)) {
            var_dump($error);
        } else {
            $result = json_decode($response, true);
            if (isset($result['lat'])) {
                return $result;
            } else {
                return $result['cod'];
            }
        }
    }

    public function getCityLocation($city, $lat = null, $lon = null){
        $city = trim($city);
        if ($lat != null && $lon != null) {
            $url = "https://api.openweathermap.org/data/2.5/weather?lat=$lat&lon=$lon&units=metric&appid=$this->API_KEY";
        } else if ($city != '') {
            $url = "https://api.openweathermap.org/data/2.5/weather?q=$city&units=metric&appid=$this->API_KEY";   
        } else {
            return false;
        }

        $client = curl_init($url);
        curl_setopt($client, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($client);
        curl_close($client);

        if (!curl_error($client)) {
            $result = json_decode($response, true);
            if ($result['cod'] == 200) {
                return $result;
            } else {
                return $result['cod'];
            }
        }
    }
}
?>