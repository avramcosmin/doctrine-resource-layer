<?php

namespace Mindlahus\Traits\Entity;

trait ControlFieldTrait
{

    /**
     * @var
     *
     * @ORM\Column(type="string", length=40, nullable=true)
     */
    private $controlField;

    /**
     * @return $this
     */
    public function setControlField()
    {
        $this->controlField = bin2hex(random_bytes(20));

        return $this;
    }

    /**
     * @return mixed
     */
    public function getControlField()
    {
        return $this->getControlField();
    }

}