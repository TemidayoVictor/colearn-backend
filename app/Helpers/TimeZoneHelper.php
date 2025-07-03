<?php

namespace App\Helpers;

class TimezoneHelper
{
    public static function mapCountryToTimezone(string $countryCode): string
    {
        $mapping = [
            'NG' => 'Africa/Lagos',
            'US' => 'America/New_York',
            'GB' => 'Europe/London',
            'IN' => 'Asia/Kolkata',
            'ZA' => 'Africa/Johannesburg',
            'EG' => 'Africa/Cairo',
            'KE' => 'Africa/Nairobi',
            'MA' => 'Africa/Casablanca',
            'DE' => 'Europe/Berlin',
            'FR' => 'Europe/Paris',
            'BR' => 'America/Sao_Paulo',
            'JP' => 'Asia/Tokyo',
            'CN' => 'Asia/Shanghai',
            'RU' => 'Europe/Moscow',
            'AU' => 'Australia/Sydney',
            // ... add more as needed
        ];

        return $mapping[$countryCode] ?? 'UTC'; // default fallback
    }
}
