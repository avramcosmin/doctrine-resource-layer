<?php

namespace Mindlahus\Service;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Mindlahus\Helper\EntityHelper;
use Mindlahus\Helper\ExceptionHelper;
use Mindlahus\Helper\StringHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyAccess;

class ResourceService
{

    /**
     * @var EntityManager
     */
    public $entityManager;
    /**
     * @var ContainerInterface
     */
    public $containerInterface;
    /**
     * @var \Symfony\Component\PropertyAccess\PropertyAccessor
     */
    public $accessor;
    /**
     * @var \Symfony\Component\HttpFoundation\RequestStack
     */
    protected $requestStack;
    protected $requestContent = null;

    public function __construct(RequestStack $requestStack,
                                ContainerInterface $containerInterface)
    {
        $this->requestStack = $requestStack->getCurrentRequest();
        $this->entityManager = $containerInterface->get('doctrine.orm.entity_manager');
        $this->containerInterface = $containerInterface;
        $this->accessor = PropertyAccess::createPropertyAccessor();
        $this->requestContent = json_decode($this->requestStack->getContent());
    }

    /**
     * @param $entity (object)
     * @param $propertyPath (string) string representing name of input field/entity property Ex. product
     * @param null $useValue (boolean|string|numeric)
     *              if given, this value is used instead of searching for and using an input's field value
     *              Ex. true | Some text | 23
     * @param null $property
     * @return mixed
     * @throws \Exception
     */
    public function set($entity, $propertyPath, $useValue = null, $property = null)
    {
        $this->_validate($entity);

        $this->accessor->setValue(
            $entity,
            $propertyPath,
            $this->_get($propertyPath, $useValue, $property)
        );

        return $entity;
    }

    /**
     * @param $entity
     * @param $propertyPath
     * @param null $decimalsSeparator
     * @param null $useValue
     * @param null $property
     * @return mixed
     * @throws \Exception
     */
    public function setFloat($entity, $propertyPath, $decimalsSeparator = null, $useValue = null, $property = null)
    {
        $this->_validate($entity);

        $this->accessor->setValue(
            $entity,
            $propertyPath,
            $this->_getFloat(
                $this->_get($propertyPath, $useValue, $property),
                $decimalsSeparator
            )
        );

        return $entity;
    }

    /**
     * @param $entity
     * @param $propertyPath
     * @param null $decimals
     * @param null $decimalsSeparator
     * @param null $useValue
     * @param null $property
     * @return mixed
     */
    public function setDouble(
        $entity,
        $propertyPath,
        $decimals = null,
        $decimalsSeparator = null,
        $useValue = null,
        $property = null
    )
    {
        if (!is_numeric($decimals)) {
            $decimals = 2;
        }

        $this->accessor->setValue(
            $entity,
            $propertyPath,
            $this->_getDouble(
                $this->_get($propertyPath, $useValue, $property),
                $decimals,
                $decimalsSeparator
            )
        );

        return $entity;
    }

    /**
     * @param $entity
     * @param $propertyPath
     * @param null $useValue
     * @param null $property
     * @return mixed
     * @throws \Exception
     */
    public function setInt($entity, $propertyPath, $useValue = null, $property = null)
    {
        $this->_validate($entity);

        $this->accessor->setValue(
            $entity,
            $propertyPath,
            $this->_getInt(
                $this->_get($propertyPath, $useValue, $property)
            )
        );

        return $entity;
    }

    /**
     * @param $entity
     * @param $propertyPath
     * @param null $useValue
     * @param null $property
     * @return mixed
     */
    public function setInteger($entity, $propertyPath, $useValue = null, $property = null)
    {
        return $this->setInt($entity, $propertyPath, $useValue, $property);
    }

    /**
     * When the property has a default we no worry about making sure a value is set to trigger validation
     *
     * @param $entity
     * @param $propertyPath (string) string representing name of input field/entity property Ex. product
     * @param $defaultValue
     * @param null $useValue (boolean|string|numeric)
     *              if given, this value is used instead of searching for and using an input's field value
     *              Ex. true | Some text | 23
     * @param null $property
     * @return mixed
     * @throws \Exception
     */
    public function setOrUseDefault($entity,
                                    $propertyPath,
                                    $defaultValue,
                                    $useValue = null,
                                    $property = null)
    {
        $this->_validate($entity);

        $val = $this->_get($propertyPath, $useValue, $property);
        if ($val === null) {
            $val = $defaultValue;
        }

        $this->accessor->setValue(
            $entity,
            $propertyPath,
            $val
        );

        return $entity;
    }

    /**
     * @param $entity
     * @param $propertyPath (string) string representing name of input field/entity property Ex. product
     * @param null $useValue (numeric)
     *              if given, this value is used instead of searching for and using an input's field value
     *              Ex. 23
     * @param null $property
     * @return mixed
     * @throws \Exception
     */
    public function setNumeric($entity,
                               $propertyPath,
                               $useValue = null,
                               $property = null)
    {
        $this->_validate($entity);

        $val = $this->_get($propertyPath, $useValue, $property);

        $this->accessor->setValue(
            $entity,
            $propertyPath,
            (is_numeric($val) ? $val : null)
        );

        return $entity;
    }

    /**
     * @param $entity
     * @param $propertyPath (string) string representing name of input field/entity property Ex. product
     * @param null $useValue (string)
     *              if given, this value is used instead of searching for and using an input's field value
     *              Ex. 21-09-1996
     * @param null $property
     * @return mixed
     * @throws \Exception
     */
    public function setDate($entity,
                            $propertyPath,
                            $useValue = null,
                            $property = null)
    {
        $this->_validate($entity);

        $val = $this->_get($propertyPath, $useValue, $property);
        $this->accessor->setValue(
            $entity,
            $propertyPath,
            (is_string($val) ? new \DateTime($val, new \DateTimeZone('UTC')) : null)
        );

        return $entity;
    }

    /**
     * @param $entity
     * @param $propertyPath
     * @return mixed
     */
    public function setNegation($entity, $propertyPath)
    {
        $this->_validate($entity);

        $this->accessor->setValue(
            $entity,
            $propertyPath,
            !$this->accessor->getValue($entity, $propertyPath)
        );

        return $entity;
    }

    /**
     * @param $entity
     * @param $propertyPath (string) string representing name of input field/entity property Ex. product
     * @param null $useValue (boolean)
     *              if given, this value is used instead of searching for and using an input's field value
     *              Ex. true
     * @param null $property
     * @return mixed
     * @throws \Exception
     */
    public function setBooleanIfHas($entity,
                                    $propertyPath,
                                    $useValue = null,
                                    $property = null)
    {
        $this->_validate($entity);

        $val = $this->_get($propertyPath, $useValue, $property);

        $this->accessor->setValue(
            $entity,
            $propertyPath,
            ($val !== null ? true : false)
        );

        return $entity;
    }

    /**
     * @param $entity
     * @param $propertyPath (string) string representing name of input field/entity property Ex. product
     * @param null $useValue (boolean|string|numeric)
     *              if given, this value is used instead of searching for and using an input's field value
     *              Ex. true | yes | 1
     * @param null $property
     * @return mixed
     * @throws \Exception
     */
    public function setBoolean($entity, $propertyPath, $useValue = null, $property = null)
    {
        $this->_validate($entity);

        $val = $this->_get($propertyPath, $useValue, $property);

        $this->accessor->setValue(
            $entity,
            $propertyPath,
            ($val !== null ? filter_var($val, FILTER_VALIDATE_BOOLEAN) : null)
        );

        return $entity;
    }

