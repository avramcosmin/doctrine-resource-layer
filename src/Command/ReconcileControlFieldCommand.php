<?php

namespace Mindlahus\Command;

use Mindlahus\AbstractInterface\ControlFieldInterface;
use Mindlahus\Traits\CommandTrait;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ReconcileControlFieldCommand extends ContainerAwareCommand
{
    use CommandTrait;

    protected $repositories = [];

    protected function configure()
    {
        $this
            ->setName('mindlahus:v2:reconcile:control-field')
            ->setDescription('Reconciles the control fields from the database.')
            ->addArgument('repository', InputArgument::OPTIONAL, 'The entity of witch control field you want to reconcile.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!$this->_handlePersist($output, $input->getArgument('repository'))) {
            foreach ($this->repositories as $repository) {
                $this->_handlePersist($output, $repository);
            }
        }
    }

    /**
     * @param ControlFieldInterface $entity
     * @return ControlFieldInterface
     */
    private function _callback(ControlFieldInterface $entity)
    {
        $entity->setControlField();

        return $entity;
    }
}
