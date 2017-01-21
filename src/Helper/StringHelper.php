<?php

namespace Mindlahus\Helper;

class StringHelper
{

    /**
     * Shorten a string to the desired length.
     *
     * @param $str
     * @param $length
     * @return mixed|string
     */
    public static function shortenThis($str, $length)
    {

        /**
         * [:space:] removes \t as well
         * http://stackoverflow.com/questions/2326125/remove-multiple-whitespaces
         * http://www.php.net/manual/en/regexp.reference.character-classes.php
         */
        $str = preg_replace('/[[:space:]]+/', ' ', strip_tags($str));
        $length = abs((int)$length - 1);

        /**
         * allow to only remove all special chars and html entities without shortening the string
         */
        if ($length === null) {
            return $str;
        }

        if (strlen($str) > $length) {
            $str = preg_replace('/^(.{1,' . $length . '})(\s.*|$)/s', '\\1…', $str);
        }

        return $str;
    }

    /**
     * @param $str
     * @return mixed|string
     */
    public static function parsedownExtra($str)
    {

        if (empty($str)) {
            return '';
        }

        $parsedownExtra = new \ParsedownExtra();
        return $parsedownExtra->text($str);
    }

    /**
     * \DateTime::ISO8601 is not compatible with the ISO8601 itself
     * For compatibility use \DateTime::ATOM or just c
     *
     * @param $dateTime
     * @param $format
     * @return string
     */
    public static function dateFormat($dateTime, $format = \DateTime::ATOM)
    {
        return $dateTime instanceof \DateTime
            ? $dateTime->format($format)
            : '';
    }


    /**
     * @param $str
     * @param string $separator
     * @param string|null $prefix
     * @return mixed
     */
    public static function toCamelCase($str, $prefix = null, $separator = "-")
    {
        if (!is_string($separator) OR empty($separator)) {
            $separator = '-';
        }

        $str = str_replace(" ", "", ucwords(str_replace($separator, " ", $str)));

        if (!is_string($prefix)) {
            return lcfirst($str);
        }

        return $prefix . $str;
    }

    public static function base64url_encode($str, $jsonEncode = false)
    {
        if ($jsonEncode) {
            $str = json_encode($str);
        }
        $str = base64_encode($str);
        return strtr($str, '+/=', '-_,');
    }

    public static function base64url_decode($str, $jsonDecode = false)
    {
        $str = strtr($str, '-_,', '+/=');
        $str = base64_decode($str);
        if ($jsonDecode) {
            return json_decode($str, true);
        }
        return $str;
    }
}