    /**
     * @param $entity
     * @param $options = [
     *      'propertyPath' string
     *      'size' (optional) numeric
     *      'useValue' (optional) string
     * ]
     * @return mixed
     */
    public function setMarkdown($entity, $options)
    {
        $this->_validate($entity);

        $resolver = new OptionsResolver();
        $resolver->setDefined(['propertyPath', 'size', 'useValue'])
            ->setRequired(['propertyPath'])
            ->setAllowedTypes('propertyPath', ['string'])
            ->setAllowedTypes('size', ['numeric', 'null'])
            ->setAllowedTypes('useValue', ['string', 'null', 'object'])
            ->setDefaults([
                'size' => null,
                'useValue' => null
            ]);
        $options = $resolver->resolve($options);

        $val = $this->_get($options['propertyPath'], $options['useValue']);

        if (empty($val)) {
            return $entity;
        }

        $this->accessor->setValue(
            $entity,
            $options['propertyPath'],
            $val
        );

        $propertyPath = str_replace('Markdown', 'HTML', $options['propertyPath']);
        $val = StringHelper::parsedownExtra($val);
        if (property_exists($entity, $propertyPath)) {
            $this->accessor->setValue(
                $entity,
                $propertyPath,
                $val
            );
        }

        $propertyPath = str_replace('Markdown', 'Short', $options['propertyPath']);
        if (property_exists($entity, $propertyPath)) {
            if (is_numeric($options['size'])) {
                $this->accessor->setValue(
                    $entity,
                    $propertyPath,
                    StringHelper::shortenThis($val, $options['size'])
                );
            }
        }

        return $entity;
    }

    /**
     * Example:
     * $this->resourceService->setNumberOf($entity, ['assignees']);
     *
     * @param $entity
     * @param array $properties
     * @param bool $skipCount
     * @return mixed
     * @throws \Exception
     */
    public function setNumberOf($entity, array $properties, $skipCount = true)
    {
        $this->_validate($entity);

        foreach ($properties as $property) {
            if ($skipCount === true) {
                $entity->{'setNumberOf' . ucfirst($property)}();
            } else {
                $this->accessor->setValue(
                    $entity,
                    'numberOf' . ucfirst($property),
                    count($this->accessor->getValue($entity, $property))
                );
            }
        }

        return $entity;
    }

    /**
     * Example: Many-To-One, Unidirectional
     * A user has many email addresses and we imply that two users cannot have the same email address in the same time.
     * User entity is the OWNING side while the EmailAddress entity is not aware about the user.
     *
     * @param $entity (Doctrine Entity) Ex. Company
     * @param $propertyPath (string) string representing name of input field/entity property Ex. city (all lowercase)
     * @param $repository (path to Entity Repository) Ex. MindlahusCommerceBundle:City
     * @param null $useValue (numeric|object)
     *              if given, this value is used instead of searching for and using an input's field value
     *              Ex. 23 | Instance of a Doctrine Entity
     * @param null $property
     * @return mixed
     * @throws \Exception
     */
    public function setManyToOneUnidirectional(
        $entity,
        $propertyPath,
        $repository,
        $useValue = null,
        $property = null
    )
    {
        $this->_validate($entity);

        $val = $this->_get($propertyPath, $useValue, $property);

        if (is_numeric($val)) {
            $val = $this->entityManager->getRepository($repository)
                ->findOneBy([
                    'id' => $val
                ]);
        }

        /**
         * Accept only null or instance of Entity
         */
        if ($val !== null AND !is_object($val)) {
            throw new \Exception(
                'Only null or instance of Doctrine Entity is allowed!'
            );
        }

        /**
         * check if the object in use is an instance of Entity
         */
        if ($val !== null AND (!is_object($val) OR get_class($val) === 'stdClass')) {
            throw new \Exception(
                'You passed in an object that first has to be persisted as an Entity!',
                ExceptionHelper::EXCEPTION_CODE_REQUIRES_PERSIST
            );
        }

        $this->accessor->setValue($entity, $propertyPath, $val);

        return $entity;
    }

    /**
     * UNIDIRECTIONAL, OWNING side (implied)
     *
     * @param $entity
     * @param $thisSideProperty
     * @param $repository
     * @param null $useValue
     * @param null $property
     * @param null $element
     * @return mixed
     * @throws \Throwable
     * @throws \TypeError
     */
    public function setOneToOneUnidirectional($entity,
                                              $thisSideProperty,
                                              $repository,
                                              $useValue = null,
                                              $property = null,
                                              $element = null)
    {

        $this->accessor->setValue(
            $entity,
            $thisSideProperty,
            $this->_oneToOneGetElement(
                $element,
                $thisSideProperty,
                $repository,
                $useValue,
                $property
            )
        );

        return $entity;
    }

    /**
     * BIDIRECTIONAL, INVERSE side
     *
     * @param $entity
     * @param $thisSideProperty
     * @param $otherSideProperty
     * @param $repository
     * @param null $useValue
     * @param null $property
     * @param null $element
     * @return mixed
     */
    public function inverseSideSetsOneToOneBidirectional($entity,
                                                         $thisSideProperty,
                                                         $otherSideProperty,
                                                         $repository,
                                                         $useValue = null,
                                                         $property = null,
                                                         $element = null)
    {
        /**
         * here $element is the other side the OWNING SIDE
         */
        $element = $this->_oneToOneGetElement(
            $element,
            $thisSideProperty,
            $repository,
            $useValue,
            $property
        );

        if (empty($element)) {
            return $entity;
        }

        $this->owningSideSetsOneToOneBidirectional($entity,
            $thisSideProperty,
            $otherSideProperty,
            $repository,
            $useValue,
            $property,
            $element);

        return $entity;
    }

    /**
     * BIDIRECTIONAL, OWNING side
     *
     * @param $entity
     * @param $thisSideProperty
     * @param $otherSideProperty
     * @param $repository
     * @param null $useValue
     * @param null $property
     * @param null $element
     * @return mixed
     * @throws \Throwable
     * @throws \TypeError
     */
    public function owningSideSetsOneToOneBidirectional($entity,
                                                        $thisSideProperty,
                                                        $otherSideProperty,
                                                        $repository,
                                                        $useValue = null,
                                                        $property = null,
                                                        $element = null)
    {
        /**
         * here $element is the INVERSE SIDE
         */
        $element = $this->_oneToOneGetElement(
            $element,
            $thisSideProperty,
            $repository,
            $useValue,
            $property
        );

        $this->setOneToOneUnidirectional(
            $entity,
            $thisSideProperty,
            $repository,
            $useValue,
            $property,
            $element
        );

        // todo : add check if instance of entity
        if (!empty($element)) {
            $this->accessor->setValue($element, $otherSideProperty, $entity);
        }

        return $entity;
    }

    /**
     * @param $element
     * @param $thisSideProperty
     * @param $repository
     * @param $useValue
     * @param $property
     * @return null|object
     */
    private function _oneToOneGetElement($element,
                                         $thisSideProperty,
                                         $repository,
                                         $useValue,
                                         $property)
    {
        // todo : add check if instance of entity
        if (empty($element)) {
            $id = $this->_get($thisSideProperty, $useValue, $property);
            if (!empty($id)) {
                $element = $this->getOneById($repository, $id);
            }
        }

        return $element;
    }

