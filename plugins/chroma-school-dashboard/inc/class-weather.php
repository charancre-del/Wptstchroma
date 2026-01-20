<?php

class Chroma_Weather_Provider
{
    public static function get_weather($lat, $lon)
    {
        if (!$lat || !$lon)
            return null;

        $cache_key = 'chroma_weather_' . md5($lat . $lon);
        $cached = get_transient($cache_key);

        if ($cached !== false) {
            return $cached;
        }

        // Fetch from Open-Meteo
        $url = "https://api.open-meteo.com/v1/forecast?latitude={$lat}&longitude={$lon}&current=temperature_2m,weather_code,is_day&temperature_unit=fahrenheit&timezone=auto";
        
        // Add timeout to prevent blocking (default is 5s, we want fail-fast)
        $response = wp_remote_get($url, ['timeout' => 2]);

        if (is_wp_error($response)) {
            return null;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (isset($data['current'])) {
            $weather = [
                'temp' => round($data['current']['temperature_2m']),
                'code' => $data['current']['weather_code'],
                'is_day' => $data['current']['is_day'],
                'description' => self::get_weather_desc($data['current']['weather_code'])
            ];
            set_transient($cache_key, $weather, 15 * MINUTE_IN_SECONDS);
            return $weather;
        }

        return null;
    }

    private static function get_weather_desc($code)
    {
        // Simple WMO code map
        $codes = [
            0 => 'Clear Sky',
            1 => 'Mainly Clear',
            2 => 'Partly Cloudy',
            3 => 'Overcast',
            45 => 'Fog',
            48 => 'Depositing Rime Fog',
            51 => 'Light Drizzle',
            53 => 'Drizzle',
            55 => 'Heavy Drizzle',
            61 => 'Rain',
            63 => 'Rain',
            65 => 'Heavy Rain',
            71 => 'Snow',
            73 => 'Snow',
            75 => 'Heavy Snow',
            95 => 'Thunderstorm'
        ];
        return $codes[$code] ?? 'Unknown';
    }
}
