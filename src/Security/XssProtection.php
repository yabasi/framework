<?php

namespace Yabasi\Security;

class XssProtection
{
    public static function clean($data)
    {
        if (is_array($data)) {
            return array_map([self::class, 'clean'], $data);
        }

        return is_string($data) ? self::cleanString($data) : $data;
    }

    protected static function cleanString($string)
    {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }
}