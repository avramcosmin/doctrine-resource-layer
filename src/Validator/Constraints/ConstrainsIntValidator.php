<?php

namespace Mindlahus\Validator\Constraints;

use Mindlahus\Helper\StringHelper;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class ConstrainsIntValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        if (!StringHelper::isFloat($value)) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('%string%', $value)
                ->addViolation();
        }
    }
}