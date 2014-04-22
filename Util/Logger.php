<?php
/**
 * Created by iBROWS AG.
 * User: marcsteiner
 * Date: 27.02.14
 * Time: 15:52
 */

namespace Ibrows\LoggableBundle\Util;


use Doctrine\Common\Persistence\ObjectManager;
use Ibrows\LoggableBundle\Entity\Log;
use Ibrows\LoggableBundle\Entity\LogMany2Many;
use Ibrows\LoggableBundle\Entity\LogParent;
use Ibrows\LoggableBundle\Listener\LoggableListener;
use Ibrows\LoggableBundle\Repository\ChangeSetRepository;
use Ibrows\LoggableBundle\Repository\LogRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Validator\Constraints\DateTime;

/**
 * Class Changer
 * @package Ibrows\LoggableBundle\Util
 */
class Logger
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
     * @var LoggableListener
     */
    protected $listener;


    /**
     *
     */
    public function __construct(ObjectManager $manager, LoggableListener $listener)
    {
        $this->manager = $manager;
        $this->listener = $listener;
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

    public function undo($object,$steps=1){
        --$steps;
        $logs = $this->getLogRepo()->getLogsByObject($object,$steps,1);
        if(sizeof($logs)==0){
            return false;
        }
        $log = array_pop($logs);
        return $this->revertByLog($log);
    }


    public function revertByLog(Log $log){
        return $this->getLogRepo()->revertTo($log);
    }


    /**
     * @return  LogRepository
     */
    public function getLogRepo()
    {
        return $this->manager->getRepository($this->listener->getDefaultLogEntryClass());
    }


    /**
     * @return  LogParent
     */
    public function getLogParentRepo()
    {
        return $this->manager->getRepository($this->listener->getLogParentEntryClass());
    }


    /**
     * @return  LogMany2Many
     */
    public function getLogMany2ManyRepo()
    {
        return $this->manager->getRepository($this->listener->getLogCollectionEntryClass());
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