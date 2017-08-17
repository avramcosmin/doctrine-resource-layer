<?php

namespace Mindlahus\DoctrineResourceLayer\AbstractInterface;

interface ResourceInterface
{
    /**
     * http://symfony.com/doc/current/components/property_access.html
     *
     * @param string $propertyPath
     * @param mixed $content
     * @param bool|null $forceReturn
     * @return null|mixed
     * @throws \Throwable
     */
    public function getFromJSON(string $propertyPath, $content = null, bool $forceReturn = null);

    /**
     * @param string $propertyPath
     * @param string|null $pathOverwritePrefix
     * @return null|string
     */
    public function gluePathOverwritePrefix(string $propertyPath, string $pathOverwritePrefix = null):? string;

    /**
     * @param $entity
     * @param string $propertyPath
     * @param string|null $propertyPathOverwrite
     * @param mixed $content
     * @param bool|null $forceReturn
     * @return mixed
     * @throws \Throwable
     */
    public function set($entity, string $propertyPath, string $propertyPathOverwrite = null, $content = null, bool $forceReturn = null);

    /**
     * @param array $propertyPaths
     * @param $entity
     * @param string|null $pathOverwritePrefix
     * @throws \Throwable
     */
    public function batchSet(array $propertyPaths, $entity, string $pathOverwritePrefix = null);

    /**
     * @param string $propertyPath
     * @param string $repository
     * @param string $col
     * @param mixed $content
     * @param bool|null $forceReturn
     * @return object|null
     * @throws \Throwable
     */
    public function getOneBy(string $propertyPath, string $repository, string $col, $content = null, bool $forceReturn = null);

    /**
     * @param string $propertyPath
     * @param string $repository
     * @param mixed $content
     * @param bool|null $forceReturn
     * @return object|null
     * @throws \Throwable
     */
    public function getOneById(string $propertyPath, string $repository, $content = null, bool $forceReturn = null);

    /**
     * @param string $repository
     * @param array $values
     * @param string $col
     * @return array
     * @throws \Throwable
     */
    public function getManyBy(string $repository, array $values, string $col): array;

    /**
     * @param string $repository
     * @param array $values
     * @return array
     * @throws \Throwable
     */
    public function getManyById(string $repository, array $values): array;

    /**
     * Filters an array of entities and returns an array of id's
     * This will receive either integers or objects that should represent same Entity object.
     *
     * @param array $arr
     * @return array
     * @throws \Throwable
     */
    public function filterEntitiesForIds(array $arr): array;

    /**
     * @param $entity
     * @param string $propertyPath
     * @param string|null $propertyPathOverwrite
     * @param mixed $content
     * @param bool|null $forceReturn
     * @param bool $isNullAllowed
     * @return mixed
     * @throws \Throwable
     */
    public function setFloat($entity, string $propertyPath, string $propertyPathOverwrite = null, $content = null, bool $forceReturn = null, bool $isNullAllowed = true);

    /**
     * @param string|float|null $val
     * @param bool $isNullAllowed
     * @return null|float
     * @throws \Throwable
     */
    public function getFloat($val, bool $isNullAllowed = true):? float;

    /**
     * @param string|float $val
     * @return null|float
     */
    public function isFloat($val):? float;

    /**
     * @param $entity
     * @param string $propertyPath
     * @param string|null $propertyPathOverwrite
     * @param mixed $content
     * @param bool|null $forceReturn
     * @param bool $isNullAllowed
     * @return mixed
     * @throws \Throwable
     */
    public function setDouble($entity, string $propertyPath, string $propertyPathOverwrite = null, $content = null, bool $forceReturn = null, bool $isNullAllowed = true);

    /**
     * @param string|float|null $val
     * @param bool $isNullAllowed
     * @return null|float
     * @throws \Throwable
     */
    public function getDouble($val, bool $isNullAllowed = true):? float;

    /**
     * @param $val
     * @param int $decimals
     * @param bool $isNullAllowed
     * @return null|float
     * @throws \Throwable
     */
    public function getWithDecimals($val, int $decimals, bool $isNullAllowed = true):? float;

    /**
     * @param $entity
     * @param string $propertyPath
     * @param string|null $propertyPathOverwrite
     * @param mixed $content
     * @param bool|null $forceReturn
     * @param bool $isNullAllowed
     * @return mixed
     * @throws \Throwable
     */
    public function setInt($entity, string $propertyPath, string $propertyPathOverwrite = null, $content = null, bool $forceReturn = null, bool $isNullAllowed = true);

