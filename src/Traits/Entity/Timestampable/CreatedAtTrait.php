<?php

namespace Mindlahus\Traits\Entity\Timestampable;

use Gedmo\Mapping\Annotation as Gedmo;
use JMS\Serializer\Annotation as Serializer;

trait CreatedAtTrait
{
    /**
     * @var \DateTime
     *
     * @Serializer\Expose()
     * @Serializer\Groups({"created-at", "timestampable", "blameable-timestampable"})
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(name="created_at", type="datetime")
     */
    private $createdAt;

    /**
     * Get createdAt
     *
     * @return \DateTime|null|string
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     *
     * @return $this
     */
    public function setCreatedAt(\DateTime $createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }
}