<?php

namespace LogEngine\Tests;


class Utils
{
    /**
     * Determine if the given string start with a particular substring.
     *
     * @param $haystack
     * @param $needle
     * @return bool
     */
    public static function startsWith($haystack, $needle)
    {
        return substr($haystack, 0, strlen($needle)) === $needle;
    }
}