    /**
     * Avoid using FILTER_SANITIZE_NUMBER_INT
     * The problem with this is that will transform a float into an integer with error
     * Ex. 122.45 will become 12245 (this is dangerous)
     *
     * @param string|int|null $val
     * @param bool $isNullAllowed
     * @return mixed|null
     * @throws \Throwable
     */
    public function getInt($val, bool $isNullAllowed = true);

    /**
     * @param $val
     * @return null|int
     */
    public function isInt($val):? int;

    /**
     * @param $entity
     * @param string $propertyPath
     * @param mixed $defaultValue
     * @param string|null $propertyPathOverwrite
     * @param mixed $content
     * @param bool|null $forceReturn
     * @return mixed
     * @throws \Throwable
     */
    public function setOrUseDefault($entity, string $propertyPath, $defaultValue, string $propertyPathOverwrite = null, $content = null, bool $forceReturn = null);

    /**
     * @param $entity
     * @param string $propertyPath
     * @param string|null $propertyPathOverwrite
     * @param mixed $content
     * @param bool|null $forceReturn
     * @param bool $isNullAllowed
     * @return mixed
     * @throws \Throwable
     */
    public function setNumeric($entity, string $propertyPath, string $propertyPathOverwrite = null, $content = null, bool $forceReturn = null, bool $isNullAllowed = true);

    /**
     * @param array $propertyPaths
     * @param $entity
     * @param string|null $pathOverwritePrefix
     * @return mixed
     * @throws \Throwable
     */
    public function batchSetNumeric(array $propertyPaths, $entity, string $pathOverwritePrefix = null);

    /**
     * @param $val
     * @param bool $isNullAllowed
     * @return mixed
     * @throws \Throwable
     */
    public function getNumeric($val, bool $isNullAllowed = true);

    /**
     * @param $entity
     * @param string $propertyPath
     * @param string|null $propertyPathOverwrite
     * @param mixed $content
     * @param bool|null $forceReturn
     * @param bool $isNullAllowed
     * @return mixed
     * @throws \Throwable
     */
    public function setDate($entity, string $propertyPath, string $propertyPathOverwrite = null, $content = null, bool $forceReturn = null, bool $isNullAllowed = true);

    /**
     * @param array $propertyPaths
     * @param $entity
     * @param string|null $pathOverwritePrefix
     * @return mixed
     * @throws \Throwable
     */
    public function batchSetDate(array $propertyPaths, $entity, string $pathOverwritePrefix = null);

    /**
     * @param $val
     * @param bool $isNullAllowed
     * @return \DateTime|null
     * @throws \Throwable
     */
    public function getDate($val, $isNullAllowed = true):? \DateTime;

    /**
     * @param $val
     * @return \DateTime|null
     */
    public function isDateTime($val):? \DateTime;

    /**
     * This gets the current value of the propertyPath and sets its negation.
     * The value should be a boolean.
     *
     * @param $entity
     * @param string $propertyPath
     * @return mixed
     * @throws \Throwable
     */
    public function setNegation($entity, string $propertyPath);

    /**
     * @param $entity
     * @param string $propertyPath
     * @param $objectOrArray
     * @param string|null $propertyPathOverwrite
     * @return mixed
     * @throws \Throwable
     */
    public function setBoolIfObjectOrArrayHas($entity, string $propertyPath, $objectOrArray, string $propertyPathOverwrite = null);

    /**
     * This sets true, false or null.
     *
     * @param $entity
     * @param string $propertyPath
     * @param string|null $propertyPathOverwrite
     * @param mixed $content
     * @param bool|null $forceReturn
     * @return mixed
     * @throws \Throwable
     */
    public function setBool($entity, string $propertyPath, string $propertyPathOverwrite = null, $content = null, bool $forceReturn = null);

    /**
     * @param array $propertyPaths
     * @param $entity
     * @param string|null $pathOverwritePrefix
     * @throws \Throwable
     */
    public function batchSetBool(array $propertyPaths, $entity, string $pathOverwritePrefix = null);

