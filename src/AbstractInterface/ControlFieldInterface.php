<?php

namespace Mindlahus\AbstractInterface;

interface ControlFieldInterface
{
    /**
     * @return $this
     */
    public function setControlField();

    /**
     * @return mixed
     */
    public function getControlField();
}