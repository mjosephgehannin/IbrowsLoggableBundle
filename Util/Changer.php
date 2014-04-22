<?php
/**
 * Created by iBROWS AG.
 * User: marcsteiner
 * Date: 27.02.14
 * Time: 15:52
 */

namespace Ibrows\LoggableBundle\Util;


use Doctrine\Common\Persistence\ObjectManager;
use Ibrows\LoggableBundle\Repository\ChangeSetRepository;
use Ibrows\LoggableBundle\Repository\LogRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class Changer
 * @package Ibrows\LoggableBundle\Util
 */
class Changer
{

    /**
     * @var LoggerInterface
     */
    protected $logger;
    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * @var \Doctrine\Common\Persistence\ObjectManager
     */
    protected $manager;

    /**
     * @var
     */
    protected $logEntityClass;

    /**
     * @var Logger
     */
    protected $loggableLogger;

    /**
     * @var
     */
    protected $changeEntityClass;

    /**
     * @var bool
     */
    protected $catchExceptions = true;
    /**
     * @var int
     */
    protected $processed;
    /**
     * @var int
     */
    protected $failed;

    /**
     * @param ObjectManager $manager
     * @param Logger $logger
     * @param string $changeEntityClass
     */
    public function __construct(ObjectManager $manager,Logger $logger, $changeEntityClass)
    {
        $this->manager = $manager;
        $this->loggableLogger = $logger;
        $this->changeEntityClass = $changeEntityClass;
    }

    /**
     * @return int
     */
    public function getProcessed()
    {
        return $this->processed;
    }

    /**
     * @return int
     */
    public function getFailed()
    {
        return $this->failed;
    }



    /**
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function setLogger($logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param \Ibrows\LoggableBundle\Util\Changer $output
     */
    public function setOutput($output)
    {
        $this->output = $output;
    }

    /**
     * @param boolean $catchExceptions
     */
    public function setCatchExceptions($catchExceptions)
    {
        $this->catchExceptions = $catchExceptions;
    }

    /**
     * @param $now
     * @throws \Exception
     */
    public function applyChanges($now = null)
    {
        if (!$now) {
            $now = new \DateTime();
        }
        $loggableListener = $this->getLogRepo()->getLoggableListener();

        $this->processed = 0;
        $this->failed = 0;
        $changes = $this->getRepo()->findAllByChangeAt($now);
        foreach ($changes as $change) {
            $class = $change->getObjectClass();
            $id = $change->getObjectId();
            $cid = $change->getId();
            $loggableListener->setUsername($change->getUsername());
            try {
                $changeObject = $this->getRepo()->applyChangeSet($change);
            } catch (\Exception $e) {
                $this->writeln("Something went wrong:{$e->getMessage()}, {$e->getCode()}", 'error');
                if (!$this->catchExceptions) {
                    throw $e;
                }
                $changeObject = false;
            }

            if (!$changeObject) {
                $this->writeln("Cant apply Changeset:$cid with Object:$class ($id)", 'error');
                $this->failed++;
            } else {
                $this->writeln("Apply Changeset:$cid with Object:$class ($id)", 'info');
                $this->processed++;
            }
        }
        $this->manager->flush();
        $this->getRepo()->clear();
        $this->writeln("Processed {$this->processed} changes, failed {$this->failed}", 'info');
    }

    /**
     * @return  LogRepository
     */
    protected function getLogRepo()
    {
        return $this->loggableLogger->getLogRepo();
    }


    /**
     * @return  ChangeSetRepository
     */
    public function getRepo()
    {
        return $this->manager->getRepository($this->changeEntityClass);
    }


    /**
     * @param string $log
     * @param string $type
     */
    protected function writeln($log, $type = 'info')
    {
        if ($this->output) {
            if ($this->output->getVerbosity() > 1 || $type == 'error') {
                $this->output->writeln("<$type>$log</$type>");
            }
        }
        if ($this->logger) {
            if ($type == 'error') {
                $this->logger->error($log);
            } else {
                $this->logger->debug($log);
            }
        }
    }


}