    /**
     * @param $entity
     * @param string $propertyPath
     * @param int|null $length
     * @param string|null $propertyPathOverwrite
     * @param mixed $content
     * @param bool|null $forceReturn
     * @return mixed
     * @throws \Throwable
     */
    public function setMarkdown($entity, string $propertyPath, int $length = null, string $propertyPathOverwrite = null, $content = null, bool $forceReturn = null);

    /**
     * @param $entity
     * @param string $propertyPath
     * @param string|null $propertyPathOverwrite
     * @param mixed $content
     * @param bool|null $forceReturn
     * @return mixed
     * @throws \Throwable
     */
    public function setMarkdownRaw($entity, string $propertyPath, string $propertyPathOverwrite = null, $content = null, bool $forceReturn = null);

    /**
     * @param $entity
     * @param string $propertyPath
     * @param string|null $propertyPathOverwrite
     * @param mixed $content
     * @param bool|null $forceReturn
     * @return mixed
     * @throws \Throwable
     */
    public function setMarkdownHTML($entity, string $propertyPath, string $propertyPathOverwrite = null, $content = null, bool $forceReturn = null);

    /**
     * @param $entity
     * @param string $propertyPath
     * @param int|null $length
     * @param string|null $propertyPathOverwrite
     * @param mixed $content
     * @param bool|null $forceReturn
     * @return mixed
     * @throws \Throwable
     */
    public function setMarkdownShort($entity, string $propertyPath, int $length = null, string $propertyPathOverwrite = null, $content = null, bool $forceReturn = null);

    /**
     * http://docs.doctrine-project.org/projects/doctrine-orm/en/latest/reference/association-mapping.html#one-to-one-unidirectional
     *
     * Owning Side, One-To-One, Unidirectional
     *
     * Ex.
     * Product [owning side]
     * One Product has One Shipment.
     * $product->shipment [OneToOne] [targetEntity: Shipment] [JoinColumn]
     *
     * Shipment [no inverse side]
     *
     * $productResource->setOneToOneUnidirectional($product, 'shipment', ShipmentRepository, ... )
     *
     * @param object $owningEntity $product
     * @param string $owningPropertyPath $product->shipment
     * @param string $repository ShipmentRepository
     * @param string $owningPropertyPathOverwrite
     * @param mixed $content
     * @param bool|null $forceReturn
     * @return object
     * @throws \Throwable
     */
    public function setOneToOneUnidirectional($owningEntity, string $owningPropertyPath, string $repository, string $owningPropertyPathOverwrite = null, $content = null, bool $forceReturn = null);

    /**
     * http://docs.doctrine-project.org/projects/doctrine-orm/en/latest/reference/association-mapping.html#many-to-one-unidirectional
     *
     * Owning Side, Many-To-One, Unidirectional
     *
     * Ex.
     * User [owning side]
     * Many Users have One Address.
     * $user->address [ManyToOne] [targetEntity: Address] [JoinColumn]
     *
     * Address [no inverse side]
     *
     * $userResource->setOneToOneUnidirectional(
     *                                          $user,
     *                                          'address',
     *                                          AddressRepository,
     *                                          'address.id', ... )
     *
     * @param object $owningEntity $user
     * @param string $owningPropertyPath $user->address
     * @param string $repository AddressRepository
     * @param string $owningPropertyPathOverwrite
     * @param mixed $content
     * @param bool|null $forceReturn
     * @return object
     * @throws \Throwable
     */
    public function setManyToOneUnidirectional($owningEntity, string $owningPropertyPath, string $repository, string $owningPropertyPathOverwrite = null, $content = null, bool $forceReturn = null);

    /**
     * http://docs.doctrine-project.org/projects/doctrine-orm/en/latest/reference/association-mapping.html#one-to-one-bidirectional
     * http://docs.doctrine-project.org/projects/doctrine-orm/en/latest/reference/unitofwork-associations.html
     *
     * Inverse Side, One-To-One, Bidirectional
     *
     * The owning side of a OneToOne, Bidirectional association is the entity with the table containing the foreign key.
     * Doctrine will only check the owning side of an association for changes.
     * Changes made only to the inverse side of an association are ignored.
     *
     * Ex.
     * Customer [inverse side]
     * One Customer has One Cart.
     * $customer->cart [OneToOne] [targetEntity: Cart] [mappedBy]
     *
     * Cart [owning side]
     * One Cart has One Customer.
     * $cart->customer [OneToOne] [targetEntity: Customer] [inversedBy] [JoinColumn]
     *
     * $customerResource->inverseSideSetsOneToOneBidirectional($customer, 'cart', $cart, 'customer')
     *
     * $cart = $this->getOneById('cart',
     *                           CartRepository,
     *                           'cart.id',
     *                           $content,
     *                           $forceReturn
     *                          );
     *
     * @param object $inverseEntity $customer
     * @param string $inversePropertyPath $customer->cart
     * @param object|null $owningEntity $cart
     * @param string $owningSidePropertyPath $cart->customer
     * @return object
     * @throws \Throwable
     */
    public function inverseSideSetsOneToOneBidirectional($inverseEntity, string $inversePropertyPath, $owningEntity = null, string $owningSidePropertyPath);

