<?php

namespace Mindlahus\DoctrineResourceLayer\AbstractInterface;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ObjectManager;
use Mindlahus\SymfonyAssets\Helper\GlobalHelper;
use Mindlahus\SymfonyAssets\Helper\StringHelper;
use Mindlahus\SymfonyAssets\Helper\ThrowableHelper;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PropertyAccess\PropertyAccess;

abstract class ResourceAbstract
{
    /**
     * @var Request
     */
    private $request;
    /**
     * @var ObjectManager
     */
    private $entityManager;
    /**
     * @var Logger $logger
     */
    private $logger;
    /**
     * @var PropertyAccess
     */
    private $accessor;
    /**
     * @var ContainerInterface
     */
    private $container;
    private $requestContent;

    /**
     * todo : check that the logger actually works
     *
     * IMPORTANT!   If you should use an instance of the RequestStack,
     *              return the Request by calling $request->getCurrentRequest()
     *
     * ResourceAbstract constructor.
     * @param Request $request
     * @param ObjectManager $entityManager
     * @param Logger $logger
     * @param null $requestContent
     */
    public function __construct(
        Request $request,
        ObjectManager $entityManager,
        Logger $logger,
        $requestContent = null
    )
    {
        $this->request = $request;
        $this->entityManager = $entityManager;
        $this->logger = $logger;
        $this->accessor = PropertyAccess::createPropertyAccessor();
        $this->requestContent = $requestContent ?? $this->_getRequestContent();
    }

