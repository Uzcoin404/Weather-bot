<?php
date_default_timezone_set('Asia/Tashkent');
require_once('library/Telegram.php');
require_once('api.php');
include_once('user.php');

$telegram = new Telegram("5117795199:AAGg4FIN3IQ4v55uNNdK_88JQJroWNkACHQ", true);
$api = new Api("c2519906f5612a7f2b8c9953409e0626");
$Admin = "829349149";

$message = $telegram->getData()['message'];
$chat_id = $telegram ->ChatID();
$text = $telegram ->Text();
$first_name = $telegram -> FirstName();
$last_name = $telegram -> LastName();
$username = $telegram -> Username();
$location = $telegram -> Location();
$latitude = isset($location['latitude']) ? $location['latitude'] : '';
$longitude = isset($location['longitude']) ? $location['longitude'] : '';
$userLat = getData($chat_id, 'latitude');
$userLon = getData($chat_id, 'longitude');

if ($text == '/start') {
    showStart();
    setData($chat_id, 'page', 'start');
} else {
    switch (getData($chat_id, 'page')) {
        case 'start':
            if ($text == 'Shahar') {
                getCityName();
                setData($chat_id, 'page', 'getCity');
            } else if ($location) {
                gettingReady();
                $weather = $api->getCityLocation('', $latitude, $longitude);
                setData($chat_id, 'latitude', $latitude);
                setData($chat_id, 'longitude', $longitude);
                setData($chat_id, 'page', 'main');
                if (isset($weather['coord']['lat'])) {
                    setData($chat_id, 'city', $weather['name']. ' (' .$weather['sys']['country'] . ')');
                }
            } else if ($text == 'Joylashuvim'){
                $telegram->sendMessage(['chat_id' => $chat_id, 'text' => "Sizning qurilmangiz lokatsiyani yubora olmadi"]);
            } else {
                $telegram->sendMessage(['chat_id' => $chat_id, 'text' => "Manzilda adashdingiz /start ni bosing"]);
            }
            break;
        case 'getCity':
            $weather = $api->getCityLocation($text);
            if (isset($weather['coord']['lat'])) {
                setData($chat_id, 'latitude', $weather['coord']['lat']);
                setData($chat_id, 'longitude', $weather['coord']['lon']);
                setData($chat_id, 'city', $weather['name']. ' (' .$weather['sys']['country'] . ')');
                setData($chat_id, 'page', 'main');
                gettingReady();
            } else if ($text == 'Orqaga') {
                setData($chat_id, 'page', 'start');
                showStart();
            } else {
                $content = ['chat_id' => $chat_id, 'text' => "||Shahar topilmadi\! Shahar nomini tekshirib Qaytadan urinib ko'ring||", 'parse_mode' => 'markdownV2'];
                $telegram->sendMessage($content);
            }
            break;
        case 'location':
            
            break;
        case 'main':
            if ($text != 'Bosh sahifa') {
                showWeather($userLat, $userLon, $text);
            } else {
                setData($chat_id, 'page', 'start');
                showStart();
            }
            break;
        default:
            $telegram->sendMessage(['chat_id' => $chat_id, 'text' => "Manzilda adashdingiz /start ni bosing"]);
    }
}

function showStart() {
    global $telegram, $chat_id, $api;

    $month = showMonths();
    $week =  showWeek();
    $weather = $api->getWeather(41.2646, 69.2163)['current'];
    $hour = date('H');
    $min = date('i');
    $day = round(date('d'));
    $condition = weatherCondition($weather);
    $temp = weatherWarm($weather['temp']);
    
    $text = welcome()." Bugun $month oyining $day-inchi kuni $week. Naqadar fayzli kun \nBugun O'zbekistonda havo $condition $temp \nSoat millari $hour dan $min daqiqa o'tganini ko'rsatmoqda";
    $content = ['chat_id' => $chat_id, 'text' => $text];
    $telegram->sendMessage($content);
    
    $main_menu = $telegram->buildKeyBoard([
    [$telegram->buildKeyboardButton("Shahar"),
    $telegram->buildKeyboardButton("Joylashuvim", false, true)]]);

    $telegram->sendMessage(['chat_id' => $chat_id, 'text' => "Quyidagi berilgan tugmalar orqali Ob-havoni bilib oling",  'reply_markup' => $main_menu]);
}

function getCityName(){
    global $telegram, $chat_id;

    $backButton = $telegram->buildKeyBoard([[$telegram->buildKeyboardButton('Orqaga')]]);
    $content = ['chat_id' => $chat_id, 'text' => "_Iltimos Shahar nomini Lotin yozuvi va Ingliz tilida yuboring_", 'parse_mode' => 'markdownV2', 'reply_markup' => $backButton];
    $telegram->sendMessage($content);
}