    /**
     * Example: INVERSE SIDE
     * addOneToMany is always called from the inverse side when bidirectional
     *
     * $this->resourceService->addOneToManyBidirectional($entity, [
     *      'entities' => [1, 4, 5] | 'cities' | 3 | ArrayCollection,
     *      'thisSideAdder' => 'addCity',
     *      'repository' => 'MindlahusBundle:City'
     *      'otherSideProperty' => 'country'
     * ])
     *
     * @param object $entity
     * @param array $options = [
     *      'entities' array|number|string|ArrayCollection
     *      'thisSideAdder' string
     *      'repository' string
     *      'otherSideProperty' string
     *      'thisSideProperty' (optional) array
     * ]
     * @return mixed
     * @throws \Exception
     */
    public function addOneToManyBidirectional($entity, $options)
    {
        $this->_validate($entity);

        $resolver = new OptionsResolver();
        $this->_addOneToManyBidirectionalConfigureOptions($resolver);
        $resolver->setDefined(array_merge($resolver->getDefinedOptions(), [
            'thisSideProperty'
        ]))
            ->setAllowedTypes('thisSideProperty', ['array'])
            ->setDefaults([
                'thisSideProperty' => []
            ]);
        $options = $resolver->resolve($options);

        $options['entities'] = $this->_resolveEntitiesOption($options['entities']);

        /**
         * persist new associations
         */
        if (empty($options['entities'])) return $entity;

        if (!$options['entities'] instanceof ArrayCollection) {
            $options['entities'] = $this->getManyById($options['repository'], $options['entities']);
        }

        foreach ($options['entities'] as $element) {
            $entity->{$options['thisSideAdder']}($element);
            $this->accessor->setValue($element, $options['otherSideProperty'], $entity);
        }

        return $entity;
    }

    /**
     * Example: INVERSE SIDE
     * addOneToManyBidirectionalAndSetNumberOf is always called from the inverse side when bidirectional
     *
     * $this->resourceService->addOneToManyBidirectional($entity, [
     *      'entities' => [1, 4, 5] | 'cities' | 3 | ArrayCollection,
     *      'thisSideProperty' => ['cities'] | ['cities', 'population'],
     *      'thisSideAdder' => 'addCity',
     *      'repository' => 'MindlahusBundle:City'
     *      'otherSideProperty' => 'country'
     * ])
     *
     * ['cities', 'population'] -> with each city the total number of the population increases.
     *
     * @param $entity
     * @param array $options = [
     *      'entities' array|number|string|ArrayCollection
     *      'thisSideProperty' array
     *      'thisSideAdder' string
     *      'repository' string
     *      'otherSideProperty' string
     * ]
     * @return mixed
     */
    public function addOneToManyBidirectionalAndSetNumberOf($entity, $options)
    {
        $this->_validate($entity);

        $resolver = new OptionsResolver();
        $this->_addOneToManyBidirectionalConfigureOptions($resolver);
        $resolver->setDefined(array_merge($resolver->getDefinedOptions(), [
            'thisSideProperty'
        ]))
            ->setRequired(array_merge($resolver->getRequiredOptions(), [
                'thisSideProperty'
            ]))
            ->setAllowedTypes('thisSideProperty', ['array']);
        $options = $resolver->resolve($options);

        $this->addOneToManyBidirectional($entity, $options);

        $this->setNumberOf($entity, $options['thisSideProperty']);

        return $entity;
    }

    /**
     * @param OptionsResolver $resolver
     * @return OptionsResolver
     */
    private function _addOneToManyBidirectionalConfigureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefined(['entities', 'thisSideAdder', 'repository', 'otherSideProperty'])
            ->setRequired(['entities', 'thisSideAdder', 'repository', 'otherSideProperty'])
            ->setAllowedTypes('entities', ['array', 'numeric', 'string', '\Doctrine\Common\Collections\ArrayCollection'])
            ->setAllowedTypes('thisSideAdder', ['string'])
            ->setAllowedTypes('repository', ['string'])
            ->setAllowedTypes('otherSideProperty', ['string']);