    /**
     * http://docs.doctrine-project.org/projects/doctrine-orm/en/latest/reference/association-mapping.html#one-to-one-bidirectional
     *
     * Owning Side, One-To-One, Bidirectional
     *
     * Because this is the Owning Side, Doctrine automatically manages any change.
     *
     * Ex.
     * Customer [inverse side]
     * One Customer has One Cart.
     * $customer->cart [OneToOne] [targetEntity: Cart] [mappedBy]
     *
     * Cart [owning side]
     * One Cart has One Customer.
     * $cart->customer [OneToOne] [targetEntity: Customer] [inversedBy] [JoinColumn]
     *
     * $cartResource->owningSideSetsOneToOneBidirectional($cart, 'customer', $customer, 'cart')
     *
     * $customer = $this->getOneById('customer',
     *                               CustomerRepository,
     *                               'customer.id',
     *                               $content,
     *                               $forceReturn
     *                              );
     *
     * @param object $owningEntity $cart
     * @param string $owningPropertyPath $cart->customer
     * @param object|null $inverseEntity
     * @param string $inverseSidePropertyPath $customer->cart
     * @return object
     * @throws \Throwable
     */
    public function owningSideSetsOneToOneBidirectional($owningEntity, string $owningPropertyPath, $inverseEntity = null, string $inverseSidePropertyPath);

    /**
     * http://docs.doctrine-project.org/projects/doctrine-orm/en/latest/reference/association-mapping.html#one-to-many-bidirectional
     *
     * Inverse Side, One-To-Many, Bidirectional
     *
     * The adder is always executed on the Inverse Side. The Owning Side only has a setter.
     * There is no difference between a bidirectional one-to-many and a bidirectional many-to-one.
     *
     * Ex.
     * Product [inverse side]
     * One Product has Many Features.
     * $product->features [OneToMany] [targetEntity: Feature] [mappedBy]
     *
     * Feature [owning side]
     * Many Features have One Product.
     * $feature->product [ManyToOne] [targetEntity: Product] [inversedBy] [JoinColumn]
     *
     * $productResource->inverseSideAddsOneToManyBidirectional($product,
     *                                                         'addFeature',
     *                                                         'removeFeature',
     *                                                         $feature,
     *                                                         'product',
     *                                                        )
     *
     * $feature = $this->getOneById('feature',
     *                              FeatureRepository,
     *                              'feature.id',
     *                              $content,
     *                              $forceReturn
     *                             );
     *
     * @param object $inverseEntity $product
     * @param string $inverseAdder $product->addFeature()
     * @param string $inverseRemover $product->removeFeature()
     * @param object $owningEntity $feature
     * @param string $owningSidePropertyPath $feature->product
     * @param string $instanceOf
     * @return object
     * @throws \Throwable
     */
    public function inverseSideAddsOneToManyBidirectional($inverseEntity, string $inverseAdder, string $inverseRemover, $owningEntity, string $owningSidePropertyPath, string $instanceOf);

