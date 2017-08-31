<?php

namespace Mindlahus\DoctrineResourceLayer\AbstractInterface;

use Mindlahus\SymfonyAssets\Helper\StringHelper;

abstract class ResourceAbstract
    extends \Mindlahus\SymfonyAssets\AbstractInterface\ResourceAbstract
    implements ResourceInterface
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
    public function getFromJSON(string $propertyPath,
                                $content = null,
                                bool $forceReturn = null
    )
    {
        if ($forceReturn === true) {
            return $content;
        }

        if (!is_array($content) && !is_object($content)) {
            $content = $this->getRequestContent();
        }

        return $this->getAccessor()->getValue(
            $content,
            $propertyPath
        );
    }

    /**
     * @param string $propertyPath
     * @param string|null $pathOverwritePrefix
     * @return null|string
     */
    public function gluePathOverwritePrefix(
        string $propertyPath,
        string $pathOverwritePrefix = null
    ):? string
    {
        if (null === $pathOverwritePrefix) {
            return null;
        }

        return trim($pathOverwritePrefix, '.') . '.' . $propertyPath;
    }

    /**
     * @param $entity
     * @param string $propertyPath
     * @param string|null $propertyPathOverwrite
     * @param mixed $content
     * @param bool|null $forceReturn
     * @return mixed
     * @throws \Throwable
     */
    public function set($entity,
                        string $propertyPath,
                        string $propertyPathOverwrite = null,
                        $content = null,
                        bool $forceReturn = null
    )
    {
        $this->accessor->setValue(
            $entity,
            $propertyPath,
            $this->getFromJSON(
                $propertyPathOverwrite ?: $propertyPath,
                $content,
                $forceReturn
            )
        );

        return $entity;
    }

    /**
     * @param array $propertyPaths
     * @param $entity
     * @param string|null $pathOverwritePrefix
     * @throws \Throwable
     */
    public function batchSet(array $propertyPaths,
                             $entity,
                             string $pathOverwritePrefix = null
    )
    {
        foreach ($propertyPaths as $propertyPath) {
            $this->set(
                $entity,
                $propertyPath,
                $this->gluePathOverwritePrefix(
                    $propertyPath,
                    $pathOverwritePrefix
                )
            );
        }
    }

    /**
     * @param string $propertyPath
     * @param string $repository
     * @param string $col
     * @param mixed $content
     * @param bool|null $forceReturn
     * @return mixed|null
     * @throws \Throwable
     */
    public function getOneBy(string $propertyPath,
                             string $repository,
                             string $col,
                             $content = null,
                             bool $forceReturn = null
    )
    {
        return $this->entityManager
            ->getRepository($repository)
            ->findOneBy([
                $col => $this->getFromJSON(
                    $propertyPath,
                    $content,
                    $forceReturn
                )
            ]);
    }

    /**
     * @param string $propertyPath
     * @param string $repository
     * @param mixed $content
     * @param bool|null $forceReturn
     * @return mixed|null
     * @throws \Throwable
     */
    public function getOneById(string $propertyPath,
                               string $repository,
                               $content = null,
                               bool $forceReturn = null
    )
    {
        return $this->getOneBy(
            $propertyPath,
            $repository,
            'id',
            $content,
            $forceReturn
        );
    }

    /**
     * @param string $repository
     * @param array $values
     * @param string $col
     * @return array
     * @throws \Throwable
     */
    public function getManyBy(string $repository,
                              array $values,
                              string $col
    ): array
    {
        return $this->entityManager
            ->getRepository($repository)
            ->findBy([
                $col => $values
            ]);
    }

    /**
     * @param string $repository
     * @param array $values
     * @return array
     * @throws \Throwable
     */
    public function getManyById(string $repository,
                                array $values
    ): array
    {
        return $this->getManyBy(
            $repository,
            $this->filterEntitiesForIds($values),
            'id'
        );
    }

    /**
     * Filters an array of entities and returns an array of id's
     * This will receive either integers or objects that should represent same Entity object.
     *
     * @param array $arr
     * @return array
     * @throws \Throwable
     */
    public function filterEntitiesForIds(array $arr): array
    {
        $res = [];

        foreach ($arr as $item) {
            if (filter_var($item, FILTER_VALIDATE_INT)) {
                $res[] = $item;
                continue;
            }

            $res[] = $this->accessor->getValue($item, 'id');
        }

        return $res;
    }

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
    public function setFloat($entity,
                             string $propertyPath,
                             string $propertyPathOverwrite = null,
                             $content = null,
                             bool $forceReturn = null,
                             bool $isNullAllowed = true
    )
    {
        $this->accessor->setValue(
            $entity,
            $propertyPath,
            $this->getFloat(
                $this->getFromJSON(
                    $propertyPathOverwrite ?: $propertyPath,
                    $content,
                    $forceReturn
                ),
                $isNullAllowed
            )
        );

        return $entity;
    }

    /**
     * @param string|float|null $val
     * @param bool $isNullAllowed
     * @return null|float
     * @throws \Throwable
     */
    public function getFloat($val, bool $isNullAllowed = true):? float
    {
        if ($val === null && $isNullAllowed === true) {
            return $val;
        }

        $valType = strtoupper(gettype($val));

        $val = $this->isFloat($val);

        if (!$val) {
            $this->logger->error('Not float value when trying to get float.');
            throw new \ErrorException('Expecting float value. ' . $valType . ' given.');
        }

        return $val;
    }

    /**
     * @param string|float $val
     * @return null|float
     */
    public function isFloat($val):? float
    {
        return StringHelper::isFloat($val);
    }

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
    public function setDouble($entity,
                              string $propertyPath,
                              string $propertyPathOverwrite = null,
                              $content = null,
                              bool $forceReturn = null,
                              bool $isNullAllowed = true
    )
    {
        $this->accessor->setValue(
            $entity,
            $propertyPath,
            $this->getDouble(
                $this->getFromJSON(
                    $propertyPathOverwrite ?: $propertyPath,
                    $content,
                    $forceReturn
                ),
                $isNullAllowed
            )
        );

        return $entity;
    }

    /**
     * @param string|float|null $val
     * @param bool $isNullAllowed
     * @return null|float
     * @throws \Throwable
     */
    public function getDouble($val, bool $isNullAllowed = true):? float
    {
        return $this->getWithDecimals($val, 2, $isNullAllowed);
    }

    /**
     * @param $val
     * @param int $decimals
     * @param bool $isNullAllowed
     * @return null|float
     * @throws \Throwable
     */
    public function getWithDecimals($val, int $decimals, bool $isNullAllowed = true):? float
    {

        if (!is_numeric($val)) {
            $this->logger->error('Not numeric value when trying to get with decimal.');
            throw new \ErrorException('Expecting numeric value. ' . strtoupper(gettype($val)) . ' given.');
        }

        return $this->getFloat(number_format($val, $decimals), $isNullAllowed);
    }

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
    public function setInt($entity,
                           string $propertyPath,
                           string $propertyPathOverwrite = null,
                           $content = null,
                           bool $forceReturn = null,
                           bool $isNullAllowed = true
    )
    {
        $this->accessor->setValue(
            $entity,
            $propertyPath,
            $this->getInt(
                $this->getFromJSON(
                    $propertyPathOverwrite ?: $propertyPath,
                    $content,
                    $forceReturn
                ),
                $isNullAllowed
            )
        );

        return $entity;
    }

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
    public function getInt($val, bool $isNullAllowed = true)
    {
        if ($val === null && $isNullAllowed === true) {
            return $val;
        }

        $valType = strtoupper(gettype($val));

        $val = $this->isInt($val);

        if (!$val) {
            $this->logger->error('Not integer value when trying to get integer.');
            throw new \ErrorException('Expecting integer value. ' . $valType . ' given.');
        }

        return $val;
    }

    /**
     * @param $val
     * @return null|int
     */
    public function isInt($val):? int
    {
        return StringHelper::isInt($val);
    }

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
    public function setOrUseDefault($entity,
                                    string $propertyPath,
                                    $defaultValue,
                                    string $propertyPathOverwrite = null,
                                    $content = null,
                                    bool $forceReturn = null
    )
    {
        $this->accessor->setValue(
            $entity,
            $propertyPath,
            $this->getFromJSON(
                $propertyPathOverwrite ?: $propertyPath,
                $content,
                $forceReturn
            ) ?: $defaultValue
        );

        return $entity;
    }

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
    public function setNumeric($entity,
                               string $propertyPath,
                               string $propertyPathOverwrite = null,
                               $content = null,
                               bool $forceReturn = null,
                               bool $isNullAllowed = true
    )
    {
        $this->accessor->setValue(
            $entity,
            $propertyPath,
            $this->getNumeric(
                $this->getFromJSON(
                    $propertyPathOverwrite ?: $propertyPath,
                    $content,
                    $forceReturn
                ),
                $isNullAllowed
            )
        );

        return $entity;
    }

    /**
     * @param array $propertyPaths
     * @param $entity
     * @param string|null $pathOverwritePrefix
     * @return mixed
     * @throws \Throwable
     */
    public function batchSetNumeric(array $propertyPaths,
                                    $entity,
                                    string $pathOverwritePrefix = null
    )
    {
        foreach ($propertyPaths as $propertyPath) {
            $this->setNumeric(
                $entity,
                $propertyPath,
                $this->gluePathOverwritePrefix(
                    $propertyPath,
                    $pathOverwritePrefix
                )
            );
        }

        return $entity;
    }

    /**
     * @param $val
     * @param bool $isNullAllowed
     * @return mixed
     * @throws \Throwable
     */
    public function getNumeric($val, bool $isNullAllowed = true)
    {
        if ($val === null && $isNullAllowed === true) {
            return $val;
        }

        $valType = strtoupper(gettype($val));

        if (!is_numeric($val)) {
            $this->logger->error('Not numeric value when trying to set numeric.');
            throw new \ErrorException('Expecting numeric value. ' . $valType . ' given.');
        }

        return $val;
    }

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
    public function setDate($entity,
                            string $propertyPath,
                            string $propertyPathOverwrite = null,
                            $content = null,
                            bool $forceReturn = null,
                            bool $isNullAllowed = true
    )
    {
        $this->accessor->setValue(
            $entity,
            $propertyPath,
            $this->getDate(
                $this->getFromJSON(
                    $propertyPathOverwrite ?: $propertyPath,
                    $content,
                    $forceReturn
                ),
                $isNullAllowed
            )
        );

        return $entity;
    }

    /**
     * @param array $propertyPaths
     * @param $entity
     * @param string|null $pathOverwritePrefix
     * @return mixed
     * @throws \Throwable
     */
    public function batchSetDate(array $propertyPaths,
                                 $entity,
                                 string $pathOverwritePrefix = null
    )
    {
        foreach ($propertyPaths as $propertyPath) {
            $this->setDate(
                $entity,
                $propertyPath,
                $this->gluePathOverwritePrefix(
                    $propertyPath,
                    $pathOverwritePrefix
                )
            );
        }

        return $entity;
    }

    /**
     * @param $val
     * @param bool $isNullAllowed
     * @return \DateTime|null
     * @throws \Throwable
     */
    public function getDate($val, $isNullAllowed = true):? \DateTime
    {
        if ($val === null && $isNullAllowed === true) {
            return $val;
        }

        $val = $this->isDateTime($val);

        if (!$val) {
            $this->logger->error('Not \DateTime() instance when trying to get date.');
            throw new \ErrorException('Expecting \DateTime() instance. ' . strtoupper(gettype($val)) . ' given.');
        }

        return $val;
    }

    /**
     * @param $val
     * @return \DateTime|null
     */
    public function isDateTime($val):? \DateTime
    {
        return StringHelper::isDateTime($val);
    }

    /**
     * This gets the current value of the propertyPath and sets its negation.
     * The value should be a boolean.
     *
     * @param $entity
     * @param string $propertyPath
     * @return mixed
     * @throws \Throwable
     */
    public function setNegation($entity, string $propertyPath)
    {
        $val = $this->getAccessor()->getValue($entity, $propertyPath);

        if (!is_bool($val) && $val !== null) {
            $this->logger->error('Negation can only be used on boolean type properties.');
            throw new \ErrorException('Negation can only be used on boolean type properties.');
        }

        $this->accessor->setValue(
            $entity,
            $propertyPath,
            $val === null ? $val : !$val
        );

        return $entity;
    }

    /**
     * @param $entity
     * @param string $propertyPath
     * @param $objectOrArray
     * @param string|null $propertyPathOverwrite
     * @return mixed
     * @throws \Throwable
     */
    public function setBoolIfObjectOrArrayHas($entity,
                                              string $propertyPath,
                                              $objectOrArray,
                                              string $propertyPathOverwrite = null
    )
    {
        $this->accessor->setValue(
            $entity,
            $propertyPath,
            $this->getAccessor()->isReadable(
                $objectOrArray,
                $propertyPathOverwrite ?: $propertyPath
            )
        );

        return $entity;
    }

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
    public function setBool($entity,
                            string $propertyPath,
                            string $propertyPathOverwrite = null,
                            $content = null,
                            bool $forceReturn = null
    )
    {
        $val = $this->getFromJSON(
            $propertyPathOverwrite ?: $propertyPath,
            $content,
            $forceReturn
        );

        if ($val !== null) {
            $val = filter_var($val, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        }

        $this->accessor->setValue(
            $entity,
            $propertyPath,
            $val
        );

        return $entity;
    }

    /**
     * @param array $propertyPaths
     * @param $entity
     * @param string|null $pathOverwritePrefix
     * @throws \Throwable
     */
    public function batchSetBool(array $propertyPaths,
                                 $entity,
                                 string $pathOverwritePrefix = null
    )
    {
        foreach ($propertyPaths as $propertyPath) {
            $this->setBool(
                $entity,
                $propertyPath,
                $this->gluePathOverwritePrefix(
                    $propertyPath,
                    $pathOverwritePrefix
                )
            );
        }
    }

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
    public function setMarkdown($entity,
                                string $propertyPath,
                                int $length = null,
                                string $propertyPathOverwrite = null,
                                $content = null,
                                bool $forceReturn = null
    )
    {
        // this will set propertyPathMarkdown, propertyPathHTML & propertyPathShort
        $this->setMarkdownRaw(
            $entity,
            $propertyPath,
            $propertyPathOverwrite,
            $content,
            $forceReturn
        );
        $propertyPath = str_replace('Markdown', 'HTML', $propertyPath);
        if ($propertyPathOverwrite) {
            $propertyPathOverwrite = str_replace('Markdown', 'HTML', $propertyPathOverwrite);
        }
        $this->setMarkdownHTML(
            $entity,
            $propertyPath,
            $propertyPathOverwrite,
            $content,
            $forceReturn
        );
        $propertyPath = str_replace('HTML', 'Short', $propertyPath);
        if ($propertyPathOverwrite) {
            $propertyPathOverwrite = str_replace('HTML', 'Short', $propertyPathOverwrite);
        }
        $this->setMarkdownShort(
            $entity,
            $propertyPath,
            $length,
            $propertyPathOverwrite,
            $content,
            $forceReturn
        );

        return $entity;
    }

    /**
     * @param $entity
     * @param string $propertyPath
     * @param string|null $propertyPathOverwrite
     * @param mixed $content
     * @param bool|null $forceReturn
     * @return mixed
     * @throws \Throwable
     */
    public function setMarkdownRaw($entity,
                                   string $propertyPath,
                                   string $propertyPathOverwrite = null,
                                   $content = null,
                                   bool $forceReturn = null
    )
    {
        $this->accessor->setValue(
            $entity,
            $propertyPath,
            $this->getFromJSON(
                $propertyPathOverwrite ?: $propertyPath,
                $content,
                $forceReturn
            )
        );

        return $entity;
    }

    /**
     * @param $entity
     * @param string $propertyPath
     * @param string|null $propertyPathOverwrite
     * @param mixed $content
     * @param bool|null $forceReturn
     * @return mixed
     * @throws \Throwable
     */
    public function setMarkdownHTML($entity,
                                    string $propertyPath,
                                    string $propertyPathOverwrite = null,
                                    $content = null,
                                    bool $forceReturn = null
    )
    {
        $val = $this->getFromJSON(
            $propertyPathOverwrite ?: $propertyPath,
            $content,
            $forceReturn
        );

        $this->accessor->setValue(
            $entity,
            $propertyPath,
            StringHelper::parsedownExtra($val)
        );

        return $entity;
    }

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
    public function setMarkdownShort($entity,
                                     string $propertyPath,
                                     int $length = null,
                                     string $propertyPathOverwrite = null,
                                     $content = null,
                                     bool $forceReturn = null
    )
    {
        $val = $this->getFromJSON(
            $propertyPathOverwrite ?: $propertyPath,
            $content,
            $forceReturn
        );

        $this->accessor->setValue(
            $entity,
            $propertyPath,
            StringHelper::shortenThis($val, $length)
        );

        return $entity;
    }

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
     * @param mixed $owningEntity $product
     * @param string $owningPropertyPath $product->shipment
     * @param string $repository ShipmentRepository
     * @param string $owningPropertyPathOverwrite
     * @param mixed $content
     * @param bool|null $forceReturn
     * @return mixed
     * @throws \Throwable
     */
    public function setOneToOneUnidirectional($owningEntity,
                                              string $owningPropertyPath,
                                              string $repository,
                                              string $owningPropertyPathOverwrite = null,
                                              $content = null,
                                              bool $forceReturn = null
    )
    {
        if ($content && $content instanceof $repository) {
            $inverseEntity = $content;
        } else {
            $inverseEntity = $this->getOneById(
                $owningPropertyPathOverwrite ?: $owningPropertyPath,
                $repository,
                $content,
                $forceReturn
            );
        }
        // sets object or null
        $this->accessor->setValue(
            $owningEntity,
            $owningPropertyPath,
            $inverseEntity
        );

        return $owningEntity;
    }

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
     * @param mixed $owningEntity $user
     * @param string $owningPropertyPath $user->address
     * @param string $repository AddressRepository
     * @param string $owningPropertyPathOverwrite
     * @param mixed $content
     * @param bool|null $forceReturn
     * @return mixed
     * @throws \Throwable
     */
    public function setManyToOneUnidirectional($owningEntity,
                                               string $owningPropertyPath,
                                               string $repository,
                                               string $owningPropertyPathOverwrite = null,
                                               $content = null,
                                               bool $forceReturn = null
    )
    {
        // sets object or null
        return $this->setOneToOneUnidirectional(
            $owningEntity,
            $owningPropertyPath,
            $repository,
            $owningPropertyPathOverwrite,
            $content,
            $forceReturn
        );
    }

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
     * $cart = $this->getOneById('cart.id' ?: 'cart',
     *                           CartRepository,
     *                           $content,
     *                           $forceReturn
     *                          );
     *
     * @param mixed $inverseEntity $customer
     * @param string $inversePropertyPath $customer->cart
     * @param mixed|null $owningEntity $cart
     * @param string $owningSidePropertyPath $cart->customer
     * @return mixed
     * @throws \Throwable
     */
    public function inverseSideSetsOneToOneBidirectional($inverseEntity,
                                                         string $inversePropertyPath,
                                                         $owningEntity = null,
                                                         string $owningSidePropertyPath
    )
    {
        /**
         * If the inverse had an owner.
         * Presuming null is allowed (else MySQL will generate an error).
         * Detach the inverse from the old owner.
         *
         * @var mixed|null $oldOwningEntity
         */
        $oldOwningEntity = $this->getAccessor()->getValue($inverseEntity, $inversePropertyPath);
        if ($oldOwningEntity) {
            $this->accessor->setValue($oldOwningEntity, $owningSidePropertyPath, null);
        }

        if ($owningEntity) {
            /**
             * If the new owner has an inverse.
             * Let's make this OLD inverse aware of the changes.
             *
             * @var mixed|null $oldInverseEntity
             */
            $oldInverseEntity = $this->accessor->getValue($owningEntity, $owningSidePropertyPath);
            if ($oldInverseEntity) {
                $this->accessor->setValue($oldInverseEntity, $inversePropertyPath, null);
            }

            /**
             * Doctrine will only check the owning side of an association for changes.
             */
            $this->accessor->setValue($owningEntity, $owningSidePropertyPath, $inverseEntity);
        }

        /**
         * Let's make the inverse side aware about the changes that happens
         *
         * Sets object or null
         */
        $this->accessor->setValue($inverseEntity, $inversePropertyPath, $owningEntity);

        return $inverseEntity;
    }

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
     * $customer = $this->getOneById('customer.id' ?: 'customer',
     *                               CustomerRepository,
     *                               $content,
     *                               $forceReturn
     *                              );
     *
     * @param mixed $owningEntity $cart
     * @param string $owningPropertyPath $cart->customer
     * @param mixed|null $inverseEntity
     * @param string $inverseSidePropertyPath $customer->cart
     * @return mixed
     * @throws \Throwable
     */
    public function owningSideSetsOneToOneBidirectional($owningEntity,
                                                        string $owningPropertyPath,
                                                        $inverseEntity = null,
                                                        string $inverseSidePropertyPath
    )
    {
        /**
         * If the owner has an inverse.
         * Let's make this OLD inverse aware of the changes.
         */
        $oldInverseEntity = $this->accessor->getValue($owningEntity, $owningPropertyPath);
        if ($oldInverseEntity) {
            $this->accessor->setValue($oldInverseEntity, $inverseSidePropertyPath, null);
        }

        if ($inverseEntity) {
            /**
             * Let's make the inverse side aware about the changes that happens
             */
            $this->accessor->setValue($inverseEntity, $inverseSidePropertyPath, $owningEntity);
        }

        $this->accessor->setValue($owningEntity, $owningPropertyPath, $inverseEntity);

        return $owningEntity;
    }

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
     * $feature = $this->getOneById('feature.id' ?: 'feature',
     *                              FeatureRepository,
     *                              $content,
     *                              $forceReturn
     *                             );
     *
     * @param mixed $inverseEntity $product
     * @param string $inverseAdder $product->addFeature()
     * @param string $inverseRemover $product->removeFeature()
     * @param mixed $owningEntity $feature
     * @param string $owningSidePropertyPath $feature->product
     * @param string $instanceOf
     * @return mixed
     * @throws \Throwable
     */
    public function inverseSideAddsOneToManyBidirectional($inverseEntity,
                                                          string $inverseAdder,
                                                          string $inverseRemover,
                                                          $owningEntity,
                                                          string $owningSidePropertyPath,
                                                          string $instanceOf
    )
    {
        if (!$owningEntity instanceof $instanceOf) {
            throw new \ErrorException(
                'Expecting Owning Entity when adding OneToMany, Bidirectional. None provided.'
            );
        }

        /**
         * If the owner has an inverse.
         * Let's make this OLD inverse aware of the changes.
         *
         * @var mixed|null $oldInverseEntity
         */
        $oldInverseEntity = $this->accessor->getValue($owningEntity, $owningSidePropertyPath);
        if ($oldInverseEntity) {
            $oldInverseEntity->{$inverseRemover}($owningEntity);
        }

        /**
         * Because Doctrine will only check the owning side of an association for changes.
         */
        $this->accessor->setValue($owningEntity, $owningSidePropertyPath, $inverseEntity);

        /**
         * Let's make the inverse side aware about the changes that happens
         */
        $inverseEntity->{$inverseAdder}($owningEntity);

        return $inverseEntity;
    }

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
     * @param mixed $inverseEntity $product
     * @param string $inversePropertyPath $product->features
     * @param string $inverseRemover $product->removeFeature()
     * @param array $owningEntities $features
     * @param string $owningSidePropertyPath $feature->product
     * @return mixed
     * @throws \Throwable
     */
    public function inverseSideSetsOneToManyBidirectional($inverseEntity,
                                                          string $inversePropertyPath,
                                                          string $inverseRemover,
                                                          array $owningEntities = [],
                                                          string $owningSidePropertyPath
    )
    {
        /**
         * Let's make the OLD owning entities aware about the changes that happens.
         * This are now orphans.
         */
        foreach ($this->getAccessor()->getValue(
            $inverseEntity, $inversePropertyPath) ?: [] as $oldOwningEntity) {
            $this->accessor->setValue(
                $oldOwningEntity,
                $owningSidePropertyPath,
                null
            );
        }

        // make the owning side aware of the changes
        foreach ($owningEntities as $owningEntity) {
            /**
             * If the NEW owners have an inverse.
             * Let's make this OLD inverse aware of the changes.
             *
             * @var mixed|null $oldInverseEntity
             */
            $oldInverseEntity = $this->getAccessor()->getValue(
                $owningEntity,
                $owningSidePropertyPath
            );
            if ($oldInverseEntity) {
                $oldInverseEntity->{$inverseRemover}($owningEntity);
            }

            /**
             * Because Doctrine will only check the owning side of an association for changes.
             */
            $this->accessor->setValue(
                $owningEntity,
                $owningSidePropertyPath,
                $inverseEntity
            );
        }

        // set the associations
        $this->accessor->setValue(
            $inverseEntity,
            $inversePropertyPath,
            $owningEntities
        );


        return $inverseEntity;
    }

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
     * $product = $this->getOneById('product.id' ?: 'product',
     *                              ProductRepository,
     *                              $content,
     *                              $forceReturn
     *                             );
     *
     * @param mixed $owningEntity $feature
     * @param string $owningPropertyPath $feature->product
     * @param mixed|null $inverseEntity $product
     * @param string $inverseAdder $product->addFeature()
     * @param string $inverseRemover $product->removeFeature()
     * @return mixed
     * @throws \Throwable
     */
    public function owningSideSetsOneToManyBidirectional($owningEntity,
                                                         string $owningPropertyPath,
                                                         $inverseEntity = null,
                                                         string $inverseAdder,
                                                         string $inverseRemover
    )
    {
        /**
         * If the owning entity has an inverse.
         * Let's make this OLD inverse aware of the change.
         *
         * @var mixed|null $oldInverseEntity
         */
        $oldInverseEntity = $this->getAccessor()->getValue($owningEntity, $owningPropertyPath);
        if ($oldInverseEntity) {
            $oldInverseEntity->{$inverseRemover}($owningEntity);
        }

        if ($inverseEntity) {
            // make the inverse side aware of the change
            $inverseEntity->{$inverseAdder}($owningEntity);
        }

        // set the association
        $this->getAccessor()->setValue($owningEntity, $owningPropertyPath, $inverseEntity);

        return $owningEntity;
    }

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
     * $phoneNumber = $this->getOneById('phoneNumber.id' ?: 'phoneNumber',
     *                                  PhoneRepository,
     *                                  $content,
     *                                  $forceReturn
     *                                 );
     *
     * @param $inverseEntity $user
     * @param string $inverseAdder $user->addPhoneNumber()
     * @param mixed $otherSideEntity $phoneNumber
     * @param string $instanceOf
     * @return mixed
     * @throws \Throwable
     */
    public function addOneToManyUnidirectional($inverseEntity,
                                               string $inverseAdder,
                                               $otherSideEntity,
                                               string $instanceOf
    )
    {
        if (!$otherSideEntity instanceof $instanceOf) {
            throw new \ErrorException(
                'Expecting Other Side Entity when adding OneToMany, Unidirectional. None provided.'
            );
        }

        $inverseEntity->{$inverseAdder}($otherSideEntity);

        return $inverseEntity;
    }

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
    public function setOneToManyUnidirectional($inverseEntity,
                                               string $inversePropertyPath,
                                               array $otherSideEntities)
    {
        // set the associations
        $this->accessor->setValue(
            $inverseEntity,
            $inversePropertyPath,
            $otherSideEntities
        );

        return $inverseEntity;
    }

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
     * $group = $this->getOneById('group.id' ?: 'group',
     *                            GroupRepository,
     *                            $content,
     *                            $forceReturn
     *                           );
     *
     * @param mixed $thisSideEntity $user
     * @param string $thisSideAdder $user->addGroup()
     * @param mixed $otherSideEntity $group
     * @param string $otherSideAdder $group->addUser()
     * @param string $instanceOf
     * @return mixed
     * @throws \Throwable
     */
    public function addManyToManyBidirectional($thisSideEntity,
                                               string $thisSideAdder,
                                               $otherSideEntity,
                                               string $otherSideAdder,
                                               string $instanceOf
    )
    {
        if (!$otherSideEntity instanceof $instanceOf) {
            throw new \ErrorException(
                'Expecting Other Side Entity when adding ManyToMany, Bidirectional. None provided.');
        }

        $thisSideEntity->{$thisSideAdder}($otherSideEntity);
        $otherSideEntity->{$otherSideAdder}($thisSideEntity);

        return $thisSideEntity;
    }

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
     * @param mixed $thisSideEntity $user
     * @param string $thisSidePropertyPath $user->groups
     * @param array $otherSideEntities $groups
     * @param string $otherSideRemover $group->removeUser()
     * @param string $otherSideAdder $group->addUser()
     * @return mixed
     * @throws \Throwable
     */
    public function setManyToManyBidirectional($thisSideEntity,
                                               string $thisSidePropertyPath,
                                               array $otherSideEntities = [],
                                               string $otherSideRemover,
                                               string $otherSideAdder
    )
    {
        /**
         * Let's make any OLD associations aware of the change
         */
        $oldOtherSideEntities = $this->getAccessor()->getValue($thisSideEntity, $thisSidePropertyPath);
        foreach ($oldOtherSideEntities ?: [] as $oldOtherSideEntity) {
            $oldOtherSideEntity->{$otherSideRemover}($thisSideEntity);
        }

        // set the associations on this side
        $this->accessor->setValue(
            $thisSideEntity,
            $thisSidePropertyPath,
            $otherSideEntities
        );

        // let make aware the other side about the update
        foreach ($otherSideEntities as $otherSideEntity) {
            $otherSideEntity->{$otherSideAdder}($thisSideEntity);
        }

        return $thisSideEntity;
    }

    /**
     * @param mixed $thisSideEntity $user|$product
     * @param string $thisSideRemover $user->removeGroup()|$product->removeFeature()
     * @param mixed|null $otherSideEntity $group|$feature
     * @param string|null $otherSideRemover $group->removeUser()
     * @param string|null $otherSidePropertyPath $feature->product
     * @return mixed
     * @throws \Throwable
     */
    public function removeAssociation($thisSideEntity,
                                      string $thisSideRemover,
                                      $otherSideEntity = null,
                                      string $otherSideRemover = null,
                                      string $otherSidePropertyPath = null
    )
    {
        if (!$otherSideRemover && !$otherSidePropertyPath) {
            throw new \ErrorException(
                'Unable to remove. Missing both Other Side Remover and Other Side Property Path'
            );
        }

        if ($otherSideRemover && $otherSidePropertyPath) {
            throw new \ErrorException(
                'Ambiguous operation. There is no way to differentiate between OneToMany and ManyToMany.'
            );
        }

        if (!$otherSideEntity) {
            throw new \ErrorException('Unable to continue. You have to provide an Other Side Entity. NULL received.');
        }

        // remove the association
        $thisSideEntity->{$thisSideRemover}($otherSideEntity);

        // in case ManyToMany expect $otherSideRemover
        // make the Other Side aware of the change
        if ($otherSideRemover) {
            $otherSideEntity->{$otherSideRemover}($thisSideEntity);

            return $thisSideEntity;
        }

        // in case OneToMany expect $otherSidePropertyPath
        // make the Other Side aware of the change
        if ($otherSidePropertyPath) {
            $this->accessor->setValue($otherSideEntity, $otherSidePropertyPath, null);
        }

        return $thisSideEntity;
    }
}