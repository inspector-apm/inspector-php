<?php


namespace Inspector;


class OS
{
    public static function isWin()
    {
        return 'WIN' === strtoupper(substr(PHP_OS, 0, 3));
    }

    public static function isLinux()
    {
        return !static::isWin();
    }
}