    /**
     * @param ContainerInterface $container
     */
    public function setContainer(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @return ContainerInterface
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * $options = [
     *  ...used by $this->getFromJSON()
     *  propertyPath    optional    string
     *  useValue        optional    mixed
     *  forceReturn     optional    boolean
     * ]
     *
     * @param $entity
     * @param string $propertyPath
     * @param array $options
     * @return mixed
     */
    public function set($entity, string $propertyPath, array $options = [])
    {
        $this->_validate($entity);

        $this->accessor->setValue(
            $entity,
            $propertyPath,
            $this->getFromJSON($propertyPath, $options)
        );

        return $entity;
    }

    /**
     * @param $entity
     * @param null $instanceOf
     * @return mixed
     * @throws \Throwable
     */
    protected function _validate($entity, $instanceOf = null)
    {
        if (
            ($instanceOf !== null && !$entity instanceof $instanceOf)
            ||
            !is_object($entity)
            ||
            $entity instanceof \stdClass
        ) {
            throw new \Error(
                'Expecting instance of an entity class. '
                . strtoupper(gettype($entity))
                . ' given.'
            );
        }

        return $entity;
    }

    /**
     * @param $entity
     * @param $propertyPath
     * @return mixed|null
     */
    protected function _getValue($entity, $propertyPath)
    {
        if (!$this->accessor->isReadable($entity, $propertyPath)) {
            return null;
        }

        return $this->accessor->getValue($entity, $propertyPath);
    }

    /**
     * http://symfony.com/doc/current/components/property_access.html
     *
     * $options = [
     *  propertyPath    optional    string      Deep reading from object|array
     *  useValue        optional    mixed       Use given value in place of $this->requestContent
     *  forceReturn     optional    boolean     Force return of $options['useValue'] without deep reading in case if object|array
     * ]
     *
     * Please use $options['propertyPath'] to deep read from an object|array
     *
     * @param string $propertyPath
     * @param array $options
     * @return mixed
     */
    public function getFromJSON(string $propertyPath, array $options = [])
    {

        if (isset($options['propertyPath']) && is_string($options['propertyPath'])) {
            $propertyPath = $options['propertyPath'];
        }

        if (array_key_exists('useValue', $options)) {

            if ($options['forceReturn'] === true) {
                return $options['useValue'];
            }

            if (!is_array($options['useValue']) && !is_object($options['useValue'])) {
                return null;
            }

            return $this->_getValue($options['useValue'], $propertyPath);
        }

        return $this->_getValue($this->requestContent, $propertyPath);
    }

    /**
     * $options = [
     *  entity  optional    Doctrine Entity
     *  findBy  optional    string              Defaults to the column name `id`
     *
     *  ...used by ThrowableHelper::NotInstanceOf
     *  instanceOf      required   string   Path to class (class name), class instance, \stdClass() instance.
     *
     *  ...used by $this->getFromJSON()
     *  propertyPath    optional    string
     *  useValue        optional    mixed
     *  forceReturn     optional    boolean
     * ]
     *
     * @param string $propertyPath
     * @param string $repository
     * @param array $options
     * @return mixed
     */
    public function getOneBy(string $propertyPath, string $repository, array $options = [])
    {
        $options = array_merge([
            'findBy' => 'id'
        ], $options);

        if (!array_key_exists('entity', $options)) {
            $options['useValue'] = $this->entityManager
                ->getRepository($repository)
                ->findOneBy([
                    $options['findBy'] => $this->getFromJSON($propertyPath, $options)
                ]);
        } else {
            $options['useValue'] = $options['entity'];
        }

        if (!is_null($options['useValue']) && !GlobalHelper::isInstanceOf(
                $options['useValue'],
                $options['instanceOf'])
        ) {
            ThrowableHelper::NotInstanceOf($options['useValue'], $options['instanceOf']);
        }

        return $options['useValue'];
    }

    /**
     * @param string $repository
     * @param array $entities
     * @param string $col
     * @return array
     */
    public function getManyBy(string $repository, array $entities, $col = 'id')
    {
        return $this->entityManager
            ->getRepository($repository)
            ->findBy([
                $col => $this->_filterEntities($entities)
            ]);
    }

    /**
     * Filters an array of entities and returns an array of id's
     * This will receive either integers or objects that should represent an instance of the same Entity.
     *
     * @param $entities
     * @return array
     */
    protected function _filterEntities($entities)
    {
        $filteredEntities = [];

        foreach ($entities as $entity) {
            if (filter_var($entity, FILTER_VALIDATE_INT)) {
                $filteredEntities[] = $entity;
                continue;
            }

            if ($this->accessor->isReadable($entity, 'id')) {
                $filteredEntities[] = $this->accessor->getValue($entity, 'id');
            }
        }

        return $filteredEntities;
    }

    /**
     * $options = [
     *  ...used by $this->getFromJSON()
     *  propertyPath    optional    string
     *  useValue        optional    mixed
     *  forceReturn     optional    boolean
     *
     *  ...used by $this->getFloat()
     *  isNullAllowed  optional    boolean
     * ]
     *
     * @param $entity
     * @param string $propertyPath
     * @param array $options
     * @return mixed
     */
    public function setFloat($entity, string $propertyPath, array $options = [])
    {
        $this->_validate($entity);

        $this->accessor->setValue(
            $entity,
            $propertyPath,
            $this->getFloat($this->getFromJSON($propertyPath, $options), $options['isNullAllowed'] ?? true)
        );

        return $entity;
    }

    /**
     * @param $val
     * @param bool $isNullAllowed
     * @return mixed
     * @throws \Throwable
     */
    public function getFloat($val, $isNullAllowed = true)
    {
        if (is_null($val) && $isNullAllowed === true) {
            return $val;
        }

        $valType = strtoupper(gettype($val));

        $val = $this->isFloat($val);

        if (!$val) {
            $this->logger->error('Not float value when trying to get float.');
            throw new \Error('Expecting integer value. ' . $valType . ' given.');
        }

        return $val;
    }

    /**
     * @param $val
     * @return mixed
     */
    public function isFloat($val)
    {
        return StringHelper::isFloat($val);
    }

    /**
     * $options = [
     *  ...used by $this->getFromJSON()
     *  propertyPath    optional    string
     *  useValue        optional    mixed
     *  forceReturn     optional    boolean
     *
     *  ...used by $this->getFloat()
     *  isNullAllowed  optional    boolean
     * ]
     *
     * @param $entity
     * @param string $propertyPath
     * @param array $options
     * @return mixed
     */
    public function setDouble($entity, string $propertyPath, array $options = [])
    {
        $this->_validate($entity);

        $this->accessor->setValue(
            $entity,
            $propertyPath,
            $this->getDouble($this->getFromJSON($propertyPath, $options), $options['isNullAllowed'] ?? true)
        );

        return $entity;
    }

    /**
     * @param $val
     * @param bool $isNullAllowed
     * @return mixed
     */
    public function getDouble($val, $isNullAllowed = true)
    {
        return $this->getWithDecimals($val, 2, $isNullAllowed);
    }

    /**
     * @param $val
     * @param $decimals
     * @param bool $isNullAllowed
     * @return mixed
     * @throws \Throwable
     */
    public function getWithDecimals($val, $decimals, $isNullAllowed = true)
    {

        if (!is_numeric($val)) {
            $this->logger->error('Not numeric value when trying to get with decimal.');
            throw new \Error('Expecting numeric value. ' . strtoupper(gettype($val)) . ' given.');
        }

        return $this->getFloat(number_format($val, $decimals), $isNullAllowed);
    }

    /**
     * $options = [
     *  ...used by $this->getFromJSON()
     *  propertyPath    optional    string
     *  useValue        optional    mixed
     *  forceReturn     optional    boolean
     *
     *  ...used by $this->getInt()
     *  isNullAllowed  optional    boolean
     * ]
     *
     * @param $entity
     * @param string $propertyPath
     * @param array $options
     * @return mixed
     */
    public function setInt($entity, string $propertyPath, array $options = [])
    {
        $this->_validate($entity);

        $this->accessor->setValue(
            $entity,
            $propertyPath,
            $this->getInt($this->getFromJSON($propertyPath, $options), $options['isNullAllowed'] ?? true)
        );

        return $entity;
    }

    /**
     * Avoid using FILTER_SANITIZE_NUMBER_INT
     * The problem with this is that will transform a float into an integer with error
     * Ex. 122.45 will become 12245 (this is dangerous)
     *
     * @param $val
     * @param bool $isNullAllowed
     * @return mixed
     * @throws \Throwable
     */
    public function getInt($val, $isNullAllowed = true)
    {
        if (is_null($val) && $isNullAllowed === true) {
            return $val;
        }

        $valType = strtoupper(gettype($val));

        $val = $this->isInt($val);

        if (!$val) {
            $this->logger->error('Not integer value when trying to get integer.');
            throw new \Error('Expecting integer value. ' . $valType . ' given.');
        }

        return $val;
    }

    /**
     * @param $val
     * @return mixed
     */
    public function isInt($val)
    {
        return StringHelper::isInt($val);
    }

    /**
     * $options = [
     *  ...used by $this->getFromJSON()
     *  propertyPath    optional    string
     *  useValue        optional    mixed
     *  forceReturn     optional    boolean
     * ]
     *
     * @param $entity
     * @param string $propertyPath
     * @param $defaultValue
     * @param array $options
     * @return mixed
     */
    public function setOrUseDefault($entity, string $propertyPath, $defaultValue, array $options = [])
    {
        $this->_validate($entity);

        $this->accessor->setValue(
            $entity,
            $propertyPath,
            $this->getFromJSON($propertyPath, $options) ?? $defaultValue
        );

        return $entity;
    }

    /**
     * $options = [
     *  ...used by $this->getFromJSON()
     *  propertyPath    optional    string
     *  useValue        optional    mixed
     *  forceReturn     optional    boolean
     *
     *  isNullAllowed  optional    boolean
     * ]
     *
     * @param $entity
     * @param string $propertyPath
     * @param array $options
     * @return mixed
     * @throws \Throwable
     */
    public function setNumeric($entity, string $propertyPath, array $options = [])
    {
        $this->_validate($entity);

        $val = $this->getFromJSON($propertyPath, $options);

        /**
         * !is_numeric() fixes the empty(0) === true
         */
        if (empty($val) && !is_numeric($val)) {
            $val = null;
        }

        if (!is_numeric($val) && (($options['isNullAllowed'] ?? true) && !is_null($val))) {
            $this->logger->error('Not numeric value when trying to set numeric.');
            throw new \Error('Expecting numeric value. ' . strtoupper(gettype($val)) . ' given.');
        }

        $this->accessor->setValue(
            $entity,
            $propertyPath,
            $val
        );

        return $entity;
    }

    /**
     * $options = [
     *  ...used by $this->getFromJSON()
     *  propertyPath    optional    string
     *  useValue        optional    mixed
     *  forceReturn     optional    boolean
     *
     *  ...used by $this->getInt()
     *  isNullAllowed  optional    boolean
     * ]
     *
     * @param $entity
     * @param string $propertyPath
     * @param array $options
     * @return mixed
     * @throws \Throwable
     */
    public function setDate($entity, string $propertyPath, array $options = [])
    {
        $this->_validate($entity);

        $this->accessor->setValue(
            $entity,
            $propertyPath,
            $this->getDate($this->getFromJSON($propertyPath, $options), $options['isNullAllowed'] ?? true)
        );

        return $entity;
    }

    /**
     * @param $val
     * @param bool $isNullAllowed
     * @return \DateTime
     * @throws \Throwable
     */
    public function getDate($val, $isNullAllowed = true)
    {
        if (is_null($val) && $isNullAllowed === true) {
            return $val;
        }

        $val = $this->isDateTime($val);

        if (!$val) {
            $this->logger->error('Not \DateTime instance when trying to get date.');
            throw new \Error('Expecting \DateTime instance. ' . strtoupper(gettype($val)) . ' given.');
        }

        return $val;
    }

    /**
     * @param $val
     * @return bool|\DateTime
     */
    public function isDateTime($val)
    {
        return StringHelper::isDateTime($val);
    }

    /**
     * This gets the current value of the propertyPath and sets its negation.
     * The value should be a boolean.
     *
     * $options = [
     *  ...used by $this->getFromJSON()
     *  propertyPath    optional    string
     *  useValue        optional    mixed
     *  forceReturn     optional    boolean
     * ]
     *
     * @param $entity
     * @param string $propertyPath
     * @return mixed
     * @throws \Throwable
     */
    public function setNegation($entity, string $propertyPath)
    {
        $this->_validate($entity);

        $val = $this->getAccessor()->getValue($entity, $propertyPath);

        if (!is_bool($val) && !is_null($val)) {
            $this->logger->error('Negation can only be used on boolean type properties.');
            throw new \Error('Negation can only be used on boolean type properties.');
        }

        $this->accessor->setValue(
            $entity,
            $propertyPath,
            !$val
        );

        return $entity;
    }

    /**
     * $options = [
     *  stdClass                required    \stdClass
     *  stdClassPropertyPath    required    string
     *
     *  ...used by $this->getFromJSON()
     *  propertyPath            optional    string
     *  useValue                optional    mixed
     *  forceReturn             optional    boolean
     *
     *  isNullAllowed           optional    boolean
     * ]
     *
     * @param $entity
     * @param string $propertyPath
     * @param array $options
     * @return mixed
     */
    public function setBoolIfStdClassHas($entity, string $propertyPath, array $options = [])
    {
        $this->_validate($entity);

        $this->accessor->setValue(
            $entity,
            $propertyPath,
            property_exists($options['stdClass'], $options['stdClassPropertyPath']) ?? null
        );

        return $entity;
    }

    /**
     * This allows for null's in case both not true and not yes.
     *
     * $options = [
     *  ...used by $this->getFromJSON()
     *  propertyPath    optional    string
     *  useValue        optional    mixed
     *  forceReturn     optional    boolean
     *
     *  isNullAllowed   boolean     boolean
     * ]
     *
     * @param $entity
     * @param string $propertyPath
     * @param array $options
     * @return mixed
     */
    public function setBool($entity, string $propertyPath, array $options = [])
    {
        $this->_validate($entity);

        $args = [
            $this->getFromJSON($propertyPath, $options),
            FILTER_VALIDATE_BOOLEAN
        ];

        if (!isset($options['isNullAllowed']) || $options['isNullAllowed'] !== true) {
            $args[] = FILTER_NULL_ON_FAILURE;
        }

        $this->accessor->setValue(
            $entity,
            $propertyPath,
            call_user_func_array('filter_var', $args)
        );

        return $entity;
    }

    /**
     * $options = [
     *  ...used by $this->getFromJSON()
     *  propertyPath    optional    string
     *  useValue        optional    mixed
     *  forceReturn     optional    boolean
     * ]
     *
     * @param $entity
     * @param string $propertyPath
     * @param array $options
     * @return mixed
     */
    public function setMarkdown($entity, string $propertyPath, array $options)
    {
        // this will set propertyPathMarkdown, propertyPathHTML & propertyPathShort
        $this->setMarkdownRaw($entity, $propertyPath, $options);
        $this->setMarkdownHTML(
            $entity,
            str_replace('Markdown', 'HTML', $propertyPath),
            array_merge($options, [
                'propertyPath' => str_replace('Markdown', 'HTML', $options['propertyPath'])
            ])
        );
        $this->setMarkdownShort(
            $entity,
            str_replace('Markdown', 'Short', $propertyPath),
            array_merge($options, [
                'propertyPath' => str_replace('Markdown', 'Short', $options['propertyPath'])
            ])
        );

        return $entity;
    }

    /**
     * $options = [
     *  ...used by $this->getFromJSON()
     *  propertyPath    optional    string
     *  useValue        optional    mixed
     *  forceReturn     optional    boolean
     * ]
     *
     * @param $entity
     * @param string $propertyPath
     * @param array $options
     * @return mixed
     */
    public function setMarkdownRaw($entity, string $propertyPath, array $options)
    {
        $this->_validate($entity);

        $this->accessor->setValue(
            $entity,
            $propertyPath,
            $this->getFromJSON($propertyPath, $options)
        );

        return $entity;
    }

    /**
     * $options = [
     *  ...used by $this->getFromJSON()
     *  propertyPath    optional    string
     *  useValue        optional    mixed
     *  forceReturn     optional    boolean
     * ]
     *
     * @param $entity
     * @param string $propertyPath
     * @param array $options
     * @return mixed
     */
    public function setMarkdownHTML($entity, string $propertyPath, array $options)
    {
        $this->_validate($entity);

        $val = $this->getFromJSON($propertyPath, $options);

        if (property_exists($entity, $propertyPath)) {
            $this->accessor->setValue(
                $entity,
                $propertyPath,
                StringHelper::parsedownExtra($val)
            );
        }

        return $entity;
    }

    /**
     * $options = [
     *  ...used by $this->getFromJSON()
     *  propertyPath    optional    string
     *  useValue        optional    mixed
     *  forceReturn     optional    boolean
     * ]
     *
     * @param $entity
     * @param string $propertyPath
     * @param array $options
     * @return mixed
     */
    public function setMarkdownShort($entity, string $propertyPath, array $options)
    {
        $this->_validate($entity);

        $val = $this->getFromJSON($propertyPath, $options);

        $this->accessor->setValue(
            $entity,
            $propertyPath,
            StringHelper::shortenThis($val, $options['size'] ?? null)
        );

        return $entity;
    }

    /**
     * http://docs.doctrine-project.org/projects/doctrine-orm/en/latest/reference/association-mapping.html#many-to-one-unidirectional
     *
     * Owning Side
     *
     * $options = [
     *  ...used by ThrowableHelper::NotInstanceOf
     *  instanceOf      required   string   Path to class (class name), class instance, \stdClass() instance.
     *
     *  ...used by $this->getFromJSON() inside $this->getOneBy()
     *  propertyPath    optional    string
     *  useValue        optional    mixed
     *  forceReturn     optional    boolean
     *
     *  ...Used by $this->getOneBy() to returning the provided entity
     *  entity          optional    Doctrine Entity
     *  findBy          optional    string              Defaults to the column name `id`
     * ]
     *
     * Please be advised that you should only allow instances of Doctrine Entities.
     *
     * $options['useValue'], $repository & $thisSideProperty refer to the Other Entity.
     *
     * Ex. City & Country
     * City is the owning side (this side). Many cities to one country.
     * Country is the inverse side (other side).
     * $options['useValue'], $repository & $thisSideProperty refer to Country.
     *
     * @param $thisSideEntity
     * @param string $thisSideProperty Reference to the other side
     * @param string $repository Path to the other's side repository
     * @param array $options
     * @return mixed
     * @throws \Throwable
     */
    public function setManyToOneUnidirectional($thisSideEntity,
                                               string $thisSideProperty,
                                               string $repository,
                                               array $options = [])
    {
        return $this->setOneToOneUnidirectional($thisSideEntity, $thisSideProperty, $repository, $options);
    }

    /**
     * http://docs.doctrine-project.org/projects/doctrine-orm/en/latest/reference/association-mapping.html#one-to-one-unidirectional
     *
     * Owning Side
     *
     * $options = [
     *  ...used by ThrowableHelper::NotInstanceOf
     *  instanceOf      required   string   Path to class (class name), class instance, \stdClass() instance.
     *
     *  ...used by $this->getFromJSON() inside $this->getOneBy()
     *  propertyPath    optional    string
     *  useValue        optional    mixed
     *  forceReturn     optional    boolean
     *
     *  ...Used by $this->getOneBy() to returning the provided entity
     *  entity          optional    Doctrine Entity
     *  findBy          optional    string              Defaults to the column name `id`
     * ]
     *
     * Please be advised that you should only allow instances of Doctrine Entities.
     *
     * $options['useValue'], $repository & $thisSideProperty refer to the Other Entity.
     *
     * Ex. User & Cart
     * A user can only have one cart.
     * If Cart is the owning side, $options['useValue'], $repository & $thisSideProperty refer to Cart.
     *
     * There is NO Inverse Side
     *
     * @param $thisSideEntity
     * @param string $thisSideProperty Reference to the other side
     * @param string $repository Path to the other's side repository
     * @param array $options
     * @return mixed
     */
    public function setOneToOneUnidirectional($thisSideEntity,
                                              string $thisSideProperty,
                                              string $repository,
                                              array $options = [])
    {
        $this->_validate($thisSideEntity);

        $this->accessor->setValue(
            $thisSideEntity,
            $thisSideProperty,
            $this->getOneBy($thisSideProperty, $repository, $options)
        );

        return $thisSideEntity;
    }

    /**
     * http://docs.doctrine-project.org/projects/doctrine-orm/en/latest/reference/association-mapping.html#one-to-one-bidirectional
     * http://docs.doctrine-project.org/projects/doctrine-orm/en/latest/reference/unitofwork-associations.html
     *
     * $options = [
     *  ...used by ThrowableHelper::NotInstanceOf
     *  instanceOf      required   string   Path to class (class name), class instance, \stdClass() instance.
     *
     *  ...used by $this->getFromJSON() inside $this->getOneBy()
     *  propertyPath    optional    string
     *  useValue        optional    mixed
     *  forceReturn     optional    boolean
     *
     *  ...Used by $this->getOneBy() to returning the provided entity
     *  entity          optional    Doctrine Entity
     *  findBy          optional    string              Defaults to the column name `id`
     * ]
     *
     * Please be advised that you should only allow instances of Doctrine Entities.
     *
     * The owning side of a OneToOne association is the entity with the table containing the foreign key.
     *
     * The inverse side has to use the mappedBy attribute.
     * mappedBy = $thisSideProperty.
     * mappedBy is a property of the Entity which is the owning side of the relationship.
     * $thisSideProperty is a property of the Other Entity
     *
     * The owning side has to use the inversedBy attribute.
     * inversedBy = $otherSideProperty.
     * inversedBy is a property of the Entity which is the inverse side of the relationship.
     * $otherSideProperty is a property of This Entity
     *
     * $options['useValue'], $repository & $thisSideProperty refer to the Other Entity (The Owner)
     * $otherSideProperty refers to This Entity (The Inverse)
     *
     * Inverse Side
     *
     * Doctrine will only check the owning side of an association for changes.
     * Changes made only to the inverse side of an association are ignored.
     *
     * @param $thisSideEntity
     * @param string $thisSideProperty
     * @param string $repository
     * @param string $otherSideProperty
     * @param array $options
     * @return mixed
     */
    public function inverseSideSetsOneToOneBidirectional($thisSideEntity,
                                                         string $thisSideProperty,
                                                         string $repository,
                                                         string $otherSideProperty,
                                                         array $options = [])
    {
        $this->_validate($thisSideEntity);

        /**
         * here $otherSideEntity is the Owning Side Entity
         */
        $otherSideEntity = $this->getOneBy(
            $thisSideProperty,
            $repository,
            $options
        );
        $this->_validate($otherSideEntity);

        /**
         * If the entity allows the association to be null and there is an old association
         * then, set this to null. Later the owner will become the `new` Other Side Entity.
         *
         * In case we try to set from both directions in the same time, they mutually exclude themselves
         */
        $oldOtherSideEntity = $this->accessor->getValue($thisSideEntity, $thisSideProperty);
        if ($oldOtherSideEntity && $oldOtherSideEntity != $otherSideEntity) {
            $this->accessor->setValue($oldOtherSideEntity, $otherSideProperty, null);
        }

        /**
         * Let's make the OLD inverse side of the owning side aware about the changes we made in here
         *
         * In case we try to set from both directions in the same time, they mutually exclude themselves
         */
        $oldInverseAssociation = $this->accessor->getValue($otherSideEntity, $otherSideProperty);
        if ($oldInverseAssociation && $oldInverseAssociation != $thisSideEntity) {
            $this->accessor->setValue($oldInverseAssociation, $thisSideProperty, null);
        }

        $this->accessor->setValue($otherSideEntity, $otherSideProperty, $thisSideEntity);

        /**
         * Let's make this side aware about the changes we made.
         */
        $this->accessor->setValue($thisSideEntity, $thisSideProperty, $otherSideEntity);

        return $thisSideEntity;
    }

    /**
     * @param $thisSideEntity
     * @param string $thisSideProperty
     * @param string $repository
     * @param string $otherSideProperty
     * @param array $options
     * @return mixed
     */
    public function inverseSideSetsOneToOneBidirectionalOrNull($thisSideEntity,
                                                               string $thisSideProperty,
                                                               string $repository,
                                                               string $otherSideProperty,
                                                               array $options = [])
    {
        $this->_validate($thisSideEntity);

        /**
         * here $otherSideEntity is the Owning Side Entity
         */
        $otherSideEntity = $this->getOneBy(
            $thisSideProperty,
            $repository,
            $options
        );

        /**
         * This is the case when the inverse becomes an orphan
         */
        if (!$otherSideEntity) {
            $oldOtherSideEntity = $this->accessor->getValue($thisSideEntity, $thisSideProperty);
            if ($oldOtherSideEntity) {
                $this->accessor->setValue($oldOtherSideEntity, $otherSideProperty, null);
            }

            $this->accessor->setValue($thisSideEntity, $thisSideProperty, null);

            return $thisSideEntity;
        }

        $options['entity'] = $options['entity'] ?? $otherSideEntity;
        return $this->inverseSideSetsOneToOneBidirectional(
            $thisSideEntity,
            $thisSideProperty,
            $repository,
            $otherSideProperty,
            $options
        );
    }

    /**
     * http://docs.doctrine-project.org/projects/doctrine-orm/en/latest/reference/association-mapping.html#one-to-one-bidirectional
     *
     * $options = [
     *  ...used by ThrowableHelper::NotInstanceOf
     *  instanceOf      required   string   Path to class (class name), class instance, \stdClass() instance.
     *
     *  ...used by $this->getFromJSON() inside $this->getOneBy()
     *  propertyPath    optional    string
     *  useValue        optional    mixed
     *  forceReturn     optional    boolean
     *
     *  ...Used by $this->getOneBy() to returning the provided entity
     *  entity          optional    Doctrine Entity
     *  findBy          optional    string              Defaults to the column name `id`
     * ]
     *
     * Please be advised that you should only allow instances of Doctrine Entities
     *
     * Owning Side
     *
     * Because this is the Owning Side, Doctrine automatically manages any change.
     * This is the simples to handle operation. Because of this, there is not too much to document.
     *
     * $options['useValue'], $repository & $thisSideProperty refer to the Other Entity (The Owner)
     *
     * @param $thisSideEntity
     * @param string $thisSideProperty
     * @param string $repository
     * @param string $otherSideProperty
     * @param array $options
     * @return mixed
     */
    public function owningSideSetsOneToOneBidirectional($thisSideEntity,
                                                        string $thisSideProperty,
                                                        string $repository,
                                                        string $otherSideProperty,
                                                        array $options = [])
    {
        $this->_validate($thisSideEntity);
        /**
         * here $otherSideEntity is the Inverse Side Entity
         */
        $otherSideEntity = $this->getOneBy(
            $thisSideProperty,
            $repository,
            $options
        );
        $this->_validate($otherSideEntity);

        /**
         * Let's make the OLD inverse side aware about the changes we made in here
         *
         * In case we try to set from both directions in the same time, they mutually exclude themselves
         */
        $oldInverseAssociation = $this->accessor->getValue($thisSideEntity, $thisSideProperty);
        if ($oldInverseAssociation && $oldInverseAssociation != $otherSideEntity) {
            $this->accessor->setValue($oldInverseAssociation, $otherSideProperty, null);
        }

        $this->accessor->setValue($thisSideEntity, $thisSideProperty, $otherSideEntity);
        /**
         * Let's make the inverse side aware about the changes we made in here
         */
        $this->accessor->setValue($otherSideEntity, $otherSideProperty, $thisSideEntity);

        return $thisSideEntity;
    }

    /**
     * @param $thisSideEntity
     * @param string $thisSideProperty
     * @param string $repository
     * @param string $otherSideProperty
     * @param array $options
     * @return mixed
     */
    public function owningSideSetsOneToOneBidirectionalOrNull($thisSideEntity,
                                                              string $thisSideProperty,
                                                              string $repository,
                                                              string $otherSideProperty,
                                                              array $options = [])
    {
        $this->_validate($thisSideEntity);
        /**
         * here $otherSideEntity is the Inverse Side Entity
         */
        $otherSideEntity = $this->getOneBy(
            $thisSideProperty,
            $repository,
            $options
        );

        /**
         * If no association is set
         */
        if (!$otherSideEntity) {
            $oldOtherSideEntity = $this->accessor->getValue($thisSideEntity, $thisSideProperty);
            if ($oldOtherSideEntity) {
                $this->accessor->setValue($oldOtherSideEntity, $otherSideProperty, null);
            }

            $this->accessor->setValue($thisSideEntity, $thisSideProperty, null);

            return $thisSideEntity;
        }

        $options['entity'] = $options['entity'] ?? $otherSideEntity;
        return $this->owningSideSetsOneToOneBidirectional(
            $thisSideEntity,
            $thisSideProperty,
            $repository,
            $otherSideProperty,
            $options
        );
    }

    /**
     * http://docs.doctrine-project.org/projects/doctrine-orm/en/latest/reference/association-mapping.html#one-to-many-bidirectional
     *
     * Identical to $this->inverseSideAddsOneToManyBidirectional()
     * Helps to batch process associations using the adder
     *
     * @param $thisSideEntity
     * @param $thisSidePropertyPath
     * @param string $repository
     * @param array $otherSideEntities Array of numbers is searching by column `id`
     * @param array $options
     * @return mixed
     */
    public function inverseSideBatchAddsOneToManyBidirectional($thisSideEntity,
                                                               $thisSidePropertyPath,
                                                               string $repository,
                                                               array $otherSideEntities,
                                                               array $options = [])
    {
        foreach ($this->getManyBy($repository, $otherSideEntities) as $otherSideEntity) {
            $this->inverseSideAddsOneToManyBidirectional(
                $thisSideEntity,
                $thisSidePropertyPath,
                $repository,
                array_merge($options, [
                    'entity' => $otherSideEntity
                ])
            );
        }

        return $thisSideEntity;
    }

    /**
     * http://docs.doctrine-project.org/projects/doctrine-orm/en/latest/reference/association-mapping.html#one-to-many-bidirectional
     *
     * The adder is always executed on the Inverse Side. The Owning Side only has a setter.
     *
     * $options = [
     *  thisSideAdder       required    string
     *  otherSideProperty   required    string   This is the property representing This Entity (mappedBy) on the owning side.
     *
     *  ...used by ThrowableHelper::NotInstanceOf
     *  instanceOf      required   string   Path to class (class name), class instance, \stdClass() instance.
     *
     *  ...used by $this->getFromJSON() inside $this->getOneBy()
     *  propertyPath    optional    string
     *  useValue        optional    mixed
     *  forceReturn     optional    boolean
     *
     *  ...Used by $this->getOneBy() to returning the provided entity
     *  entity          optional    Doctrine Entity
     *  findBy          optional    string              Defaults to the column name `id`
     * ]
     *
     * In case $otherSideProperty confuses you
     * $otherSideProperty = mappedBy on the Inverse Side
     * $otherSideProperty is a property on the Owning Side
     * If you are still confused, just read the example online. Check for the mappedBy="..."; from here gets clear.
     *
     * $options['useValue'], $repository & $otherSideEntity refer to the Other Entity (The Owner)
     *
     * @param $thisSideEntity           mixed
     * @param $thisSidePropertyPath     string
     * @param $repository               string
     * @param array $options
     * @return mixed
     */
    public function inverseSideAddsOneToManyBidirectional($thisSideEntity,
                                                          $thisSidePropertyPath,
                                                          string $repository,
                                                          array $options = [])
    {
        $this->_validate($thisSideEntity);

        $otherSideEntity = $this->getOneBy($thisSidePropertyPath, $repository, $options);
        $this->_validate($otherSideEntity);

        /**
         * We call the adder to make the Inverse Side aware of any change we made.
         * For this to be persisted, we actually have to call the owning side which will happen bellow.
         */
        $thisSideEntity->{$options['thisSideAdder']}($otherSideEntity);

        /**
         * Because Doctrine will only check the owning side of an association for changes;
         * Let's make Doctrine aware of the change by changing the owning side.
         */
        $this->accessor->setValue($otherSideEntity, $options['otherSideProperty'], $thisSideEntity);

        return $thisSideEntity;
    }

    /**
     * http://docs.doctrine-project.org/projects/doctrine-orm/en/latest/reference/association-mapping.html#one-to-many-bidirectional
     *
     * It is your job to get the entities using $this->getFromJSON()
     *
     * @param $thisSideEntity
     * @param string $thisSideProperty
     * @param string $repository
     * @param string $otherSideProperty
     * @param array $otherSideEntities
     * @return mixed
     */
    public function inverseSideSetsOneToManyBidirectional($thisSideEntity,
                                                          string $thisSideProperty,
                                                          string $repository,
                                                          string $otherSideProperty,
                                                          array $otherSideEntities)
    {
        $this->_validate($thisSideEntity);

        /**
         * remove old associations
         *
         * NO need to call the remover on the inverse side.
         * We will later call the setter and this overwrites all associations.
         */
        foreach ($this->getAccessor()->getValue($thisSideEntity, $thisSideProperty) ?: [] as $otherSideEntity) {
            $this->accessor->setValue($otherSideEntity, $otherSideProperty, null);
        }

        $otherSideEntities = $this->getManyById($repository, $otherSideEntities);

        // set the associations
        $this->accessor->setValue(
            $thisSideEntity,
            $thisSideProperty,
            $otherSideEntities
        );

        // make the owning side aware of the changes
        foreach ($otherSideEntities as $otherSideEntity) {
            $this->accessor->setValue($otherSideEntity, $otherSideProperty, $thisSideEntity);
        }

        return $thisSideEntity;
    }

    /**
     * @param string $repository
     * @param array $entities
     * @return array
     */
    public function getManyById(string $repository, array $entities)
    {
        return $this->getManyBy($repository, $entities);
    }

    /**
     * http://docs.doctrine-project.org/projects/doctrine-orm/en/latest/reference/association-mapping.html#one-to-many-bidirectional
     *
     * $options = [
     *  ...used by ThrowableHelper::NotInstanceOf
     *  instanceOf      required   string   Path to class (class name), class instance, \stdClass() instance.
     *
     *  ...used by $this->getFromJSON() inside $this->getOneBy()
     *  propertyPath    optional    string
     *  useValue        optional    mixed
     *  forceReturn     optional    boolean
     *
     *  ...Used by $this->getOneBy() to returning the provided entity
     *  entity          optional    Doctrine Entity
     *  findBy          optional    string              Defaults to the column name `id`
     * ]
     *
     * $options['useValue'], $repository & $thisSideProperty refer to the Other Entity (The Inverse)
     *
     * @param $thisSideEntity
     * @param string $thisSideProperty
     * @param string $repository
     * @param string $otherSideAdder
     * @param string $otherSideRemover
     * @param array $options
     * @return mixed
     */
    public function owningSideSetsOneToManyBidirectional($thisSideEntity,
                                                         string $thisSideProperty,
                                                         string $repository,
                                                         string $otherSideAdder,
                                                         string $otherSideRemover,
                                                         array $options = [])
    {
        $this->_validate($thisSideEntity);

        $otherSideEntity = $this->getOneBy($thisSideProperty, $repository, $options);
        $this->_validate($otherSideEntity);

        // remove self from past relationships
        $oldOtherSideEntity = $this->accessor->getValue($thisSideEntity, $thisSideProperty);
        if ($oldOtherSideEntity) {
            $oldOtherSideEntity->{$otherSideRemover}($thisSideEntity);
        }

        // set the association
        $this->accessor->setValue($thisSideEntity, $thisSideProperty, $otherSideEntity);

        // make the inverse side aware of the change
        $otherSideEntity->{$otherSideAdder}($thisSideEntity);

        return $thisSideEntity;
    }

    /**
     * @param $thisSideEntity
     * @param string $thisSideProperty
     * @param string $repository
     * @param string $otherSideAdder
     * @param string $otherSideRemover
     * @param array $options
     * @return mixed
     */
    public function owningSideSetsOneToManyBidirectionalOrNull($thisSideEntity,
                                                               string $thisSideProperty,
                                                               string $repository,
                                                               string $otherSideAdder,
                                                               string $otherSideRemover,
                                                               array $options = [])
    {
        $this->_validate($thisSideEntity);

        $otherSideEntity = $this->getOneBy($thisSideProperty, $repository, $options);
        if (!$otherSideEntity) {
            // remove self from past relationships
            $oldOtherSideEntity = $this->accessor->getValue($thisSideEntity, $thisSideProperty);
            if ($oldOtherSideEntity) {
                $oldOtherSideEntity->{$otherSideRemover}($thisSideEntity);
            }

            $this->accessor->setValue($thisSideEntity, $thisSideProperty, null);
            return $thisSideEntity;
        }

        $options['entity'] = $options['entity'] ?? $otherSideEntity;
        return $this->owningSideSetsOneToManyBidirectional(
            $thisSideEntity,
            $thisSideProperty,
            $repository,
            $otherSideAdder,
            $otherSideRemover,
            $options
        );
    }

    /**
     * http://docs.doctrine-project.org/projects/doctrine-orm/en/latest/reference/association-mapping.html#one-to-many-unidirectional-with-join-table
     *
     * Identical to $this->addOneToManyUnidirectional()
     * Helps to batch process associations using the adder
     *
     * It is your job to get the entities using $this->getFromJSON()
     *
     * @param $thisSideEntity
     * @param string $thisSideProperty
     * @param string $repository
     * @param array $otherSideEntities Array of numbers; this searches by column `id`
     * @param array $options
     */
    public function batchAddOneToManyUnidirectional($thisSideEntity,
                                                    string $thisSideProperty,
                                                    string $repository,
                                                    array $otherSideEntities,
                                                    array $options = [])
    {
        foreach ($this->getManyBy($repository, $otherSideEntities) as $otherSideEntity) {
            $this->addOneToManyUnidirectional(
                $thisSideEntity,
                $thisSideProperty,
                $repository,
                array_merge($options, [
                    'entity' => $otherSideEntity
                ])
            );
        }
    }

    /**
     * http://docs.doctrine-project.org/projects/doctrine-orm/en/latest/reference/association-mapping.html#one-to-many-unidirectional-with-join-table
     *
     * $options = [
     *  thisSideAdder   required   string
     *
     *  ...used by ThrowableHelper::NotInstanceOf
     *  instanceOf      required   string   Path to class (class name), class instance, \stdClass() instance.
     *
     *  ...used by $this->getFromJSON() inside $this->getOneBy()
     *  propertyPath    optional    string
     *  useValue        optional    mixed
     *  forceReturn     optional    boolean
     *
     *  ...Used by $this->getOneBy() to returning the provided entity
     *  entity          optional    Doctrine Entity
     *  findBy          optional    string              Defaults to the column name `id`
     * ]
     *
     * $options['useValue'], $repository & $thisSideProperty refer to the Other Entity
     *
     * @param $thisSideEntity
     * @param string $thisSideProperty
     * @param string $repository
     * @param array $options
     */
    public function addOneToManyUnidirectional($thisSideEntity,
                                               string $thisSideProperty,
                                               string $repository,
                                               array $options = [])
    {
        $this->_validate($thisSideEntity);

        $otherSideEntity = $this->getOneBy($thisSideProperty, $repository, $options);
        $this->_validate($otherSideEntity);

        $thisSideEntity->{$options['thisSideAdder']}($otherSideEntity);
    }

    /**
     * http://docs.doctrine-project.org/projects/doctrine-orm/en/latest/reference/association-mapping.html#one-to-many-unidirectional-with-join-table
     *
     * It is your job to get the entities using $this->getFromJSON()
     *
     * todo : test that old orphan relationships are managed (removed) by Doctrine. If not, handle them (manually remove).
     *
     * @param $thisSideEntity
     * @param string $thisSideProperty
     * @param string $repository
     * @param array $otherSideEntities
     * @return mixed
     */
    public function setOneToManyUnidirectional($thisSideEntity,
                                               string $thisSideProperty,
                                               string $repository,
                                               array $otherSideEntities)
    {
        $this->_validate($thisSideEntity);

        $otherSideEntities = new ArrayCollection($this->getManyById($repository, $otherSideEntities));

        // set the associations
        $this->accessor->setValue(
            $thisSideEntity,
            $thisSideProperty,
            $otherSideEntities
        );

        return $thisSideEntity;
    }

    /**
     * Identical to $this->addManyToManyBidirectional()
     * Helps to batch process associations using the adder
     *
     * It is your job to get the entities using $this->getFromJSON()
     *
     * @param $thisSideEntity
     * @param string $thisSideProperty
     * @param string $repository
     * @param array $otherSideEntities
     * @param array $options
     */
    public function batchAddManyToManyBidirectional($thisSideEntity,
                                                    string $thisSideProperty,
                                                    string $repository,
                                                    array $otherSideEntities,
                                                    array $options = [])
    {
        foreach ($this->getManyBy($repository, $otherSideEntities) as $otherSideEntity) {
            $this->addManyToManyBidirectional(
                $thisSideEntity,
                $thisSideProperty,
                $repository,
                array_merge($options, [
                    'entity' => $otherSideEntity
                ])
            );
        }
    }

    /**
     * http://docs.doctrine-project.org/projects/doctrine-orm/en/latest/reference/association-mapping.html#many-to-many-bidirectional
     *
     * IMPORTANT! We don't differentiate between inverse and owning side because we want to trigger the event on each side
     * so that they both (the sides) are aware about changes to be made. The OneToMany case is more specific and this is
     * why we differentiate between the inverse and owning when setting associations.
     *
     * $options = [
     *  thisSideAdder       required   string
     *  otherSideAdder      required   string
     *
     *  ...used by ThrowableHelper::NotInstanceOf
     *  instanceOf      required   string   Path to class (class name), class instance, \stdClass() instance.
     *
     *  ...used by $this->getFromJSON() inside $this->getOneBy()
     *  propertyPath    optional    string
     *  useValue        optional    mixed
     *  forceReturn     optional    boolean
     *
     *  ...Used by $this->getOneBy() to returning the provided entity
     *  entity          optional    Doctrine Entity
     *  findBy          optional    string              Defaults to the column name `id`
     * ]
     *
     * $options['useValue'], $repository & $thisSideProperty refer to the Other Entity
     *
     * @param $thisSideEntity
     * @param string $thisSideProperty
     * @param string $repository
     * @param array $options
     * @return mixed
     */
    public function addManyToManyBidirectional($thisSideEntity,
                                               string $thisSideProperty,
                                               string $repository,
                                               array $options = [])
    {
        $this->_validate($thisSideEntity);

        $otherSideEntity = $this->getOneBy($thisSideProperty, $repository, $options);
        $this->_validate($otherSideEntity);

        $thisSideEntity->{$options['thisSideAdder']}($otherSideEntity);
        $otherSideEntity->{$options['otherSideAdder']}($thisSideEntity);

        return $thisSideEntity;
    }

    /**
     * http://docs.doctrine-project.org/projects/doctrine-orm/en/latest/reference/association-mapping.html#many-to-many-bidirectional
     *
     * IMPORTANT! We don't differentiate between inverse and owning side because we want to trigger the event on each side
     * so that they both (the sides) are aware about changes to be made. The OneToMany case is more specific and this is
     * why we differentiate between the inverse and owning when setting associations.
     *
     * $options = [
     *  thisSideGetter      required   string
     *  otherSideRemover    required   string
     *  otherSideAdder      required   string
     * ]
     *
     * @param $thisSideEntity
     * @param string $thisSideProperty
     * @param string $repository
     * @param array $otherSideEntities
     * @param array $options
     * @return mixed
     */
    public function setManyToManyBidirectional($thisSideEntity,
                                               string $thisSideProperty,
                                               string $repository,
                                               array $otherSideEntities,
                                               array $options = [])
    {
        $this->_validate($thisSideEntity);

        $otherSideEntities = new ArrayCollection($this->getManyById($repository, $otherSideEntities));

        /**
         * remove all current associations
         * we can use a check and verify if $entities contain $otherSideEntity
         * and by doing so avoid removing associations that don't change
         * for now we don't do it even if maybe by doing it will be faster
         * if is or not faster can only be seen with a benchmark.
         */
        foreach ($thisSideEntity->{$options['thisSideGetter']}() as $otherSideEntity) {
            $otherSideEntity->{$options['otherSideRemover']}($thisSideEntity);
        }

        // set the associations on this side
        $this->accessor->setValue(
            $thisSideEntity,
            $thisSideProperty,
            $otherSideEntities
        );

        // let make aware the other side about the update
        foreach ($otherSideEntities as $otherSideEntity) {
            $otherSideEntity->{$options['otherSideAdder']}($thisSideEntity);
        }

        return $thisSideEntity;
    }

    /**
     * Identical to $this->addManyToManyBidirectional()
     * Helps to batch process associations using the adder
     *
     * It is your job to get the entities using $this->getFromJSON()
     *
     * @param $thisSideEntity
     * @param string $thisSideProperty
     * @param string $repository
     * @param array $otherSideEntities
     * @param array $options
     */
    public function batchRemoveAssociations($thisSideEntity,
                                            string $thisSideProperty,
                                            string $repository,
                                            array $otherSideEntities,
                                            array $options = [])
    {
        foreach ($this->getManyBy($repository, $otherSideEntities) as $otherSideEntity) {
            $this->removeAssociation(
                $thisSideEntity,
                $thisSideProperty,
                $repository,
                array_merge($options, [
                    'entity' => $otherSideEntity
                ])
            );
        }
    }

    /**
     * $options = [
     *  thisSideRemover     required    string
     *  otherSideRemover    optional    string
     *  otherSideProperty   optional    string
     *
     *  ...used by ThrowableHelper::NotInstanceOf
     *  instanceOf      required   string   Path to class (class name), class instance, \stdClass() instance.
     *
     *  ...used by $this->getFromJSON() inside $this->getOneBy()
     *  propertyPath    optional    string
     *  useValue        optional    mixed
     *  forceReturn     optional    boolean
     *
     *  ...Used by $this->getOneBy() to returning the provided entity
     *  entity          optional    Doctrine Entity
     *  findBy          optional    string              Defaults to the column name `id`
     * ]
     *
     * @param $thisSideEntity
     * @param string $thisSideProperty
     * @param string $repository
     * @param array $options
     * @return mixed
     * @throws \Throwable
     */
    public function removeAssociation($thisSideEntity,
                                      string $thisSideProperty,
                                      string $repository,
                                      array $options = [])
    {
        if (array_key_exists('otherSideProperty', $options) && array_key_exists('otherSideRemover', $options)) {
            throw new \Error('Ambiguous operation. There is no way to differentiate between OneToMany & ManyToMany.');
        }

        $this->_validate($thisSideEntity);

        $otherSideEntity = $this->getOneBy($thisSideProperty, $repository, $options);
        $this->_validate($otherSideEntity);

        // remove the association
        $thisSideEntity->{$options['thisSideRemover']}($otherSideEntity);

        // in case OneToMany expect otherSideProperty
        if (array_key_exists('otherSideProperty', $options)) {
            $this->accessor->setValue($otherSideEntity, $options['otherSideProperty'], null);

            return $thisSideEntity;
        }

        // in case ManyToMany expect otherSideRemover
        if (array_key_exists('otherSideRemover', $options)) {
            $otherSideEntity->{$options['otherSideRemover']}($thisSideEntity);
        }

        return $thisSideEntity;
    }

    /**
     * @return Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @return ObjectManager
     */
    public function getEntityManager()
    {
        return $this->entityManager;
    }

    /**
     * @return PropertyAccess|\Symfony\Component\PropertyAccess\PropertyAccessor
     */
    public function getAccessor()
    {
        return $this->accessor;
    }

    /**
     * @return Logger|object
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * @return mixed|\stdClass
     */
    public function getRequestContent()
    {
        return $this->requestContent;
    }

    /**
     * @return mixed|\stdClass
     */
    protected function _getRequestContent()
    {
        return (
        (
            method_exists($this->request, 'getContentType')
            &&
            $this->request->getContentType() === 'json'
        )
            ?
            json_decode($this->request->getContent())
            :
            new \stdClass()
        );
    }
}