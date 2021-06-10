<?php


namespace Inspector;


class OS
{
    public static function isWin()
    {
        return 'WIN' === static::getOsPrefix(); // Should return "Windows"
    }

    public static function isLinux()
    {
        return in_array(static::getOsPrefix(), ['LIN', 'BSD', 'SOL']);
    }

    public function isMacOs()
    {
        return 'DAR' === static::getOsPrefix(); // Should return "Darwin"
    }

    public static function getOsPrefix()
    {
        return strtoupper(substr(PHP_OS_FAMILY, 0, 3));
    }
}
