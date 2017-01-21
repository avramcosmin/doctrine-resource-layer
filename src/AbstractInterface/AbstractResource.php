<?php

namespace Mindlahus\AbstractInterface;

use Doctrine\Common\Collections\ArrayCollection;
use Mindlahus\Helper\ExceptionHelper;
use Mindlahus\Traits\EntityResource\AbstractEntityResourceTrait;
use Symfony\Component\OptionsResolver\OptionsResolver;

abstract class AbstractResource
{

    use AbstractEntityResourceTrait;

    /**
     * @param $entity
     * @param $options
     * @throws \Exception
     */
    public function owningSideCreatesAndSetsOneToMany($entity, $options)
    {

        $resolver = new OptionsResolver();
        $resolver->setDefined([
            'propertyPath',
            'repository',
            'resourceClass',
            'entityClass',
            'findBy',//used when type was saved but id was not refreshed on the client and a new save attempt is made
            'resourceMethod',
            'useValue',//no restriction on the type
            'property',
            'arguments',//used to pass arguments to Resource's method on create
            'forceCreate'
        ])
            ->setRequired([
                'propertyPath',
                'repository',
                'resourceClass',
                'entityClass',
                'findBy'
            ])
            ->setAllowedTypes('propertyPath', ['string'])
            ->setAllowedTypes('repository', ['string'])
            ->setAllowedTypes('resourceClass', ['string'])
            ->setAllowedTypes('entityClass', ['string'])
            ->setAllowedTypes('findBy', ['string'])
            ->setAllowedTypes('resourceMethod', ['string'])
            ->setAllowedTypes('property', ['null', 'string', 'array'])
            ->setAllowedTypes('arguments', ['array'])
            ->setAllowedTypes('forceCreate', ['null', 'boolean'])
            ->setDefaults([
                'resourceMethod' => '_selfFromObject',
                'useValue' => null,
                'property' => null,
                'arguments' => [],
                'forceCreate' => null
            ]);
        $options = $resolver->resolve($options);

        try {
            if ($options['forceCreate']) {
                throw new \Exception(
                    'Force Create is enable and because of this we try move forward',
                    ExceptionHelper::EXCEPTION_CODE_REQUIRES_PERSIST
                );
            }
            $this->resourceService->owningSideSetsOneToMany(
                $entity,
                $options['propertyPath'],
                $options['repository']
            );
        } catch (\Exception $e) {
            if ($e->getCode() === ExceptionHelper::EXCEPTION_CODE_REQUIRES_PERSIST) {
                /**
                 * if the association already exists, just use it
                 * otherwise, create id
                 */
                if ($options['forceCreate'] OR !$associationEntity = $this->resourceService
                        ->entityManager
                        ->getRepository($options['repository'])
                        ->findOneBy([
                            $options['findBy'] => $this->resourceService->_get(
                                $options['propertyPath'],
                                $this->resourceService->_get(
                                    $options['propertyPath'],
                                    $options['useValue'],
                                    $options['property']
                                ),
                                [$options['findBy']]
                            )
                        ])
                ) {
                    $associationEntity = new $options['entityClass']();
                    //persist
                    call_user_func_array([
                        new $options['resourceClass']($this->resourceService),
                        $options['resourceMethod']
                    ], array_merge([$associationEntity], $options['arguments']));
                }

                $this->resourceService->owningSideSetsOneToMany(
                    $entity,
                    $options['propertyPath'],
                    $options['repository'],
                    $associationEntity
                );
            } else {
                ExceptionHelper::PropagateException($e);
            }
        }
    }

