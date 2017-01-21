<?php

namespace Mindlahus\Traits;

trait TestTrait
{
    /**
     * Tears down the Mockery framework
     */
    public function tearDown()
    {
        \Mockery::close();
    }
}