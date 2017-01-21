<?php

namespace Mindlahus\Helper;

class ExceptionHelper
{

    const EXCEPTION_CODE_VALIDATION_ERROR = 1001;
    const EXCEPTION_CODE_REQUIRES_PERSIST = 1002;

    /**
     * @param \Exception $e
     * @throws \Exception
     */
    public static function PropagateException(\Exception $e)
    {
        throw new \Exception(
            $e->getMessage(),
            $e->getCode()
        );
    }

    public static function ValidationException()
    {

    }

    public static function errorsToString(array $errors)
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