function gettingReady(){
    global $telegram, $chat_id;

    $weatherButtons = $telegram->buildKeyBoard([[$telegram->buildKeyboardButton('Ayni payt'), $telegram->buildKeyboardButton('Bugun')],
    [$telegram->buildKeyboardButton('Kecha'),
    $telegram->buildKeyboardButton('Ertaga')],
    [$telegram->buildKeyboardButton('7 Kunlik'), $telegram->buildKeyboardButton('Bosh sahifa')]]);

    $content = ['chat_id' => $chat_id, 'text' => "*Ob\-havo ma'lumotlari olindi Quyidagi Prognozlarni ko'rishingiz mumkin*", 'parse_mode' => 'markdownV2', 'reply_markup' => $weatherButtons];
    $telegram->sendMessage($content);
}

function showWeather($lat, $lon, $type){
    global $telegram, $chat_id, $api;
    $previous = false;
    $current = false;
    $daily = false;

    if (trim($lat) != '' && trim($lon) != '' && trim($type) != '') {
        switch ($type) {
            case 'Ayni payt':
                $weather = $api->getWeather($lat, $lon)['current'];
                $current = true;
                break;
            case 'Bugun':
                $weather = $api->getWeather($lat, $lon)['daily'][0];
                $daily = true;
                break;
            case 'Kecha':
                $weather = $api->getWeather($lat, $lon, strtotime('00:00'))['hourly'];
                $previous = true;
                break;
            case 'Ertaga':
                $weather = $api->getWeather($lat, $lon)['daily'][1];
                $daily = true;
                break;
            case '7 Kunlik':
                $weather = $api->getWeather($lat, $lon)['daily'];
                $sevenDay = true;
                break;
            default:
                $telegram->sendMessage(['chat_id' => $chat_id, 'text' => "Bunday buyruq yo'q! Iltimos tugmalardan birini bosing"]);
                return false;
                break;
        }
    } else {
        $telegram->sendMessage(['chat_id' => $chat_id, 'text' => "Ma'lumotlar olinmadi"]);
        return false;
    }

    $city = getData($chat_id, 'city');

    if ($daily) {
        $feels_like = round($weather['feels_like']['day']) ."°C";
    } else {
        $feels_like = round($weather['feels_like']) ."°C";
    }

    $temp_min = round($weather['temp']['min']) ."°C";
    $temp_max = round($weather['temp']['max']) ."°C";

    if (!$previous) {
        $sunrise = date('H:i', $weather['sunrise']);
        $sunset = date('H:i', $weather['sunset']);
    }

    $pressure = $weather['pressure'] ."mbar";
    $humidity = $weather['humidity'] ."%";
    $wind = round($weather['wind_speed']) ."m/s";
    $clouds = $weather['clouds'] ."ta";
    $date = date('Y.m.d H:i', $weather['dt']);

    $condition = weatherCondition($weather);
    if ($daily) {
        $temp = weatherWarm($weather['temp']['day']);
    } else {
        $temp = weatherWarm($weather['temp']);
    }
    $fixedText = "Bosim:  $pressure, \nNamlik:  $humidity,\nOsmonda ko'rinadigan (taxminiy) bulutlar soni:  $clouds, \nShamol tezligi:  $wind,";
    $text = '';

    if (!$previous) {
        if ($current) {
            $weatherType = $type . 'dagi';

            $text = "$city Shahridagi $weatherType Ob-havo:
            $condition $temp
            Haqiqiy his:  $feels_like,
            $fixedText
            Quyosh chiqishi:  $sunrise,
            Quyosh botishi:  $sunset,
        
            Sana: $date vaqtiga ko'ra ma'lumotlar olindi";
        } else if ($daily) {
            $weatherType = $type . 'ngi';
            $moon_phase = ($weather['moon_phase'] * 100) . '%';
            $precipitation = ($weather['pop'] * 100) . '%';

            $text = "$city Shahridagi $weatherType Ob-havo:
            $condition $temp
            Haqiqiy his:  $feels_like,
            Minimal harorat:  $temp_min,
            Maximal harorat:  $temp_max,
            $fixedText
            Yog'ingarchilik ehtimoli: $precipitation,
            Oyning to'linligi: $moon_phase,
            Quyosh chiqishi:  $sunrise,
            Quyosh botishi:  $sunset";
        } else if (isset($sevenDay)) {
            for ($i=0; $i < count($weather); $i++) { 
                $feels_like = round($weather[$i]['feels_like']['day']) ."°C";
            
                $temp_min = round($weather[$i]['temp']['min']) ."°C";
                $temp_max = round($weather[$i]['temp']['max']) ."°C";
                $sunrise = date('H:i', $weather[$i]['sunrise']);
                $sunset = date('H:i', $weather[$i]['sunset']);
            
                $pressure = $weather[$i]['pressure'] ."mbar";
                $humidity = $weather[$i]['humidity'] ."%";
                $wind = round($weather[$i]['wind_speed']) ."m/s";
                $condition = weatherCondition($weather[$i]);
                $temp = weatherWarm($weather[$i]['temp']['day']);

                
                $month = showMonths($weather[$i]['dt']) . 'dagi';
                $day = date('d', $weather[$i]['dt']);
                $moon_phase = ($weather[$i]['moon_phase'] * 100) . '%';
                $precipitation = ($weather[$i]['pop'] * 100) . '%';
                
                $fixedText = "Bosim:  $pressure, \nNamlik:  $humidity, \nShamol tezligi:  $wind,";

                $text = "$city Shahridagi $day-$month Ob-havo:
                $condition $temp
                Haqiqiy his:  $feels_like,
                Minimal harorat:  $temp_min,
                Maximal harorat:  $temp_max,
                $fixedText
                Yog'ingarchilik ehtimoli: $precipitation,
                Oyning to'linligi: $moon_phase,
                Quyosh chiqishi:  $sunrise,
                Quyosh botishi:  $sunset\n\n";

                count($weather)-1 != $i ? $telegram->sendMessage(['chat_id' => $chat_id, 'text' => $text]) : '';
            }
        }
    } else {
        $dailyTemp = 0;
        $pressure = 0;
        $humidity = 0;
        $clouds = 0;
        for ($i=0; $i < count($weather); $i++) { 
            
            $feels_like += $weather[$i]['feels_like'];
            $pressure += $weather[$i]['pressure'];
            $humidity += $weather[$i]['humidity'];

            $wind += $weather[$i]['wind_speed'];
            $clouds += $weather[$i]['clouds'];
            $condition = weatherCondition($weather[$i]);
            
            $dailyTemp += $weather[$i]['temp'];
        }
        
        $temp = weatherWarm($dailyTemp / 24);
        $wind = round($wind / 24) ."m/s";
        $clouds = round($clouds / 24) ."ta";
        $feels_like = round($feels_like / 24) ."°C";
        $pressure = round($pressure / 24)  ."mbar";
        $humidity = round($humidity / 24)  ."%";

        $fixedText = "Bosim:  $pressure, \nNamlik:  $humidity,\nOsmonda ko'rinadigan (taxminiy) bulutlar soni:  $clouds, \nShamol tezligi:  $wind";
        $weatherType = $type . 'gi';

        $text = "$city Shahridagi $weatherType Ob-havo:
        $condition $temp
        Haqiqiy his:  $feels_like,
        $fixedText";
    }

    $telegram->sendMessage(['chat_id' => $chat_id, 'text' => $text]);
}

