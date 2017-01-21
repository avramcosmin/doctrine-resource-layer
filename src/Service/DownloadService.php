<?php

namespace Mindlahus\Service;

use Mindlahus\AbstractInterface\DownloadAbstract;
use Symfony\Component\DependencyInjection\ContainerInterface;

class DownloadService extends DownloadAbstract
{
    public $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param string $path
     * @param string|null $name
     * @param bool $deleteOnCompleted
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function execute(string $path, string $name = null, $deleteOnCompleted = true)
    {
        return parent::execute($path, $name, $deleteOnCompleted);
    }

    /**
     * @param string $path
     * @param string $octetStream
     * @return string
     */
    public function octetStreamToTmp(string $path, string $octetStream)
    {
        return parent::octetStreamToTmp($path, $octetStream);
    }

    /**
     * @param string|null $type
     * @param bool|null $flip
     * @return array|mixed|null
     */
    public function _getMimeType(string $type = null, bool $flip = null)
    {
        return parent::_getMimeType($type, $flip);
    }
}