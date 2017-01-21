<?php

namespace Mindlahus\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class ConstrainsInt extends Constraint
{
    public $message = '"%string%" should be of type float.';
}