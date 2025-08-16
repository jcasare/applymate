<?php

namespace App\Services;

class GravatarService
{
    public static function getGravatarUrl(string $email, int $size = 200, string $default = 'mp'): string
    {
        $hash = md5(strtolower(trim($email)));
        return "https://www.gravatar.com/avatar/{$hash}?s={$size}&d={$default}";
    }

    public static function hasGravatar(string $email): bool
    {
        $hash = md5(strtolower(trim($email)));
        $url = "https://www.gravatar.com/avatar/{$hash}?d=404";
        
        $headers = @get_headers($url);
        return $headers && strpos($headers[0], '200') !== false;
    }
}