    /**
     * http://docs.doctrine-project.org/projects/doctrine-orm/en/latest/reference/association-mapping.html#one-to-many-bidirectional
     *
     * Inverse Side, One-To-Many, Bidirectional
     *
     * Ex.
     * Product [inverse side]
     * One Product has Many Features.
     * $product->features [OneToMany] [targetEntity: Feature] [mappedBy]
     *
     * Feature [owning side]
     * Many Features have One Product.
     * $feature->product [ManyToOne] [targetEntity: Product] [inversedBy] [JoinColumn]
     *
     * $productResource->inverseSideSetsOneToManyBidirectional($product,
     *                                                         'features',
     *                                                         'removeFeatures',
     *                                                         $features,
     *                                                         'product'
     *                                                        )
     *
     * $features = $this->getManyById(FeatureRepository,
     *                                $this->getFromJSON('features',
     *                                                   $content,
     *                                                   $forceReturn
     *                                                  )
     *                               )
     *
     * @param object $inverseEntity $product
     * @param string $inversePropertyPath $product->features
     * @param string $inverseRemover $product->removeFeature()
     * @param array $owningEntities $features
     * @param string $owningSidePropertyPath $feature->product
     * @return object
     * @throws \Throwable
     */
    public function inverseSideSetsOneToManyBidirectional($inverseEntity, string $inversePropertyPath, string $inverseRemover, array $owningEntities = [], string $owningSidePropertyPath);

    /**
     * http://docs.doctrine-project.org/projects/doctrine-orm/en/latest/reference/association-mapping.html#one-to-many-bidirectional
     *
     * Owning Side, One-To-Many, Bidirectional
     *
     * Ex.
     * Product [inverse side]
     * One Product has Many Features.
     * $product->features [OneToMany] [targetEntity: Feature] [mappedBy]
     *
     * Feature [owning side]
     * Many Features have One Product.
     * $feature->product [ManyToOne] [targetEntity: Product] [inversedBy] [JoinColumn]
     *
     * $productResource->owningSideSetsOneToManyBidirectional($feature,
     *                                                        'product',
     *                                                        $product,
     *                                                        'addFeature',
     *                                                        'removeFeature'
     *                                                       )
     *
     * $product = $this->getOneById('product',
     *                              ProductRepository,
     *                              'product.id',
     *                              $content,
     *                              $forceReturn
     *                             );
     *
     * @param object $owningEntity $feature
     * @param string $owningPropertyPath $feature->product
     * @param object|null $inverseEntity $product
     * @param string $inverseAdder $product->addFeature()
     * @param string $inverseRemover $product->removeFeature()
     * @return object
     * @throws \Throwable
     */
    public function owningSideSetsOneToManyBidirectional($owningEntity, string $owningPropertyPath, $inverseEntity = null, string $inverseAdder, string $inverseRemover);

    /**
     * http://docs.doctrine-project.org/projects/doctrine-orm/en/latest/reference/association-mapping.html#one-to-many-unidirectional-with-join-table
     *
     * A unidirectional one-to-many association can be mapped through a join table.
     * From Doctrine’s point of view, it is simply mapped as a unidirectional many-to-many
     * whereby a unique constraint on one of the join columns enforces the one-to-many cardinality.
     *
     * Inverse Side, One-To-Many, Unidirectional
     * Foreign Key sets in the Join Table
     *
     * Ex.
     * User [inverse side]
     * Many User have Many PhoneNumbers.
     * $user->phoneNumbers [ManyToMany] [targetEntity: PhoneNumber]
     *
     * [JoinTable]
     *
     * PhoneNumbers [no owning side]
     *
     * $userResource->addOneToManyUnidirectional($user,
     *                                           'addPhoneNumber',
     *                                           $phoneNumber
     *                                          )
     *
     * $phoneNumber = $this->getOneById('phoneNumber',
     *                                  PhoneRepository,
     *                                  'phoneNumber.id',
     *                                  $content,
     *                                  $forceReturn
     *                                 );
     *
     * @param $inverseEntity $user
     * @param string $inverseAdder $user->addPhoneNumber()
     * @param object $otherSideEntity $phoneNumber
     * @param string $instanceOf
     * @return object
     * @throws \Throwable
     */
    public function addOneToManyUnidirectional($inverseEntity, string $inverseAdder, $otherSideEntity, string $instanceOf);

