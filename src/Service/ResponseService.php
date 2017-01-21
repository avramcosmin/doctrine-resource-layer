<?php

namespace Mindlahus\Service;

use Mindlahus\Helper\ExceptionHelper;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Doctrine\Common\Annotations\AnnotationReader;
use Symfony\Component\Serializer\Mapping\Loader\AnnotationLoader;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\ConstraintViolationList;

class ResponseService
{
    const RESPONSE_TYPE_VALIDATION_ERRORS = 'validation_errors';
    const RESPONSE_TYPE_EXCEPTION = 'exception';
    const RESPONSE_TYPE_NOT_FOUND = 'not_found';
    const RESPONSE_TYPE_FORBIDDEN = 'forbidden';
    const RESPONSE_TYPE_SUCCESS = 'success';
    const RESPONSE_TYPE_DOCTRINE_OBJECT = 'doctrine_object';
    const PAGE_SETTINGS = [
        'totalNumberOfEntities' => 0,
        'take' => 50,
        'start' => 1,
        'filterBy' => [],
        'sortBy' => []
    ];

    protected $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack->getCurrentRequest();
    }

    /**
     * @param $errors
     * @param string $returnType
     * @return Response
     * @throws \Exception
     */
    public function createValidationErrorJsonResponse($errors, $returnType = 'array')
    {
        return $this->createJsonResponse(
            $this->_getSerializedValidationErrors($errors, $returnType)
        );
    }

    /**
     * @param $errors
     * @param string $returnType
     * @return array|string
     * @throws \Exception
     */
    public function _getSerializedValidationErrors($errors, $returnType = 'array')
    {

        $data = [
            'type' => ResponseService::RESPONSE_TYPE_VALIDATION_ERRORS,
            'errors' => []
        ];

        if ($errors instanceof ConstraintViolationList) {
            $classMetadataFactory = new ClassMetadataFactory(new AnnotationLoader(new AnnotationReader()));
            $encoder = new JsonEncoder();
            $normalizer = new ObjectNormalizer($classMetadataFactory);
            $normalizer->setIgnoredAttributes([
                'messageTemplate', 'messageParameters', 'parameters', 'messagePluralization', 'plural',
                'root', 'invalidValue', 'constraint', 'cause', 'code'
            ]);

            $serializer = new Serializer([$normalizer], [$encoder]);

            $errors = $normalizer->normalize($errors);
            if (isset($errors['iterator'])) {
                $data['errors'] = $errors['iterator'];
            }

            if ($returnType === 'array') {
                return [
                    'serializer' => $serializer,
                    'data' => $data
                ];
            } elseif ($returnType === 'json') {
                return $serializer->serialize($data, 'json');
            }
        } elseif (is_array($errors)) {
            $data['errors'] = $errors;
        } else {
            throw new \Exception('Expecting a ConstraintViolationList instance or an array. '
                . ucfirst(gettype($errors))
                . ' received.');
        }

        switch ($returnType) {
            case 'html':
                return ExceptionHelper::errorsToString($data['errors']);
            case 'serialized':
                return serialize($data['errors']);
            case 'simplified':
                return [
                    'data' => $data
                ];
            default:
                throw new \Exception('Missing return type information!');
        }
    }

    /**
     * @param $options (string|array|\Exception) = [
     *      'status'
     *      'type'
     *      'message'
     *      'trace'
     *      'code'
     *      'line'
     *      'file'
     * ]
     * @return Response
     */
    public function createExceptionJsonResponse($options)
    {
        if ($options instanceof \Exception) {
            $e = $options;
            $options = [];
            $options['message'] = $e->getMessage();
            $options['trace'] = $e->getTraceAsString();
            $options['code'] = $e->getCode();
            $options['line'] = $e->getLine();
            $options['file'] = $e->getFile();
        } elseif (is_string($options)) {
            $options = [
                'message' => $options
            ];
        }

        $resolver = new OptionsResolver();
        $resolver->setDefined([
            'status',
            'type',
            'message',
            'trace',
            'code',
            'line',
            'file'
        ])
            ->setRequired(['message'])
            ->setAllowedTypes('status', ['numeric'])
            ->setAllowedTypes('type', ['string'])
            ->setAllowedTypes('message', ['string'])
            ->setAllowedTypes('trace', ['string', 'null'])
            ->setAllowedTypes('code', ['numeric', 'null'])
            ->setAllowedTypes('line', ['numeric', 'null'])
            ->setAllowedTypes('file', ['string'])
            ->setDefaults([
                'status' => Response::HTTP_OK,
                'type' => ResponseService::RESPONSE_TYPE_EXCEPTION,
                'message' => 'No specific exception message given',
                'trace' => null,
                'code' => null,
                'line' => null,
                'file' => 'ResponseService.php'
            ]);
        $options = $resolver->resolve($options);

        return $this->createJsonResponse([
            'data' => $options
        ]);
    }

    /**
     * @deprecated This will be replaced by createNotFoundExceptionResponse()
     * @param (string|\Exception) $e
     * @return Response
     * @throws \Exception
     */
    public function createNotFoundException($e)
    {
        return $this->createNotFoundExceptionResponse($e);
    }

    /**
     * @param (string|\Exception) $e
     * @return Response
     * @throws \Exception
     */
    public function createNotFoundExceptionResponse($e)
    {
        return $this->createJsonResponse([
            'data' => $this->_jsonResponseOptionResolver(
                $e,
                Response::HTTP_NOT_FOUND,
                ResponseService::RESPONSE_TYPE_NOT_FOUND
            ),
            'statusCode' => Response::HTTP_NOT_FOUND
        ]);
    }

    /**
     * @param (string|\Exception) $e
     * @return Response
     * @throws \Exception
     */
    public function createAccessDeniedExceptionResponse($e)
    {
        return $this->createJsonResponse([
            'data' => $this->_jsonResponseOptionResolver(
                $e,
                Response::HTTP_FORBIDDEN,
                ResponseService::RESPONSE_TYPE_FORBIDDEN
            ),
            'statusCode' => Response::HTTP_FORBIDDEN
        ]);
    }

    /**
     * @param $e
     * @param $status
     * @param $type
     * @return array
     * @throws \Exception
     */
    private function _jsonResponseOptionResolver($e, $status, $type)
    {
        $data = [
            'status' => $status,
            'type' => $type,
            'message' => null,
            'trace' => null,
            'code' => null,
            'line' => null,
            'file' => 'ResponseService.php'
        ];

        if ($e instanceof \Exception) {
            $data = array_merge(
                $data,
                [
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'code' => $e->getCode(),
                    'line' => $e->getLine(),
                    'file' => $e->getFile()
                ]
            );
        } elseif (is_string($e)) {
            $data['message'] = $e;
        } else {
            throw new \Exception('Expecting \Exception instance or string! ' . ucfirst(gettype($e)) . ' received.');
        }

        return $data;
    }

    /**
     * @param $message
     * @param array $optionalArguments
     * @return Response
     */
    public function createSuccessJsonResponse($message, array $optionalArguments = [])
    {
        return $this->createJsonResponse([
            'data' => array_merge(
                [
                    'type' => ResponseService::RESPONSE_TYPE_SUCCESS,
                    'message' => $message
                ],
                $optionalArguments
            )
        ]);
    }

    /**
     * @param $options = [
     *      'data' (array) = [
     *          status = OK,
     *          pageSettings = []
     *          ...
     *      ]
     *      'serializer' (null|Serializer) optional
     *      'strategy' (string) optional
     *      'contentType' (string) optional
     *      'statusCode' (string) optional
     *      'pageSettings' (array) optional
     * ]
     * @return Response
     */
    public function createJsonResponse($options)
    {
        $encoders = [new JsonEncoder()];
        $normalizers = [new ObjectNormalizer()];

        $resolver = new OptionsResolver();
        $resolver->setDefined(['data', 'serializer', 'strategy', 'contentType', 'statusCode', 'pageSettings'])
            ->setRequired(['data'])
            ->setAllowedTypes('data', ['array'])
            ->setAllowedTypes('serializer', ['\Symfony\Component\Serializer\Serializer'])
            ->setAllowedTypes('strategy', ['string'])
            ->setAllowedTypes('contentType', ['string'])
            ->setAllowedTypes('statusCode', ['numeric'])
            ->setAllowedTypes('pageSettings', ['array'])
            ->setDefaults([
                'serializer' => new Serializer($normalizers, $encoders),
                'strategy' => 'json',
                'contentType' => 'application/json',
                'statusCode' => Response::HTTP_OK,
                'pageSettings' => ResponseService::PAGE_SETTINGS
            ]);
        $options = $resolver->resolve($options);

        /**
         * this handles the cases when we have pagination
         */
        if (isset($options['data']['data'])
            AND
            is_array($options['data']['data'])
            AND
            isset($options['data']['data']['totalNumberOfEntities'])
            AND
            isset($options['data']['data']['entities'])
        ) {
            $options['pageSettings']['totalNumberOfEntities'] = $options['data']['data']['totalNumberOfEntities'];
            $options['data']['data'] = $options['data']['data']['entities'];
        }

        $response = new Response();
        $response->setContent(
            $options['serializer']->serialize(
                array_merge([
                    'status' => $options['statusCode'],
                    'pageSettings' => $options['pageSettings']
                ], $options['data']),
                $options['strategy']
            )
        );
        $response->setStatusCode($options['statusCode']);
        $response->headers->set('Content-Type', $options['contentType']);
        return $response;
    }

    /**
     * https://bitbucket.org/mindlahus/instructional-manuals/src/master/symfony.serializing-doctrine-objects.md
     *
     * Example:
     * $responseService = $this->get('mindlahus.v1.response');
     * $repository = $this->getDoctrine()->getRepository('Mindlahus:SomeSuperClassName');
     * return $responseService->_getSerializedDoctrineResponse([
     *      'doctrineResponse' => $repository->findOneById($id),
     *      'groups' => [$action],
     *      'callbacks' => ['date' => StringHelper::dateFormat($dateTime, 'm-d-Y')]
     * ]);
     *
     * Example:
     * $responseService = $this->get('mindlahus.v1.response');
     * $repository = $this->getDoctrine()->getRepository('Mindlahus:SomeSuperClassName');
     * return $responseService->_getSerializedDoctrineResponse([
     *      'doctrineResponse' => $repository->findAll(),
     *      'groups' => ['aGroup', 'anotherGroup'],
     *      'callbacks' => ['date' => StringHelper::dateFormat($dateTime, 'm-d-Y')]
     * ]);
     *
     * @param array $options = [
     *      'doctrineResponse' (Doctrine Entity|array|null)
     *      'groups' (array)
     *      'callbacks' (array) optional
     *      'getPropertyContent' (boolean|null) optional
     * ]
     * @param bool $returnArray
     * @return array|string|\Exception
     */
    public function _getSerializedDoctrineResponse(array $options, $returnArray = true)
    {

        $resolver = new OptionsResolver();
        $resolver->setDefined(['doctrineResponse', 'groups', 'callbacks', 'getPropertyContent'])
            ->setRequired(['doctrineResponse', 'groups'])
            ->setAllowedTypes('doctrineResponse', ['object', 'array', 'null'])
            ->setAllowedTypes('groups', ['array'])
            ->setAllowedTypes('callbacks', ['array'])
            ->setAllowedTypes('getPropertyContent', ['null', 'array', 'bool'])
            ->setDefaults([
                'callbacks' => [],
                'getPropertyContent' => null
            ]);
        $options = $resolver->resolve($options);

        if ($options['doctrineResponse'] === null) {
            throw new NotFoundHttpException('No result found with the given entity id!');
        }

        $classMetadataFactory = new ClassMetadataFactory(new AnnotationLoader(new AnnotationReader()));
        $encoder = new JsonEncoder();
        $normalizer = new ObjectNormalizer($classMetadataFactory);
        $normalizer->setCircularReferenceHandler(function ($object) {
            return $object->getId();
        });

        if (count($options['callbacks']) > 0) {
            $normalizer->setCallbacks($options['callbacks']);
        }

        $serializer = new Serializer([$normalizer], [$encoder]);
        $data = $serializer->normalize($options['doctrineResponse'], null, ['groups' => $options['groups']]);

        /**
         * todo : try improve all this if/else/if AND if/else
         */
        if ($options['getPropertyContent'] === true AND !empty($data)) {
            $data = current($data);
        } elseif (!empty($options['getPropertyContent']) AND !empty($data)) {
            foreach ($options['getPropertyContent'] as $normalizeStrategy) {
                switch ($normalizeStrategy) {
                    case 'current':
                        $data = current($data);
                        break;
                    case 'array_values':
                        $data = array_values($data);
                        break;
                    case 'array_values_current':
                        $array = [];
                        foreach ($data as $array_value) {
                            $array[] = current($array_value);
                        }
                        $data = $array;
                        break;
                }
            }
        }

        $data = [
            'type' => ResponseService::RESPONSE_TYPE_DOCTRINE_OBJECT,
            'data' => $data
        ];
        if ($returnArray === true) {
            return [
                'serializer' => $serializer,
                'data' => $data
            ];
        } else {
            return $serializer->serialize($data, 'json');
        }
    }

}