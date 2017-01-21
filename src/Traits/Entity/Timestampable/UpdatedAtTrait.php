<?php

namespace Mindlahus\Traits\Entity\Timestampable;

use Gedmo\Mapping\Annotation as Gedmo;
use JMS\Serializer\Annotation as Serializer;

trait UpdatedAtTrait
{
    /**
     * @var \DateTime
     *
     * @Serializer\Expose()
     * @Serializer\Groups({"updated-at", "timestampable", "blameable-timestampable"})
     *
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(name="updated_at", type="datetime")
     */
    private $updatedAt;

    /**
     * Get updatedAt
     *
     * @return \DateTime|null|string
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * Set updatedAt
     *
     * @param \DateTime $updatedAt
     *
     * @return $this
     */
    public function setUpdatedAt(\DateTime $updatedAt)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }
}