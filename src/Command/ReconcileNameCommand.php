<?php

namespace Mindlahus\Command;

use Mindlahus\AbstractInterface\NameInterface;
use Mindlahus\Traits\CommandTrait;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ReconcileNameCommand extends ContainerAwareCommand
{
    use CommandTrait;

    protected $repositories = [];

    protected function configure()
    {
        $this
            ->setName('mindlahus:v2:reconcile:name')
            ->setDescription('Reconciles First Last & Last First names from the database.')
            ->addArgument('repository', InputArgument::OPTIONAL, 'The entity of witch First & Last name combination you want to reconcile.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->_init();

        if (!$this->_handlePersist($output, $input->getArgument('repository'))) {
            foreach ($this->repositories as $repository) {
                $this->_handlePersist($output, $repository);
            }
        }
    }

    /**
     * @param NameInterface $entity
     * @return NameInterface
     */
    private function _callback(NameInterface $entity)
    {
        $entity->setFirstLastName();
        $entity->setLastFirstName();

        return $entity;
    }
}
