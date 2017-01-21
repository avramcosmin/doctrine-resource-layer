<?php

namespace Mindlahus\Traits\EntityResource;

use Mindlahus\Service\ResourceService;

trait AbstractEntityResourceTrait
{
    /**
     * @var ResourceService
     */
    protected $resourceService;

    public function __construct(ResourceService $resourceService)
    {
        $this->resourceService = $resourceService;
    }

    /**
     * todo : make this abstract and create new instance of resource inside EntityResource class
     *
     * @param $entity
     * @return mixed
     */
    public function create($entity)
    {
        $this->_self($entity);

        return $entity;
    }

    /**
     * todo : make this abstract and get the entity from the database by ID using resourceService _get()
     *
     * @param $entity
     * @return mixed
     */
    public function change($entity)
    {
        $this->_self($entity);

        return $entity;
    }

    public function delete($entity)
    {
        $this->resourceService->entityManager->remove($entity);
        $this->resourceService->entityManager->flush();
    }

    abstract public function _self($entity);

}