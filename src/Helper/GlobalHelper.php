<?php

namespace Mindlahus\Helper;

class GlobalHelper
{
    /**
     * @param $givenInstance
     * @param $expectedInstance
     * @return bool
     */
    public static function isInstanceOf($givenInstance, $expectedInstance)
    {
        return $givenInstance instanceof $expectedInstance;
    }
}