function welcome(){
    $hour = date('H');

    if($hour < 10 && $hour >= 4){
        return "Xayrli tong";
    } elseif($hour >= 10 && $hour < 18){
        return "Xayrli Kun";
    } elseif($hour >= 18 || $hour < 4){
        return "Xayrli Kech";
    }
}

function showWeek(){
    $week = ['Yakshanba', 'Dushanba', 'Seshanba', 'Chorshanba', 'Payshanba', 'Juma', 'Shanba'];
    $weekNumber = date('N');
    if (isset($week[$weekNumber])) {
        return $week[$weekNumber];
    }
}

function showMonths($previous = null){
    $months = ['yanvar', 'fevral', 'mart', 'aprel', 'may', 'iyun', 'iyul', 'avgust', 'sentabr', 'oktabr', 'noyabr', 'dekabr'];
    for ($i=0; $i < count($months)-1; $i++) { 
        if ($previous == null) {
            if (date('m')-1 == $i) {
                return $months[$i];
            }  
        } else {
            if (date('m', $previous)-1 == $i) {
                return $months[$i];
            }
        }
    }
}

function weatherCondition($weather){

    $weatherMain = $weather['weather'][0]['main'];
    $conditions = ['Thunderstorm' => 'Momaqaldiroq', 'Drizzle' => "Yog'ingarchilik", 'Rain' => "Yomg'ir", 'Snow' => 'Qor', 'Clear' => 'Ochiq', 'Clouds' => 'Bulutli', 'Mist' => 'Tumanli', 'Fog' => 'Tumanli'];

    if (isset($weatherMain)) {   
        if (isset($conditions[$weatherMain])) {
            return $conditions[$weatherMain];
        } else {
            return $weatherMain;
        }
    }
}

function weatherWarm($temp){
    if (isset($temp)) {
        $temp = round($temp);
        if ($temp > 10) {
            return $temp . "°C issiq";
        } else {
            return $temp . "°C sovuq";
        }
    }
}

function resendMessage($text){
    global $telegram, $chat_id;
    $content = ['chat_id' => $chat_id, 'text' => $text];
    $telegram->sendMessage($content);
}
?>