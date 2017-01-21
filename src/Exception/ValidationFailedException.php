<?php

namespace Mindlahus\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationListInterface;

class ValidationFailedException extends HttpException
{
    public function __construct(ConstraintViolationListInterface $errors)
    {
        $response = [
            'message' => 'Validation failed',
            'errors' => []
        ];

        /**
         * @var ConstraintViolation $error
         */
        foreach ($errors as $error) {
            $response['errors'][] = [
                'propertyPath' => $error->getPropertyPath(),
                'message' => $error->getMessage()
            ];
        }

        parent::__construct(500, json_encode($response));
    }
}