        return $resolver;
    }

    /**
     * Example: INVERSE SIDE
     * setOneToManyBidirectional is always called from the inverse side when bidirectional
     *
     * $this->resourceService->setOneToMany($entity, [
     *      'entities' => [1, 4, 5] | 'cities' | 3 | ArrayCollection,
     *      'thisSideProperty' => ['cities'] | ['cities', 'population'],
     *      'repository' => 'MindlahusBundle:City'
     *      'thisSideRemover' => 'removeCity'
     *      'otherSideProperty' => 'country'
     * ])
     *
     * @param object $entity
     * @param array $options = [
     *      'entities' array|number|string|ArrayCollection
     *      'thisSideProperty' array
     *      'repository' string
     *      'thisSideRemover' string
     *      'otherSideProperty' string
     * ]
     * @return mixed
     * @throws \Exception
     */
    public function setOneToManyBidirectional($entity, $options)
    {
        $this->_validate($entity);

        $resolver = new OptionsResolver();
        $this->_setOneToManyBidirectionalConfigureOptions($resolver);
        $options = $resolver->resolve($options);

        $options['entities'] = $this->_resolveEntitiesOption($options['entities']);

        /**
         * remove old associations
         */
        foreach ($this->accessor->getValue($entity, $options['thisSideProperty'][0]) as $element) {
            if ((
                    is_array($options['entities'])
                    AND
                    !in_array($element->getId(), $options['entities'])
                ) || (
                    $options['entities'] instanceof ArrayCollection
                    AND
                    !$options['entities']->contains($element)
                )
            ) {
                $entity->{$options['thisSideRemover']}($element);
                $this->accessor->setValue($element, $options['otherSideProperty'], null);
            }
        }

        /**
         * persist new associations
         */
        if (empty($options['entities'])) return $entity;

        if (!$options['entities'] instanceof ArrayCollection) {
            $options['entities'] = $this->getManyById($options['repository'], $options['entities']);
        }

        $this->accessor->setValue(
            $entity,
            $options['thisSideProperty'][0],
            $options['entities']
        );

        foreach ($options['entities'] as $element) {
            $this->accessor->setValue($element, $options['otherSideProperty'], $entity);
        }

        return $entity;
    }

    /**
     * Example: INVERSE SIDE
     * setOneToManyBidirectionalAndSetNumberOf is always called from the inverse side when bidirectional
     *
     * $this->resourceService->setOneToMany($entity, [
     *      'entities' => [1, 4, 5] | 'cities' | 3 | ArrayCollection,
     *      'thisSideProperty' => ['cities'] | ['cities', 'population'],
     *      'repository' => 'MindlahusBundle:City'
     *      'thisSideRemover' => 'removeCity'
     *      'otherSideProperty' => 'country'
     * ])
     *
     * @param $entity
     * @param array $options = [
     *      'entities' array|number|string|ArrayCollection
     *      'thisSideProperty' array
     *      'repository' string
     *      'thisSideRemover' string
     *      'otherSideProperty' string
     * ]
     * @return mixed
     */
    public function setOneToManyBidirectionalAndSetNumberOf($entity, $options)
    {
        $this->_validate($entity);

        $resolver = new OptionsResolver();
        $this->_setOneToManyBidirectionalConfigureOptions($resolver);
        $options = $resolver->resolve($options);

        $this->setOneToManyBidirectional($entity, $options);

        $this->setNumberOf($entity, $options['thisSideProperty']);

        return $entity;
    }

    /**
     * @param OptionsResolver $resolver
     * @return OptionsResolver
     */
    private function _setOneToManyBidirectionalConfigureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefined(['entities', 'thisSideProperty', 'repository', 'thisSideRemover', 'otherSideProperty'])
            ->setRequired(['entities', 'thisSideProperty', 'repository', 'thisSideRemover', 'otherSideProperty'])
            ->setAllowedTypes('entities', ['array', 'numeric', 'string', '\Doctrine\Common\Collections\ArrayCollection'])
            ->setAllowedTypes('thisSideProperty', ['array'])
            ->setAllowedTypes('repository', ['string'])
            ->setAllowedTypes('thisSideRemover', ['string'])
            ->setAllowedTypes('otherSideProperty', ['string']);

        return $resolver;
    }

    /**
     * Example: OWNING SIDE of a OneToMany
     * Country is the inverse side, City is the owning side of the relationship.
     *
     * $this->resourceService->owningSideSetsOneToManyAndNumberOf($entity, 'country', ['cities', 'population'], 'addCity', 'removeCity')
     *
     *
     * @param $entity object
     * @param $thisSideProperty string
     * @param $repository (path to Entity Repository) Ex. MindlahusCommerceBundle:City
     * @param null $useValue (numeric|object)
     *              if given, this value is used instead of searching for and using an input's field value
     *              Ex. 23 | Instance of a Doctrine Entity
     * @param null $property
     * @return mixed
     * @throws \Exception
     */
    public function owningSideSetsOneToMany(
        $entity,
        $thisSideProperty,
        $repository,
        $useValue = null,
        $property = null
    )
    {
        $this->_validate($entity);

        $this->setManyToOneUnidirectional(
            $entity,
            $thisSideProperty,
            $repository,
            $useValue,
            $property
        );

        return $entity;
    }

    /**
     * Example: OWNING SIDE of a OneToMany
     * Country is the inverse side, City is the owning side of the relationship.
     *
     * $this->resourceService->owningSideSetsOneToManyAndNumberOf($entity, 'country', ['cities', 'population'], 'addCity', 'removeCity')
     *
     *
     * @param $entity object
     * @param $thisSideProperty string
     * @param $repository
     * @param array $otherSideProperty array
     * @param $otherSideAdder string
     * @param $otherSideRemover string
     * @param null $useValue (numeric|object)
     *              if given, this value is used instead of searching for and using an input's field value
     *              Ex. 23 | Instance of a Doctrine Entity
     * @param null $property
     * @return mixed
     * @throws \Exception
     */
    public function owningSideSetsOneToManyAndNumberOf(
        $entity,
        $thisSideProperty,
        $repository,
        array $otherSideProperty,
        $otherSideAdder,
        $otherSideRemover,
        $useValue = null,
        $property = null
    )
    {
        $this->_validate($entity);

        // number or null
        $useValue = $this->_get($thisSideProperty, $useValue, $property);

        // category or null
        $oldAssociation = $this->accessor->getValue($entity, $thisSideProperty);

        if (!empty($oldAssociation) AND empty($useValue)) {// old category, new null
            // update with remove
            $oldAssociation->{$otherSideRemover}($entity);
            $this->setNumberOf($oldAssociation, $otherSideProperty);

            /**
             * info : because of a strange reference handling we have to set new association in here
             * why?
             * if the new association is null, calling the remover above will again (don't know how)
             * re-bind the association
             */
            $this->owningSideSetsOneToMany($entity, $thisSideProperty, $repository, $useValue, $property);
            return $entity;
        }

        if (empty($oldAssociation) AND !empty($useValue)) {// old null, new category

            $this->owningSideSetsOneToMany($entity, $thisSideProperty, $repository, $useValue, $property);
            // category or null
            $newAssociation = $this->accessor->getValue($entity, $thisSideProperty);

            // attach the new association
            $newAssociation->{$otherSideAdder}($entity);
            $this->setNumberOf($newAssociation, $otherSideProperty);

            return $entity;
        }

        if (empty(!$oldAssociation) AND !empty($useValue)) {// old category, new category
            // remove old
            $oldAssociation->{$otherSideRemover}($entity);
            $this->setNumberOf($oldAssociation, $otherSideProperty);

            $this->owningSideSetsOneToMany($entity, $thisSideProperty, $repository, $useValue, $property);
            // category or null
            $newAssociation = $this->accessor->getValue($entity, $thisSideProperty);

            // add new
            $newAssociation->{$otherSideAdder}($entity);
            $this->setNumberOf($newAssociation, $otherSideProperty);

            return $entity;
        }
        // old null, new null is ignored

        return $entity;
    }

    /**
     * Example: One-To-Many, Unidirectional
     *
     * UNIDIRECTIONAL
     * $this->resourceService->addManyToMany($entity, [
     *      'entities' => [1, 4, 5] | 'cities' | 3 | ArrayCollection,
     *      'thisSideAdder' => 'addCity',
     *      'repository' => 'Mindlahus:City'
     * ])
     *
     * @param $entity
     * @param object $entity
     * @param array $options = [
     *      'entities' array|number|string|ArrayCollection
     *      'thisSideAdder' string
     *      'repository' string
     * ]
     * @return mixed
     */
    public function addOneToManyUnidirectional($entity, $options)
    {
        return $this->addManyToMany($entity, $options);
    }

    /**
     * Example: One-To-Many, Unidirectional
     *
     * UNIDIRECTIONAL
     * $this->resourceService->addManyToMany($entity, [
     *      'entities' => [1, 4, 5] | 'cities' | 3 | ArrayCollection,
     *      'thisSideAdder' => 'addCity',
     *      'repository' => 'Mindlahus:City'
     *      'thisSideProperty' => 'cities'
     * ])
     *
     * @param $entity
     * @param object $entity
     * @param array $options = [
     *      'entities' array|number|string|ArrayCollection
     *      'thisSideAdder' string
     *      'repository' string
     *      'thisSideProperty' array
     * ]
     * @return mixed
     */
    public function addOneToManyUnidirectionalAndSetNumberOf($entity, $options)
    {
        return $this->addManyToManyAndSetNumberOf($entity, $options);
    }

    /**
     * Example: One-To-MAny, Unidirectional
     *
     * $this->resourceService->addManyToMany($entity, [
     *      'entities' => [1, 4, 5] | 'features' | 3 | ArrayCollection,
     *      'thisSideProperty' => 'cities',
     *      'repository' => 'Mindlahus:City'
     *      'thisSideRemover' => 'removeProduct'
     * ])
     *
     * @param object $entity
     * @param array $options = [
     *      'entities' array|number|string|ArrayCollection
     *      'thisSideProperty' string
     *      'repository' string
     *      'thisSideRemover' string
     * ]
     * @return mixed
     * @throws \Exception
     */
    public function setOneToManyUnidirectional($entity, $options)
    {
        return $this->setManyToMany($entity, $options);
    }

    /**
     * Example: One-To-MAny, Unidirectional
     *
     * $this->resourceService->addManyToMany($entity, [
     *      'entities' => [1, 4, 5] | 'features' | 3 | ArrayCollection,
     *      'thisSideProperty' => 'cities',
     *      'repository' => 'Mindlahus:City'
     *      'thisSideRemover' => 'removeProduct'
     * ])
     *
     * @param object $entity
     * @param array $options = [
     *      'entities' array|number|string|ArrayCollection
     *      'thisSideProperty' string
     *      'repository' string
     *      'thisSideRemover' string
     * ]
     * @return mixed
     * @throws \Exception
     */
    public function setOneToManyUnidirectionalAndSetNumberOf($entity, $options)
    {
        return $this->setManyToManyAndSetNumberOf($entity, $options);
    }

    /**
     * Example: Adding from the INVERSE side
     * $entity = Features (the inverse side)
     * A Feature (the inverse side) has Products. We add some more products to the existing relationships
     *
     * $this->resourceService->addManyToMany($entity, [
     *      'entities' => [1, 4, 5] | 'products' | 3 | ArrayCollection,
     *      'thisSideAdder' => 'addProduct',
     *      'repository' => 'Mindlahus:Product'
     *      'otherSideAdder' => 'addFeature'
     * ])
     *
     * Example: Adding from the OWNING side
     * A Product (the owning side) has Features. We add some more features to the existing relationships
     *
     * $this->resourceService->addManyToMany($entity, [
     *      'entities' => [1, 4, 5] | 'features' | 3 | ArrayCollection,
     *      'thisSideAdder' => 'addFeature',
     *      'repository' => 'Mindlahus:Feature'
     * ])
     *
     * @param object $entity
     * @param array $options = [
     *      'entities' array|number|string|ArrayCollection
     *      'thisSideAdder' string
     *      'repository' string
     *      'otherSideAdder' (optional) string
     *      'thisSideProperties' (optional) array
     *      'otherSideProperties' (optional) array
     *      'thisSideProperty' (optional) array
     *      'otherSideProperty' (optional) array
     * ]
     * @return mixed
     * @throws \Exception
     */
    public function addManyToMany($entity, array $options)
    {
        $this->_validate($entity);

        $resolver = new OptionsResolver();
        $this->_addManyToManyConfigureOptions($resolver);
        $resolver->setDefined(array_merge($resolver->getDefinedOptions(), [
            'thisSideProperty',
            'otherSideProperty'
        ]))
            ->setAllowedTypes('thisSideProperty', ['array'])
            ->setAllowedTypes('otherSideProperty', ['array'])
            ->setDefaults([
                'thisSideProperty' => [],
                'otherSideProperty' => []
            ]);
        $options = $resolver->resolve($options);

        $options['entities'] = $this->_resolveEntitiesOption($options['entities']);

        if (empty($options['entities'])) return $entity;

        if (!$options['entities'] instanceof ArrayCollection) {
            $options['entities'] = $this->getManyById($options['repository'], $options['entities']);
        }

        foreach ($options['entities'] as $element) {
            $entity->{$options['thisSideAdder']}($element);

            /**
             * When the inverse side adds many-to-many we call the owning side and add there as well
             */
            if ($options['otherSideAdder']) {
                $element->{$options['otherSideAdder']}($entity);

                /**
                 * this runs when the method is called from inside addManyToManyAndSetNumberOf
                 * or an otherSideProperty is given
                 */
                if (!empty($options['otherSideProperty'])) {
                    $this->setNumberOf($element, $options['otherSideProperty']);
                }
            }

            /**
             * Example:
             * When you add an assignee maybe you automatically want to update the list of all assignees taking into account
             * also the assignees provided by the teams
             * $options = [
             *      ...
             *      'otherSideProperties' => ['bubbleAssignees']
             * ]
             */
            foreach ($options['otherSideProperties'] as $propertyPath) {
                $element->{'set' . ucfirst($propertyPath)}();
            }
        }

        /**
         * Example:
         * When you add an assignee maybe you automatically want to update the list of all assignees taking into account
         * also the assignees provided by the teams
         * $options = [
         *      ...
         *      'thisSideProperties' => ['bubbleAssignees']
         * ]
         */
        foreach ($options['thisSideProperties'] as $propertyPath) {
            $entity->{'set' . ucfirst($propertyPath)}();
        }

        return $entity;
    }

    /**
     * Example: Adding from the INVERSE side
     * $entity = Features (the inverse side)
     * A Feature (the inverse side) has Products. We add some more products to the existing relationships
     *
     * $this->resourceService->addManyToMany($entity, [
     *      'entities' => [1, 4, 5] | 'products' | 3 | ArrayCollection,
     *      'thisSideAdder' => 'addProduct',
     *      'repository' => 'Mindlahus:Product'
     *      'otherSideAdder' => 'addFeature'
     *      'thisSideProperty' => ['products']
     *      'otherSideProperty' => ['features']
     * ])
     *
     * Example: Adding from the OWNING side
     * A Product (the owning side) has Features. We add some more features to the existing relationships
     *
     * BIDIRECTIONAL
     * $this->resourceService->addManyToMany($entity, [
     *      'entities' => [1, 4, 5] | 'features' | 3 | ArrayCollection,
     *      'thisSideAdder' => 'addFeature',
     *      'repository' => 'Mindlahus:Feature'
     *      'thisSideProperty' => ['products']
     *      'otherSideProperty' => ['features']
     * ])
     *
     * UNIDIRECTIONAL
     * $this->resourceService->addManyToMany($entity, [
     *      'entities' => [1, 4, 5] | 'features' | 3 | ArrayCollection,
     *      'thisSideAdder' => 'addFeature',
     *      'repository' => 'Mindlahus:Feature'
     *      'thisSideProperty' => ['products']
     * ])
     *
     * @param $entity
     * @param object $entity
     * @param array $options = [
     *      'entities' array|numeric|string|ArrayCollection
     *      'thisSideAdder' string
     *      'repository' string
     *      'thisSideProperty' array
     *      'otherSideProperty' array
     *      'otherSideAdder' (optional) string
     *      'thisSideProperties' (optional) array
     *      'otherSideProperties' (optional) array
     * ]
     * @return mixed
     */
    public function addManyToManyAndSetNumberOf($entity, $options)
    {
        $this->_validate($entity);

        $resolver = new OptionsResolver();
        $this->_addManyToManyConfigureOptions($resolver);
        $resolver->setDefined(array_merge($resolver->getDefinedOptions(), [
            'thisSideProperty',
            'otherSideProperty'
        ]))
            ->setRequired(array_merge($resolver->getRequiredOptions(), ['thisSideProperty']))
            ->setAllowedTypes('thisSideProperty', ['array'])
            ->setAllowedTypes('otherSideProperty', ['array'])
            ->setDefaults([
                'otherSideProperty' => []
            ]);
        $options = $resolver->resolve($options);

        $this->addManyToMany($entity, $options);

        $this->setNumberOf($entity, $options['thisSideProperty']);

        return $entity;
    }

    /**
     * @param OptionsResolver $resolver
     * @return OptionsResolver
     */
    private function _addManyToManyConfigureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefined([
            'entities',
            'thisSideAdder',
            'repository',
            'otherSideAdder',
            'thisSideProperties',
            'otherSideProperties'
        ])
            ->setRequired(['entities', 'thisSideAdder', 'repository'])
            ->setAllowedTypes('entities', [
                'array',
                'numeric',
                'string',
                '\Doctrine\Common\Collections\ArrayCollection'
            ])
            ->setAllowedTypes('thisSideAdder', ['string'])
            ->setAllowedTypes('repository', ['string'])
            ->setAllowedTypes('otherSideAdder', ['string', 'null'])
            ->setAllowedTypes('thisSideProperties', ['array'])
            ->setAllowedTypes('otherSideProperties', ['array'])
            ->setDefaults([
                'thisSideProperties' => [],
                'otherSideProperties' => [],
                'otherSideAdder' => null
            ]);

        return $resolver;
    }

    /**
     * Example: Setting a collection from the INVERSE side
     * $entity = Features (the inverse side)
     * A Feature (the inverse side) has Products. We set the associations given a collection.
     *
     * BIDIRECTIONAL, INVERSE side
     *
     * $this->resourceService->setManyToMany($entity, [
     *      'entities' => [1, 4, 5] | 'products' | 3| ArrayCollection,
     *      'thisSideProperty' => ['products'],
     *      'repository' => 'Mindlahus:Product'
     *      'thisSideRemover' => 'removeProduct'
     *      'otherSideRemover' => 'removeFeature'
     *      'otherSideAdder' => 'addFeature'
     *      'otherSideProperty' => ['features']
     * ])
     *
     * Example: Setting a collection from the OWNING side
     * A Product (the owning side) has Features. We set the associations given a collection.
     *
     * BIDIRECTIONAL, OWNING side
     * UNIDIRECTIONAL, OWNING side (implied)
     *
     * $this->resourceService->setManyToMany($entity, [
     *      'entities' => [1, 4, 5] | 'features' | 3| ArrayCollection,
     *      'thisSideProperty' => ['features'],
     *      'repository' => 'Mindlahus:Feature'
     *      'thisSideRemover' => 'removeProduct'
     * ])
     *
     * @param object $entity
     * @param array $options = [
     *      'entities' array|number|string|ArrayCollection
     *      'thisSideProperty' array
     *      'repository' string
     *      'thisSideRemover' string
     *      'otherSideRemover' (optional) string
     *      'otherSideAdder' (optional) string
     *      'otherSideProperty' (optional) array
     *      'thisSideProperties' (optional) array
     *      'otherSideProperties' (optional) array
     * ]
     * @return mixed
     * @throws \Exception
     */
    public function setManyToMany($entity, array $options)
    {
        $this->_validate($entity);

        $resolver = new OptionsResolver();
        $this->_setManyToManyConfigureOptions($resolver);
        $options = $resolver->resolve($options);

        $options['entities'] = $this->_resolveEntitiesOption($options['entities']);

        $thisSideAssociations = $this->accessor->getValue(
            $entity,
            $options['thisSideProperty'][0]
        );

        /**
         * remove old associations
         */
        if (!empty($thisSideAssociations)) {
            foreach ($thisSideAssociations as $element) {
                if (!in_array($element->getId(), $options['entities'])) {
                    $entity->{$options['thisSideRemover']}($element);

                    if ($options['otherSideRemover']) {
                        $element->{$options['otherSideRemover']}($entity);
                    }
                }
            }
        }

        if (empty($options['entities'])) return $entity;

        if (!$options['entities'] instanceof ArrayCollection) {
            $options['entities'] = $this->getManyById($options['repository'], $options['entities']);
        }

        foreach ($options['entities'] as $element) {

            /**
             * if the association already exists,
             * skip
             */
            if ($thisSideAssociations->contains($element)) {
                continue;
            }

            /**
             * When the inverse side adds many-to-many we call the owning side and add there as well
             */
            if ($options['otherSideAdder']) {
                $element->{$options['otherSideAdder']}($entity);
            }

            /**
             * this runs when the method is called from inside addManyToManyAndSetNumberOf
             * or an otherSideProperty is given
             */
            if (!empty($options['otherSideProperty'])) {
                $this->setNumberOf($element, $options['otherSideProperty']);
            }

            /**
             * todo : this can be redundant. change the name or add better documentation.
             *
             * Example:
             * When you add an assignee maybe you automatically want to update the list of all assignees taking into account
             * also the assignees provided by the teams
             * $options = [
             *      ...
             *      'otherSideProperties' => ['bubbleAssignees']
             * ]
             */
            foreach ($options['otherSideProperties'] as $propertyPath) {
                $element->{'set' . ucfirst($propertyPath)}();
            }
        }
        /**
         * todo : this can be redundant. change the name or add better documentation.
         *
         * Example:
         * When you add an assignee maybe you automatically want to update the list of all assignees taking into account
         * also the assignees provided by the teams
         * $options = [
         *      ...
         *      'thisSideProperties' => ['bubbleAssignees']
         * ]
         */
        foreach ($options['thisSideProperties'] as $propertyPath) {
            $entity->{'set' . ucfirst($propertyPath)}();
        }

        /**
         * keep this last
         * otherwise will mess with $thisSideAssociations->contains($element)
         * this is necessary when setting ManyToMany from the owning side
         */
        $this->accessor->setValue(
            $entity,
            $options['thisSideProperty'][0],
            $options['entities']
        );


        return $entity;
    }


    /**
     * Example: Setting a collection from the INVERSE side
     * $entity = Features (the inverse side)
     * A Feature (the inverse side) has Products. We set the associations given a collection.
     *
     * $this->resourceService->setManyToMany($entity, [
     *      'entities' => [1, 4, 5] | 'products' | 3| ArrayCollection,
     *      'thisSideProperty' => 'products',
     *      'repository' => 'Mindlahus:Product',
     *      'thisSideRemover' => 'removeProduct',
     *      'otherSideRemover' => 'removeFeature',
     *      'otherSideAdder' => 'addFeature',
     *      'otherSideProperty' => 'features'
     * ])
     *
     * Example: Setting a collection from the OWNING side
     * A Product (the owning side) has Features. We set the associations given a collection.
     *
     * $this->resourceService->addManyToMany($entity, [
     *      'entities' => [1, 4, 5] | 'features' | 3| ArrayCollection,
     *      'thisSideProperty' => 'features',
     *      'repository' => 'Mindlahus:Feature',
     *      'thisSideRemover' => 'removeProduct',
     *      'otherSideRemover' => 'removeFeature'
     * ])
     *
     * @param object $entity
     * @param array $options = [
     *      'entities' array|number|string|ArrayCollection
     *      'thisSideProperty' string
     *      'repository' string
     *      'thisSideRemover' string
     *      'otherSideRemover' string
     *      'otherSideAdder' (optional) string
     *      'otherSideProperty' (optional) string
     *      'thisSideProperties' (optional) array
     *      'otherSideProperties' (optional) array
     * ]
     * @return mixed
     * @throws \Exception
     */
    public function setManyToManyAndSetNumberOf($entity, $options)
    {
        $this->_validate($entity);

        $resolver = new OptionsResolver();
        $this->_setManyToManyConfigureOptions($resolver);
        $options = $resolver->resolve($options);

        $this->setManyToMany($entity, $options);

        $this->setNumberOf($entity, $options['thisSideProperty']);

        return $entity;
    }

    /**
     * @param OptionsResolver $resolver
     * @return OptionsResolver
     */
    private function _setManyToManyConfigureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefined([
            'entities',
            'thisSideProperty',
            'repository',
            'thisSideRemover',
            'otherSideRemover',
            'otherSideAdder',
            'otherSideProperty',
            'thisSideProperties',
            'otherSideProperties'
        ])
            ->setRequired(['entities', 'thisSideProperty', 'repository', 'thisSideRemover', 'otherSideRemover'])
            ->setAllowedTypes('entities', [
                'array',
                'numeric',
                'string',
                '\Doctrine\Common\Collections\ArrayCollection'
            ])
            ->setAllowedTypes('thisSideProperty', ['array'])
            ->setAllowedTypes('repository', ['string'])
            ->setAllowedTypes('thisSideRemover', ['string'])
            ->setAllowedTypes('otherSideRemover', ['string', 'null'])
            ->setAllowedTypes('otherSideAdder', ['string', 'null'])
            ->setAllowedTypes('otherSideProperty', ['array'])
            ->setAllowedTypes('thisSideProperties', ['array'])
            ->setAllowedTypes('otherSideProperties', ['array'])
            ->setDefaults([
                'otherSideRemover' => null,
                'otherSideAdder' => null,
                'otherSideProperty' => [],
                'thisSideProperties' => [],
                'otherSideProperties' => []
            ]);

        return $resolver;
    }

    /**
     * Many-To-One
     * One-To-Many, Unidirectional
     * One-TO-Many, Self-referencing
     * ManyToMany, Unidirectional
     * $this->resourceService->removeAssociations($entity, [
     *      'entities'
     *      'repository'
     *      'thisSideRemover'
     * ])
     *
     * One-To-Many, Bidirectional (from the INVERSE side)
     * $this->resourceService->removeAssociations($entity, [
     *      'entities'
     *      'repository'
     *      'thisSideRemover'
     *      'otherSideProperty'
     * ])
     *
     * ManyToMany, Bidirectional, INVERSE, OWNING
     * $this->resourceService->removeAssociations($entity, [
     *      'entities'
     *      'repository'
     *      'thisSideRemover'
     *      'otherSideRemover'
     *      'thisSideProperty'
     *      'otherSideProperty'
     * ])
     *
     * @param $entity
     * @param $options = [
     *      'entities' array|number|string|ArrayCollection
     *      'repository' string
     *      'thisSideRemover' string
     *      'otherSideRemover' (optional) string
     *      'thisSideProperty' (optional) array
     *      'otherSideProperty' (optional) array
     * ]
     *
     * @return mixed
     */
    public function removeAssociations($entity, $options)
    {
        $resolver = new OptionsResolver();
        $this->_removeAssociationsConfigureOptions($resolver);
        $options = $resolver->resolve($options);

        $options['entities'] = $this->_resolveEntitiesOption($options['entities']);

        if (empty($options['entities'])) return $entity;

        if (!$options['entities'] instanceof ArrayCollection) {
            $options['entities'] = $this->getManyById($options['repository'], $options['entities']);
        }

        foreach ($options['entities'] as $element) {
            $entity->{$options['thisSideRemover']}($element);

            if ($options['otherSideRemover']) {
                $element->{$options['otherSideRemover']}($entity);

                if (!empty($options['otherSideProperty'])) {
                    $this->setNumberOf($element, $options['otherSideProperty']);
                }
            } elseif (isset($options['otherSideProperty'][0])) {
                $this->accessor->setValue(
                    $element,
                    $options['otherSideProperty'][0],
                    null
                );
            }
        }

        return true;
    }

    /**
     * Many-To-One
     * One-To-Many, Unidirectional
     * One-TO-Many, Self-referencing
     * ManyToMany, Unidirectional
     * $this->resourceService->removeAssociations($entity, [
     *      'entities'
     *      'repository'
     *      'thisSideRemover'
     *      'thisSideProperty'
     * ])
     *
     * One-To-Many, Bidirectional (from the INVERSE side)
     * $this->resourceService->removeAssociations($entity, [
     *      'entities'
     *      'repository'
     *      'thisSideRemover'
     *      'otherSideProperty'
     *      'thisSideProperty'
     * ])
     *
     * ManyToMany, Bidirectional, INVERSE, OWNING
     * $this->resourceService->removeAssociations($entity, [
     *      'entities'
     *      'repository'
     *      'thisSideRemover'
     *      'otherSideRemover'
     * ])
     *
     * @param $entity
     * @param $options = [
     *      'entities' array|number|string|ArrayCollection
     *      'repository' string
     *      'thisSideRemover' string
     *      'otherSideRemover' (optional) string
     *      'thisSideProperty' (optional) array
     *      'otherSideProperty' (optional) array
     * ]
     *
     * @return mixed
     */
    public function removeAssociationsAndSetNumberOf($entity, $options)
    {
        $this->_validate($entity);

        $resolver = new OptionsResolver();
        $this->_removeAssociationsConfigureOptions($resolver);
        $options = $resolver->resolve($options);

        $this->removeAssociations($entity, $options);

        $this->setNumberOf($entity, $options['thisSideProperty']);

        return $entity;

    }

    /**
     * @param OptionsResolver $resolver
     * @return OptionsResolver
     */
    private function _removeAssociationsConfigureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefined([
            'entities',
            'repository',
            'thisSideRemover',
            'otherSideProperty',
            'otherSideRemover',
            'thisSideProperty'
        ])
            ->setRequired(['entities', 'repository', 'thisSideRemover'])
            ->setAllowedTypes('entities', [
                'array',
                'numeric',
                'string',
                '\Doctrine\Common\Collections\ArrayCollection'
            ])
            ->setAllowedTypes('repository', ['string'])
            ->setAllowedTypes('thisSideRemover', ['string'])
            ->setAllowedTypes('otherSideProperty', ['array'])
            ->setAllowedTypes('otherSideRemover', ['string', 'null'])
            ->setAllowedTypes('thisSideProperty', ['array'])
            ->setDefaults([
                'otherSideProperty' => [],
                'otherSideRemover' => null
            ]);

        return $resolver;
    }

    /**
     * @param $entity
     * @param $options
     * @return mixed
     * @throws \Exception
     */
    public function setEntityAssignees($entity, $options)
    {
        return $this->setOneToManyBidirectional($entity, array_merge([
            'collection' => EntityHelper::getMembersOfGroupsAndAssignees(
                $this->accessor->getValue($entity, 'assignees')->toArray(),
                $this->accessor->getValue($entity, 'teams')->toArray(),
                'getMembers'
            )->collection
        ], $options));
    }

    /**
     * Example: $options = [
     *      'propertyPath' => 'strategyAssignees',
     *      'adder' => 'addStrategyAssignee',
     *      'entityAssigneeClass' => 'StrategyAssignee',
     *      'setter' => 'setStrategy'
     * ]
     *
     * @param $entity
     * @param $options = [
     *      'propertyPath' string,
     *      'adder' (string),
     *      'entityAssigneeClass' string
     *      'setter' string
     * ]
     */
    public function setEntityAssigneesWithPivotTable($entity, $options)
    {

        $resolver = new OptionsResolver();
        $resolver->setDefined(['propertyPath', 'adder', 'entityAssigneeClass', 'setter', 'groupHasPivotTable'])
            ->setRequired(['propertyPath', 'adder', 'entityAssigneeClass', 'setter'])
            ->setAllowedTypes('propertyPath', ['string'])
            ->setAllowedTypes('adder', ['string'])
            ->setAllowedTypes('entityAssigneeClass', ['string'])
            ->setAllowedTypes('setter', ['string'])
            ->setAllowedTypes('groupHasPivotTable', ['boolean'])
            ->setDefaults([
                'groupHasPivotTable' => null
            ]);
        $options = $resolver->resolve($options);
        /**
         * get collection of users
         * @var ArrayCollection $assignees
         */
        $assignees = EntityHelper::getMembersOfGroupsAndAssignees(
            $entity->getIndividuallyAllocatedAssignees()->toArray(),
            $entity->getTeams()->toArray(),
            'getTeamMembers',
            $options['groupHasPivotTable'])->collection;

        /**
         * get collection of the last persisted/flushed entity assignees
         * EntityAssignee[] $entityAssignees
         */
        $entityAssignees = $entity->{"get" . ucfirst($options['propertyPath'])}();
        foreach ($entityAssignees as $entityAssignee) {
            /** if the user already exists as an association, do nothing */
            if ($assignees->contains($entityAssignee->getUser())) {
                $assignees->removeElement($entityAssignee->getUser());
            } else {
                /**
                 * if the user no longer exists in the current list of associations,
                 * remove the association and the entry in the table
                 */
                $this->entityManager->remove($entityAssignee);
            }
        }

        /**
         * persist all new associations
         */
        foreach ($assignees as $assignee) {
            $entityAssignee = new $options['entityAssigneeClass']();
            $entityAssignee->setUser($assignee);
            $entityAssignee->{$options['setter']}($entity);
            $this->entityManager->persist($entityAssignee);
            $entity->{$options['adder']}($entityAssignee);
        }

        if (method_exists($entity, 'setNumberOf' . ucfirst($options['propertyPath']))) {
            $entity->{'setNumberOf' . ucfirst($options['propertyPath'])}();
        }
    }

    /**
     * Example:
     * $this->resourceService->setNumberOfEntityAssignees($entity, 'bubbleAssignees');
     *
     * Note: bubbleAssignees means Team Members + Assignees (assignees all together)
     *
     * @param object $entity
     * @param string $propertyPath
     * @return mixed
     * @throws \Exception
     */
    public function setNumberOfEntityAssignees($entity, $propertyPath)
    {
        $this->_validate($entity);

        $this->accessor->setValue(
            $entity,
            'numberOf' . ucfirst($propertyPath),
            EntityHelper::getMembersOfGroupsAndAssignees(
                $this->accessor->getValue($entity, 'assignees')->toArray(),
                $this->accessor->getValue($entity, 'teams')->toArray(),
                'getTeams'
            )->total
        );

        return $entity;
    }

    /**
     * @param $entityRepository
     * @param null $id
     * @return null|object
     */
    public function getOneById($entityRepository, $id = null)
    {
        return $this->entityManager->getRepository($entityRepository)->findOneBy([
            'id' => (is_numeric($id) ? $id : $this->_get('id'))
        ]);
    }

    /**
     * @param $repository
     * @param array $entities
     * @return array
     */
    public function getManyById($repository, array $entities)
    {
        return $this->getManyBy($repository, $entities);
    }

    /**
     * @param $repository
     * @param array $entities
     * @param string $col
     * @return array
     */
    public function getManyBy($repository, array $entities, $col = 'id')
    {
        return $this->entityManager->getRepository($repository)->findBy([
            $col => $entities
        ]);
    }

    /**
     * @param $entityRepository
     * @param array $searchTermsArray
     * @return null|object
     */
    public function getOneBy($entityRepository, array $searchTermsArray)
    {
        /**
         * todo : implement the $this->_get()
         */
        return $this->entityManager->getRepository($entityRepository)->findOneBy($searchTermsArray);
    }

    /**
     * @param $propertyPath
     * @return bool
     */
    public function hasInput($propertyPath)
    {
        if ($this->requestStack->getContentType() === 'json') {
            if (property_exists($this->requestContent, $propertyPath)
                &&
                !empty($this->requestContent->{$propertyPath})
            ) {
                return true;
            }
        } else {
            if ($this->requestStack->request->has($propertyPath)
                &&
                !empty($this->requestStack->request->get($propertyPath))
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return null|\Symfony\Component\HttpFoundation\Request
     */
    public function getRequestStack()
    {
        return $this->requestStack;
    }

    /**
     * Lets keep thinks strict
     * If no value is given, we set the column to null
     * This helps avoid inserting empty strings into a column that accepts null values
     *
     * @param $propertyPath
     * @param null $useValue
     * @param null $property
     * @return mixed|null
     */
    public function _get($propertyPath, $useValue = null, $property = null)
    {
        if ($useValue !== null) {
            if (is_object($useValue) AND get_class($useValue) === 'stdClass' AND (is_string($property) OR $property === null)) {

                if ($property === null) {
                    $property = $propertyPath;
                }

                if (!property_exists($useValue, $property)) {
                    return null;
                }

                $useValue = $useValue->{$property};
                if (is_string(($useValue)) AND !is_numeric($useValue)) {
                    $useValue = trim($useValue);
                    return (empty($useValue) ? null : $useValue);
                }

                return $useValue;
            }

            if (is_object($useValue) AND is_array($property)) {

                foreach ($property as $p) {
                    if (!property_exists($useValue, $p)) {
                        return null;
                    }
                    $useValue = $useValue->{$p};
                }

                if (is_string(($useValue)) AND !is_numeric($useValue)) {
                    $useValue = trim($useValue);
                    return (empty($useValue) ? null : $useValue);
                }

                return $useValue;
            }

            if (is_string(($useValue)) AND !is_numeric($useValue)) {
                $useValue = trim($useValue);
                return (empty($useValue) ? null : $useValue);
            }

            return $useValue;
        }

        if ($this->requestStack->getContentType() === 'json'
        ) {

            if (empty($this->requestContent) OR !property_exists($this->requestContent, $propertyPath)) {
                return null;
            }

            $value = $this->requestContent->{$propertyPath};

            if (is_object($value) AND is_string($property)) {

                if (!property_exists($value, $property)) {
                    return null;
                }

                $value = $value->{$property};
                if (is_string(($value)) AND !is_numeric($value)) {
                    $value = trim($value);
                    return (empty($value) ? null : $value);
                }

                return $value;
            }

            if (is_object($value) AND is_array($property)) {

                foreach ($property as $p) {
                    if (!property_exists($value, $p)) {
                        return null;
                    }
                    $value = $value->{$p};
                }

                if (is_string(($value)) AND !is_numeric($value)) {
                    $value = trim($value);
                    return (empty($value) ? null : $value);
                }

                return $value;
            }

            if (is_string(($value)) AND !is_numeric($value)) {
                $value = trim($value);
                return (empty($value) ? null : $value);
            }

            return $value;
        }

        if ($this->requestStack->request->has($propertyPath)) {
            $value = $this->requestStack->request->get($propertyPath);
            if (is_string(($value)) AND !is_numeric($value)) {
                $value = trim($value);
                return (empty($value) ? null : $value);
            }

            return $value;
        }

        return null;
    }

    public function _getFile(string $propertyPath)
    {
        return $this->requestStack->files->get($propertyPath);
    }

    /**
     * @param $val
     * @param null $decimalsSeparator
     * @return mixed
     */
    public function _getFloat($val, $decimalsSeparator = null)
    {
        if (!is_numeric($val)) {
            return null;
        }

        if (!in_array($decimalsSeparator, [',', '.'])) {
            $decimalsSeparator = '.';
        }

        /**
         * Example of alternative regex:
         * (?!\\A[\\-0-9])\\K[^0-9\\{$decimalsSeparator}]
         */
        return floatval(preg_replace("/(?!^[\\-\\d])[^\\d\\{$decimalsSeparator}]/", '', $val));
    }

    /**
     * @param $val
     * @param int $decimals
     * @param null $decimalSeparator
     * @return null|string
     */
    public function _getDouble($val, $decimals = 2, $decimalSeparator = null)
    {
        $val = $this->_getFloat($val, $decimalSeparator);

        if (!is_numeric($val)) {
            return null;
        }

        return floatval(
            number_format($val, $decimals, $decimalSeparator, '')
        );
    }

    /**
     * @param $val
     * @return int|null
     */
    public function _getInt($val)
    {
        if (!is_numeric($val)) {
            return null;
        }

        return intval($val);
    }

    /**
     * @param $entity
     * @throws \Exception
     */
    private function _validate($entity)
    {
        if (!is_object($entity)) {
            throw new \Exception(
                'Expecting instance of a Doctrine Entity! ' . ucfirst(gettype($entity)) . ' received.'
            );
        }
    }

    /**
     * @param $optionEntities
     * @return array|null
     */
    private function _resolveEntitiesOption($optionEntities)
    {
        switch (true) {
            case is_numeric($optionEntities):
                $optionEntities = [$optionEntities];
                break;
            case is_string($optionEntities):
                $val = $this->_get($optionEntities);
                $optionEntities = (is_array($val) ? $val : (is_numeric($val) ? [$val] : []));
                break;
            case is_array($optionEntities):
                break;
            case ($optionEntities instanceof ArrayCollection) === true:
                break;
            default:
                $optionEntities = [];
                break;
        }

        return $optionEntities;
    }
}