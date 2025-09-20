<?php
require 'vendor/autoload.php';

use GeoIp2\Database\Reader;

function getClientIP(): string
{
    $keys = [
        'HTTP_CLIENT_IP',
        'HTTP_X_FORWARDED_FOR', // may contain comma-separated list
        'HTTP_X_FORWARDED',
        'HTTP_X_CLUSTER_CLIENT_IP',
        'HTTP_FORWARDED_FOR',
        'HTTP_FORWARDED',
        'REMOTE_ADDR'
    ];
    foreach ($keys as $key) {
        if (!empty($_SERVER[$key])) {
            $ip = $_SERVER[$key];
            var_dump("Checking IP from $key: $ip");
            // if comma-separated list, take first non-private/non-reserved
            if (strpos($ip, ',') !== false) {
                $parts = array_map('trim', explode(',', $ip));
                foreach ($parts as $p) {
                    if (filter_var($p, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                        return $p;
                    }
                }
            } else {
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
    }
    return '0.0.0.0';
}

$reader = new Reader('./GeoLite2-City.mmdb'); // update path
$ip = getClientIP();

try {
    $record = $reader->city($ip);
    $country = $record->country->name;      // e.g. "United States"
    $countryIso = $record->country->isoCode;
    $city = $record->city->name;
    $lat = $record->location->latitude;
    $lon = $record->location->longitude;
    var_dump(">>", $country, $countryIso, $city, $lat, $lon);
} catch (\Exception $e) {
    var_dump("Error: " . $e->getMessage());
    // handle address not found or invalid IP
}
