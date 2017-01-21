<?php

namespace Mindlahus\Helper;

class ThrowableHelper
{
    /**
     * @param $givenInstance
     * @param $expectedInstance
     * @throws \Throwable
     */
    public static function NotInstanceOf($givenInstance, $expectedInstance)
    {
        if (is_object($expectedInstance)) {
            $expectedInstance = get_class($expectedInstance);
        }

        if (!is_string($expectedInstance)) {
            throw new \Error('Expecting instance of a class. Unknown value given.');
        }

        if (is_object($givenInstance)) {
            $givenInstance = 'Instance of ' . strtoupper(get_class($givenInstance)) . ' was given.';
        } else {
            $givenInstance = strtoupper(gettype($givenInstance)) . ' given.';
        }

        throw new \Error("Expected instance of {$expectedInstance}. {$givenInstance}");
    }

    /**
     * @param array $errors
     * @return string
     */
    public static function ValidationErrorsToString(array $errors)
    {
        $str = '<h4>VALIDATION ERROR!</h4>';
        $str .= '<dl>';
        foreach ($errors as $error) {
            $str .= '<dt>' . strtoupper($error['propertyPath']) . '</dt>';
            $str .= '<dd>' . $error['message'] . '</dd>';
        }
        $str .= '</dl>';

        return $str;
    }

}