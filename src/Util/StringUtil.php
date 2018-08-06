<?php

declare(strict_types = 1);

namespace Webduck\Util;

class StringUtil
{
    public static function urlSafeBase64Encode(string $string): string
    {
        return strtr(base64_encode($string), '+/=', '._-');
    }

    public static function urlSafeBase64Decode(string $string): string
    {
        return base64_decode(strtr($string, '._-', '+/='));
    }
}
