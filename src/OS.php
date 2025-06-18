<?php

namespace Inspector;

class OS
{
    public static function isWin(): bool
    {
        return 'WIN' === static::getOsPrefix(); // Should return "Windows"
    }

    public static function isLinux(): bool
    {
        return \in_array(static::getOsPrefix(), ['LIN', 'BSD', 'SOL']);
    }

    public function isMacOs(): bool
    {
        return 'DAR' === static::getOsPrefix(); // Should return "Darwin"
    }

    public static function getOsPrefix(): string
    {
        return \strtoupper(\substr(\PHP_OS_FAMILY, 0, 3));
    }
}