    /**
     * http://docs.doctrine-project.org/projects/doctrine-orm/en/latest/reference/association-mapping.html#one-to-many-unidirectional-with-join-table
     *
     * A unidirectional one-to-many association can be mapped through a join table.
     * From Doctrine’s point of view, it is simply mapped as a unidirectional many-to-many
     * whereby a unique constraint on one of the join columns enforces the one-to-many cardinality.
     *
     * Inverse Side, One-To-Many, Unidirectional
     * Foreign Key sets in the Join Table
     *
     * Ex.
     * User [inverse side]
     * Many User have Many PhoneNumbers.
     * $user->phoneNumbers [ManyToMany] [targetEntity: PhoneNumber]
     *
     * [JoinTable]
     *
     * PhoneNumbers [no owning side]
     *
     * $userResource->setOneToManyUnidirectional($user,
     *                                           'phoneNumbers',
     *                                           $phoneNumbers
     *                                          )
     *
     * $features = $this->getManyById(PhoneNumberRepository,
     *                                $this->getFromJSON('phoneNumbers',
     *                                                   $content,
     *                                                   $forceReturn
     *                                                  )
     *                               )
     *
     * @param $inverseEntity $user
     * @param string $inversePropertyPath $user->phoneNumbers
     * @param array $otherSideEntities $phoneNumbers
     * @return mixed
     * @throws \Throwable
     */
    public function setOneToManyUnidirectional($inverseEntity, string $inversePropertyPath, array $otherSideEntities);

    /**
     * http://docs.doctrine-project.org/projects/doctrine-orm/en/latest/reference/association-mapping.html#many-to-many-bidirectional
     *
     * Many-To-Many, Bidirectional
     * No differentiation between inverse or owning side.
     *
     * Ex.
     * User [owning side] [this side]
     * Many Users have Many Groups.
     * $user->groups [ManyToMany] [targetEntity: Group] [inversedBy]
     *
     * [JoinTable]
     *
     * Group [inverse side] [other side]
     * Many Groups have Many Users.
     * $group->users [ManyToMany] [targetEntity: User] [mappedBy]
     *
     * $userResource->addManyToManyBidirectional($user,
     *                                           'addGroup',
     *                                           $group,
     *                                           'addUser'
     *                                          )
     *
     * $group = $this->getOneById('group',
     *                            GroupRepository,
     *                            'group.id',
     *                            $content,
     *                            $forceReturn
     *                           );
     *
     * @param object $thisSideEntity $user
     * @param string $thisSideAdder $user->addGroup()
     * @param object $otherSideEntity $group
     * @param string $otherSideAdder $group->addUser()
     * @param string $instanceOf
     * @return object
     * @throws \Throwable
     */
    public function addManyToManyBidirectional($thisSideEntity, string $thisSideAdder, $otherSideEntity, string $otherSideAdder, string $instanceOf);

    /**
     * http://docs.doctrine-project.org/projects/doctrine-orm/en/latest/reference/association-mapping.html#many-to-many-bidirectional
     *
     * Many-To-Many, Bidirectional
     * No differentiation between inverse or owning side.
     *
     * Ex.
     * User [owning side] [this side]
     * Many Users have Many Groups.
     * $user->groups [ManyToMany] [targetEntity: Group] [inversedBy]
     *
     * [JoinTable]
     *
     * Group [inverse side] [other side]
     * Many Groups have Many Users.
     * $group->users [ManyToMany] [targetEntity: User] [mappedBy]
     *
     * $userResource->setManyToManyBidirectional($user,
     *                                           'groups',
     *                                           $groups,
     *                                           'removeUser',
     *                                           'addUser'
     *                                          )
     *
     * $groups = $this->getManyById(GroupRepository,
     *                              $this->getFromJSON('groups',
     *                                                 $content,
     *                                                 $forceReturn
     *                                                )
     *                             )
     *
     * @param object $thisSideEntity $user
     * @param string $thisSidePropertyPath $user->groups
     * @param array $otherSideEntities $groups
     * @param string $otherSideRemover $group->removeUser()
     * @param string $otherSideAdder $group->addUser()
     * @return object
     * @throws \Throwable
     */
    public function setManyToManyBidirectional($thisSideEntity, string $thisSidePropertyPath, array $otherSideEntities = [], string $otherSideRemover, string $otherSideAdder);

    /**
     * @param object $thisSideEntity $user|$product
     * @param string $thisSideRemover $user->removeGroup()|$product->removeFeature()
     * @param object|null $otherSideEntity $group|$feature
     * @param string|null $otherSideRemover $group->removeUser()
     * @param string|null $otherSidePropertyPath $feature->product
     * @return object
     * @throws \Throwable
     */
    public function removeAssociation($thisSideEntity, string $thisSideRemover, $otherSideEntity = null, string $otherSideRemover = null, string $otherSidePropertyPath = null);
}