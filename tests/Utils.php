<?php

namespace Inspector\Tests;


class Utils
{
    /**
     * Determin if the given string starts with a particular substring.
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