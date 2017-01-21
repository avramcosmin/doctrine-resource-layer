<?php

namespace Mindlahus\Helper;

class StringHelper
{

    /**
     * Shorten a string to the desired length.
     *
     * @param $str
     * @param int $length
     * @return string
     * @throws \Throwable
     */
    public static function shortenThis($str, int $length = null)
    {
        if (!$length) {
            $length = 255;
        }

        if ($length > 255) {
            throw new \Error('The length argument should be 255 or less.');
        }

        /**
         * [:space:] removes \t as well
         * http://stackoverflow.com/questions/2326125/remove-multiple-whitespaces
         * http://www.php.net/manual/en/regexp.reference.character-classes.php
         */
        $str = mb_ereg_replace('[[:space:]]+', ' ', strip_tags($str), "ms");
        $length = abs((int)$length - 1);

        /**
         * allow to only remove all special chars and html entities without shortening the string
         */
        if ($length === null) {
            return $str;
        }

        if (strlen($str) > $length) {
            $str = mb_ereg_replace('^(.{1,' . $length . '})([^\s]+)(\s.*|$)', '\\1\\2…', $str, "ms");
        }

        return $str;
    }

    /**
     * @param $str
     * @return mixed|string
     */
    public static function parsedownExtra($str)
    {

        if (!is_string($str) && !is_numeric($str)) {
            return '';
        }

        $parsedownExtra = new \ParsedownExtra();

        return $parsedownExtra->text($str);
    }

    /**
     * \DateTime::ISO8601 is not compatible with the ISO8601 itself
     * For compatibility use \DateTime::ATOM or just c
     *
     * @param \DateTime $val
     * @param string $format
     * @return string
     */
    public static function dateFormat(\DateTime $val, string $format = \DateTime::ATOM)
    {
        return $val->format($format);
    }


    /**
     * @param string $str
     * @param string $prefix
     * @return string
     */
    public static function toCamelCase(string $str, string $prefix = null)
    {
        $str = ucwords(strtolower(preg_replace('/[^A-z0-9]+/', ' ', $str)));

        if (!empty($prefix)) {
            $str = ucfirst(strtolower($prefix)) . $str;
        }

        return preg_replace('/\s+/', '', $str);
    }

    /**
     * @param $str
     * @param bool $jsonEncode
     * @return string
     */
    public static function base64url_encode($str, $jsonEncode = false)
    {
        if ($jsonEncode === true) {
            $str = json_encode($str);
        }
        $str = base64_encode($str);
        return strtr($str, '+/=', '-_,');
    }

    /**
     * @param $str
     * @param bool $jsonDecode
     * @return mixed|string
     */
    public static function base64url_decode($str, $jsonDecode = false)
    {
        $str = strtr($str, '-_,', '+/=');
        $str = base64_decode($str);
        if ($jsonDecode === true) {
            return json_decode($str, true);
        }
        return $str;
    }

    /**
     * @param $val
     * @return mixed
     */
    public static function isFloat($val)
    {
        // we do this because filter_var(true, FILTER_VALIDATE_FLOAT, FILTER_FLAG_ALLOW_THOUSAND) returns 1
        if ($val === true) {
            return false;
        }

        return filter_var($val, FILTER_VALIDATE_FLOAT, FILTER_FLAG_ALLOW_THOUSAND);
    }

    /**
     * @param $val
     * @return mixed
     */
    public static function isInt($val)
    {
        // we do this because filter_var(true, FILTER_VALIDATE_INT) returns 1
        if ($val === true) {
            return false;
        }

        return filter_var($val, FILTER_VALIDATE_INT);
    }

    /**
     * @param $val
     * @return bool|\DateTime
     */
    public static function isDateTime($val)
    {
        if ($val instanceof \DateTime) {
            return $val;
        }

        try {
            return new \DateTime($val, new \DateTimeZone('UTC'));
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * @param string $str
     * @param string $separator
     * @return string
     */
    public static function keepLatin(string $str, string $separator = '_') {
        return preg_replace('/[^a-zA-Z0-9]/', $separator, $str);
    }
}