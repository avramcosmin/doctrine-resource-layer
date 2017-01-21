<?php

namespace Mindlahus\Traits;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Component\Stopwatch\StopwatchEvent;
use Symfony\Component\Validator\ConstraintViolation;

trait CommandTrait
{
    /**
     * @var ContainerInterface $container
     */
    private $container;
    /**
     * @var EntityManagerInterface $em
     */
    private $em;
    /**
     * @var Logger $logger
     */
    private $logger;

    /**
     *
     */
    private function _init()
    {
        $this->container = $this->getContainer();
        $this->em = $this->container->get('doctrine.orm.entity_manager');
        $this->logger = $this->container->get('monolog.logger.onew');
    }

    /**
     * @param ProgressBar $progressBar
     * @param int $step
     * @return ProgressBar
     */
    private function _advanceProgressBar(ProgressBar $progressBar, $step = 1)
    {
        $progressBar->advance($step);

        return $progressBar;
    }

    /**
     * @param string $msg
     */
    private function _log(string $msg)
    {
        $this->logger->error($msg);
    }

    /**
     * @param OutputInterface $output
     * @param $msg
     * @return OutputInterface
     */
    private function _writeError(OutputInterface $output, $msg)
    {
        return $this->_write($output, $msg, 'red');
    }

    /**
     * @param OutputInterface $output
     * @param $msg
     * @return OutputInterface
     */
    private function _writeInfo(OutputInterface $output, $msg)
    {
        return $this->_write($output, $msg, 'green');
    }

    /**
     * @param OutputInterface $output
     * @param $msg
     * @param string $fg
     * @return OutputInterface
     */
    private function _write(OutputInterface $output, $msg, $fg = 'black')
    {
        if (!is_array($msg)) {
            $msg = [$msg];
        }

        foreach ($msg as $line) {
            $output->writeln('<fg=' . $fg . ';options=bold>' . $line . '</>');
        }

        return $output;
    }

    /**
     * @param OutputInterface $output
     * @param StopwatchEvent $event
     */
    private function _writeDebugInfo(OutputInterface $output, StopwatchEvent $event)
    {
        $memory = number_format($event->getMemory() / 1000000, 2);
        $time = $event->getDuration() / 1000;
        $this->_write($output, "({$time}s / {$memory}M)");
    }

    /**
     * @param $entity
     * @return bool
     */
    private function _validate($entity)
    {
        $validator = $this->container->get('validator');
        $errors = $validator->validate($entity);
        if (count($errors) > 0) {
            /**
             * @var ConstraintViolation $error
             */
            foreach ($errors as $error) {
                $this->logger->error(
                    $error->getPropertyPath() . ' >>> ' . $error->getMessage(),
                    ["onew"]
                );
            }
            return false;
        }

        return true;
    }

    /**
     * @param OutputInterface $output
     * @param string|null $repository
     * @return bool
     */
    private function _handlePersist(OutputInterface $output, string $repository = null)
    {
        if (!$repository) {
            return false;
        }

        $this->_executeReconciliation(
            $output,
            $repository,
            '_callback'
        );

        return true;
    }

    /**
     * $options = [
     *  entities            optional    array
     *  total               optional    integer
     *  existsCallback      optional    string      returns boolean
     *  persistCallback     optional    boolean     returns entity to be persisted
     *  usingIterate        optional    boolean
     * ]
     *
     * @param OutputInterface $output
     * @param string $repository
     * @param string $callback
     * @param array $options
     */
    private function _executeReconciliation(OutputInterface $output, string $repository, string $callback, array $options = [])
    {
        gc_enable();
        $this->_init();

        try {
            if (!($options['entities'] ?? null)) {
                $options['entities'] = $this->em
                    ->createQuery("SELECT t FROM {$repository} t")
                    ->iterate();
            }
            if (!($options['total'] ?? null)) {
                $options['total'] = $this->em
                    ->createQuery("SELECT COUNT(t) FROM {$repository} t")
                    ->getSingleScalarResult();
            }

            if ($options['entities']) {
                $this->_write($output, "Working on {$repository}");
                $progressBar = new ProgressBar($output, $options['total']);
                $progressBar->start();
                $count = 0;
                $stopwatch = new Stopwatch();
                $stopwatch->start($repository);
                foreach ($options['entities'] as $entity) {
                    if (($options['usingIterate'] ?? true) === true) {
                        $entity = $entity[0];
                    }
                    if (($options['existsCallback'] ?? null) && $this->{$options['existsCallback']}($output, $entity)) {
                        continue;
                    }
                    $this->{$callback}($entity);
                    if ($options['persistCallback'] ?? null) {
                        $entity = $this->{$options['persistCallback']}($entity);
                        $this->em->persist($entity);
                    }
                    if (!$this->_validate($entity)) {
                        continue;
                    }
                    if ($count % 15 === 0) {
                        $this->_advanceProgressBar($progressBar, 15);
                        $this->em->flush();
                        if (($options['usingIterate'] ?? true) === true) {
                            $this->em->clear();
                        }
                        gc_collect_cycles();
                    }
                    $count++;
                }
                $this->em->flush();
                $this->em->clear();
                gc_collect_cycles();
                $progressBar->finish();
                $event = $stopwatch->stop($repository);
                $this->_writeInfo($output, "\n\r{$repository} successfully reconciled. Thank you!");
                $this->_writeDebugInfo($output, $event);
            } else {
                $this->_write($output, "Nothing to reconcile.");
            }
        } catch (\Throwable $e) {
            $this->_writeError($output, "\n\r" . $e->getMessage());
        }
    }
}