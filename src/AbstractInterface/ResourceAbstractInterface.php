<?php namespace Mindlahus\DoctrineResourceLayer\AbstractInterface;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PropertyAccess\PropertyAccess;

interface ResourceAbstractInterface
{
    /**
     * @param ContainerInterface $container
     */
    public function setContainer(ContainerInterface $container): void;

    /**
     * @return ContainerInterface
     */
    public function getContainer(): ContainerInterface;

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
    public function set($entity, string $propertyPath, array $options = []);

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
    public function getFromJSON(string $propertyPath, array $options = []);

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
    public function getOneBy(string $propertyPath, string $repository, array $options = []);

    /**
     * @param string $repository
     * @param array $entities
     * @param string $col
     * @return mixed|array
     */
    public function getManyBy(string $repository, array $entities, $col = 'id');

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
    public function setFloat($entity, string $propertyPath, array $options = []);

    /**
     * @param $val
     * @param bool $isNullAllowed
     * @return mixed
     * @throws \Throwable
     */
    public function getFloat($val, $isNullAllowed = true);

    /**
     * @param $val
     * @return mixed
     */
    public function isFloat($val);

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
    public function setDouble($entity, string $propertyPath, array $options = []);

    /**
     * @param $val
     * @param bool $isNullAllowed
     * @return mixed
     */
    public function getDouble($val, $isNullAllowed = true);

    /**
     * @param $val
     * @param $decimals
     * @param bool $isNullAllowed
     * @return mixed
     * @throws \Throwable
     */
    public function getWithDecimals($val, $decimals, $isNullAllowed = true);

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
    public function setInt($entity, string $propertyPath, array $options = []);

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
    public function getInt($val, $isNullAllowed = true);

    /**
     * @param $val
     * @return mixed
     */
    public function isInt($val);

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
    public function setOrUseDefault($entity, string $propertyPath, $defaultValue, array $options = []);

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
    public function setNumeric($entity, string $propertyPath, array $options = []);

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
    public function setDate($entity, string $propertyPath, array $options = []);

    /**
     * @param $val
     * @param bool $isNullAllowed
     * @return mixed|\DateTime|null
     * @throws \Throwable
     */
    public function getDate($val, $isNullAllowed = true);

    /**
     * @param $val
     * @return bool|\DateTime
     */
    public function isDateTime($val);

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
    public function setNegation($entity, string $propertyPath);

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
    public function setBoolIfStdClassHas($entity, string $propertyPath, array $options = []);

    /**
     * This sets true, false or null.
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
     * @param array $options
     * @return mixed
     */
    public function setBool($entity, string $propertyPath, array $options = []);

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
    public function setMarkdown($entity, string $propertyPath, array $options);

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
    public function setMarkdownRaw($entity, string $propertyPath, array $options);

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
    public function setMarkdownHTML($entity, string $propertyPath, array $options);

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
    public function setMarkdownShort($entity, string $propertyPath, array $options);

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
     */
    public function setManyToOneUnidirectional($thisSideEntity, string $thisSideProperty, string $repository, array $options = []);

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
    public function setOneToOneUnidirectional($thisSideEntity, string $thisSideProperty, string $repository, array $options = []);

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
    public function inverseSideSetsOneToOneBidirectional($thisSideEntity, string $thisSideProperty, string $repository, string $otherSideProperty, array $options = []);

    /**
     * @param $thisSideEntity
     * @param string $thisSideProperty
     * @param string $repository
     * @param string $otherSideProperty
     * @param array $options
     * @return mixed
     */
    public function inverseSideSetsOneToOneBidirectionalOrNull($thisSideEntity, string $thisSideProperty, string $repository, string $otherSideProperty, array $options = []);

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
    public function owningSideSetsOneToOneBidirectional($thisSideEntity, string $thisSideProperty, string $repository, string $otherSideProperty, array $options = []);

    /**
     * @param $thisSideEntity
     * @param string $thisSideProperty
     * @param string $repository
     * @param string $otherSideProperty
     * @param array $options
     * @return mixed
     */
    public function owningSideSetsOneToOneBidirectionalOrNull($thisSideEntity, string $thisSideProperty, string $repository, string $otherSideProperty, array $options = []);

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
    public function inverseSideBatchAddsOneToManyBidirectional($thisSideEntity, $thisSidePropertyPath, string $repository, array $otherSideEntities, array $options = []);

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
    public function inverseSideAddsOneToManyBidirectional($thisSideEntity, $thisSidePropertyPath, string $repository, array $options = []);

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
    public function inverseSideSetsOneToManyBidirectional($thisSideEntity, string $thisSideProperty, string $repository, string $otherSideProperty, array $otherSideEntities);

    /**
     * @param string $repository
     * @param array $entities
     * @return array
     */
    public function getManyById(string $repository, array $entities): array;

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
    public function owningSideSetsOneToManyBidirectional($thisSideEntity, string $thisSideProperty, string $repository, string $otherSideAdder, string $otherSideRemover, array $options = []);

    /**
     * @param $thisSideEntity
     * @param string $thisSideProperty
     * @param string $repository
     * @param string $otherSideAdder
     * @param string $otherSideRemover
     * @param array $options
     * @return mixed
     */
    public function owningSideSetsOneToManyBidirectionalOrNull($thisSideEntity, string $thisSideProperty, string $repository, string $otherSideAdder, string $otherSideRemover, array $options = []);

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
    public function batchAddOneToManyUnidirectional($thisSideEntity, string $thisSideProperty, string $repository, array $otherSideEntities, array $options = []): void;

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
    public function addOneToManyUnidirectional($thisSideEntity, string $thisSideProperty, string $repository, array $options = []): void;

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
     * @param array|ArrayCollection $otherSideEntities
     * @return mixed
     */
    public function setOneToManyUnidirectional($thisSideEntity, string $thisSideProperty, string $repository, array $otherSideEntities);

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
    public function batchAddManyToManyBidirectional($thisSideEntity, string $thisSideProperty, string $repository, array $otherSideEntities, array $options = []): void;

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
    public function addManyToManyBidirectional($thisSideEntity, string $thisSideProperty, string $repository, array $options = []);

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
     * @param array|ArrayCollection $otherSideEntities
     * @param array $options
     * @return mixed
     */
    public function setManyToManyBidirectional($thisSideEntity, string $thisSideProperty, string $repository, array $otherSideEntities, array $options = []);

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
    public function batchRemoveAssociations($thisSideEntity, string $thisSideProperty, string $repository, array $otherSideEntities, array $options = []): void;

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
    public function removeAssociation($thisSideEntity, string $thisSideProperty, string $repository, array $options = []);

    /**
     * @return Request
     */
    public function getRequest(): Request;

    /**
     * @return ObjectManager
     */
    public function getEntityManager(): ObjectManager;

    /**
     * @return PropertyAccess|\Symfony\Component\PropertyAccess\PropertyAccessor
     */
    public function getAccessor();

    /**
     * @return Logger|\stdClass
     */
    public function getLogger();

    /**
     * @return mixed|\stdClass
     */
    public function getRequestContent();
}