    /**
     * Example:
     * $options = [
     *      'propertyPath' => 'companyTelephoneNumbers',
     *      'pivotTableResourceClass' => 'CompanyTelephoneNumberResource',
     *      'pivotTableEntityClass' => 'CompanyTelephoneNumber',
     *      'pivotTableEntity' => 'Mindlahus:CompanyTelephoneNumber'
     * ]
     *
     * $options = [
     *      'propertyPath' => null,
     *      'pivotTableResourceClass' => '\Mindlahus\EntityResource\TelephoneNumberTypeResource',
     *      'pivotTableEntityClass' => '\Mindlahus\Entity\TelephoneNumberType',
     *      'pivotTableEntity' => 'Mindlahus:TelephoneNumberType',
     *      'property' => 'telephoneNumberType',
     *      'findBy' => 'name',
     *      'useValue' => $input
     * ]
     *
     * @param $entity
     * @param $options = [
     *      'propertyPath',
     *      'pivotTableResourceClass',
     *      'pivotTableEntityClass',
     *      'pivotTableEntity',
     *      'resourceMethod',
     *      'property',
     *      'findBy',
     *      'useValue'
     * ]
     * @return ArrayCollection|null|object
     * @throws \Exception
     */
    public function handlePivotTableAssociations($entity, $options)
    {

        $resolver = new OptionsResolver();
        $resolver->setDefined([
            'propertyPath',
            'pivotTableResourceClass',
            'pivotTableEntityClass',
            'pivotTableEntity',
            'resourceMethod',
            'property',
            'findBy',//used when type was saved but id was not refreshed on the client and a new save attempt is made
            'useValue',//no restriction on the type
            'returnsArrayCollection'
        ])
            ->setRequired([
                'propertyPath',
                'pivotTableResourceClass',
                'pivotTableEntityClass',
                'pivotTableEntity'
            ])
            ->setAllowedTypes('propertyPath', ['string', 'null'])
            ->setAllowedTypes('pivotTableResourceClass', ['string'])
            ->setAllowedTypes('pivotTableEntityClass', ['string'])
            ->setAllowedTypes('pivotTableEntity', ['string'])
            ->setAllowedTypes('resourceMethod', ['string'])
            ->setAllowedTypes('property', ['null', 'string', 'array'])
            ->setAllowedTypes('findBy', ['string', 'null'])
            ->setAllowedTypes('returnsArrayCollection', ['boolean'])
            ->setDefaults([
                'resourceMethod' => '_selfFromObject',
                'useValue' => null,
                'property' => null,
                'findBy' => null,
                'returnsArrayCollection' => true
            ]);
        $options = $resolver->resolve($options);

        $entities = new ArrayCollection();
        $input = $this->resourceService->_get($options['propertyPath'], $options['useValue'], $options['property']);

        if ($input) {
            if (!is_array($input)) {
                /**
                 * expects {id, name}, {name} or 1 (number)
                 */
                if (!is_object($input) AND !is_numeric($input)) {
                    throw new \Exception(
                        'Your input should be either an array or an object'
                    );
                }
                $input = [$input];
            }

            foreach ($input as $object) {

                if (!is_object($object) AND !is_numeric($object)) {
                    throw new \Exception(
                        'Your input should be an array of objects. One of the elements is ' . gettype(($object))
                    );
                }

                $pivotEntity = null;
                $pivotResource = new $options['pivotTableResourceClass']($this->resourceService);
                if (is_numeric($object) || (is_object($object) AND property_exists($object, 'id') AND is_numeric($object->id))) {
                    $pivotEntity = $this->resourceService->getOneById(
                        $options['pivotTableEntity'],
                        is_numeric($object) ? $object : $object->id
                    );
                } else {
                    if ($options['findBy']) {
                        /**
                         * if we want to set a value
                         * supposedly, nulls are allowed
                         * if we gave a value
                         */
                        if (property_exists($object, $options['findBy'])) {
                            $pivotEntity = $this->resourceService
                                ->entityManager
                                ->getRepository($options['pivotTableEntity'])
                                ->findOneBy([
                                    $options['findBy'] => $object->{$options['findBy']}
                                ]);
                        } elseif (!$options['returnsArrayCollection']) {
                            /**
                             * if no value given
                             */
                            return null;
                        } else {
                            /**
                             * if an array collection
                             */
                            continue;
                        }
                    }

                    if (!$pivotEntity) {
                        $pivotEntity = new $options['pivotTableEntityClass']();
                    }
                }

                /**
                 * this is expected to automatically be flushed by EntityManager via cascade
                 */
                if ($pivotEntity) {

                    if (!method_exists($pivotResource, $options['resourceMethod'])) {
                        throw new \Exception(
                            $options['resourceMethod'] . ' is not a method of resource class ' . get_class($pivotResource)
                        );
                    }

                    $pivotResource->{$options['resourceMethod']}(
                        $pivotEntity,
                        $object,
                        $entity
                    );

                    if (!$options['returnsArrayCollection']) {
                        return $pivotEntity;
                    }

                    $entities->add($pivotEntity);
                }
            }
        }

        return $options['returnsArrayCollection'] ? $entities : null;
    }

    /**
     * @param $input
     * @param $pivotTableResourceClass
     * @param $pivotTableEntityClass
     * @param $pivotTableEntity
     * @param string $resourceMethod
     * @return null|object
     * @throws \Exception
     */
    public function handleOptionalAssociation(
        $input,
        $pivotTableResourceClass,
        $pivotTableEntityClass,
        $pivotTableEntity,
        $resourceMethod = '_selfFromObject'
    )
    {
        if (is_object($input)) {
            $pivotResource = new $pivotTableResourceClass($this->resourceService);
            if (property_exists($input, 'id') AND is_numeric($input->id)) {
                $pivotEntity = $this->resourceService->getOneById(
                    $pivotTableEntity,
                    $input->id
                );
            } else {
                $pivotEntity = new $pivotTableEntityClass();
            }
            $pivotResource->{$resourceMethod}(
                $pivotEntity,
                $input
            );

            $validator = $this->resourceService->containerInterface->get('validator');
            $errors = $validator->validate($pivotEntity);
            if (count($errors) > 0) {
                try {
                    $message = $this->resourceService
                        ->containerInterface
                        ->get('mindlahus.v1.response')
                        ->_getSerializedValidationErrors($errors, 'serialized');
                } catch (\Exception $e) {
                    throw new \Exception($e->getMessage());
                }

                throw new \Exception(
                    $message,
                    ExceptionHelper::EXCEPTION_CODE_VALIDATION_ERROR
                );
            }

            return $pivotEntity;
        }

        return null;
    }
}