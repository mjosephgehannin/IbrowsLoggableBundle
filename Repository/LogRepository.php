<?php

namespace Ibrows\LoggableBundle\Repository;

use DateTime;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Gedmo\SoftDeleteable\SoftDeleteableListener;
use Gedmo\Tool\Wrapper\AbstractWrapper;
use Gedmo\Tool\Wrapper\EntityWrapper;
use Ibrows\LoggableBundle\Entity\Log;
use Ibrows\LoggableBundle\Listener\LoggableListener;

class LogRepository extends EntityRepository
{

    protected $searchChild = true;
    protected $searchOnlyChild = false;
    protected $forcePartialLoad = false;

    public function getLogsByObject($object, $offset = null, $limit = null)
    {
        $metaData = $this->getClassAndId($object);

        return $this->getLogsByClassId($metaData['class'], $metaData['id'], $offset, $limit);
    }

    protected function getClassAndId($object)
    {
        $wrapped = AbstractWrapper::wrap($object, $this->getEntityManager());
        $id = $wrapped->getIdentifier();
        $class = $wrapped->getRootObjectName();

        return array('id' => $id, 'class' => $class);
    }

    public function getLogsByClassId($class, $id, $offset = null, $limit = null)
    {
        $logs = $this->getLogs(array($class => array($id)), $offset, $limit);

        return $logs;
    }

    public function getLogs(array $classesids, $offset = null, $limit = null)
    {
        $qb = $this->getQB($classesids);
        $qb->setMaxResults($limit);
        $qb->setFirstResult($offset);
        $q = $qb->getQuery();
        if ($this->forcePartialLoad) {
            $q->setHint(Query::HINT_FORCE_PARTIAL_LOAD, $this->forcePartialLoad);
        }
        $logs = $q->getResult(AbstractQuery::HYDRATE_OBJECT);

        return $this->prepare($logs);
    }

    /**
     * @param array $classesids
     * @return QueryBuilder
     * @throws \Exception
     */
    protected function getQB(array $classesids)
    {
        if (!$this->searchChild && $this->searchOnlyChild) {
            throw new \Exception("don't use searchChild=false and searchOnlyChild=true");
        }
        $qb = $this->createQueryBuilder('log');
        $qb->orderBy('log.loggedAt', 'DESC');
        $counter = 0;
        foreach ($classesids as $class => $ids) {
            if (sizeof($ids) == 0) {
                continue;
            }
            if(is_int($ids)){
                $ids = array($ids);
            }

            $ids = array_map(function($value){
                    return (string)"$value";
                },$ids);
            $counter++;
            if (!$this->searchOnlyChild) {
                $qb->orWhere(" log.objectId IN (:ids$counter) AND log.objectClass = :class$counter");
            }
            if ($this->searchChild) {
                $qb->orWhere(" log.childId IN (:ids$counter) AND log.childClass = :class$counter");
            }
            $qb->setParameter("ids$counter", $ids);
            $qb->setParameter("class$counter", $class);
        }

        return $qb;
    }

    public function prepare(array $logs)
    {
//        foreach ($logs as $log) {
//            if (method_exists($log, 'getChildLog')) {
//                $this->_em->refresh($log);
//            }
//        }

        return $logs;
    }

    /**
     * @param object $object
     * @param DateTime $date
     * @param bool $clone
     * @throws \Gedmo\Exception\UnexpectedValueException
     * @return object
     */
    public function historicalView($object, \DateTime $date, $clone = true)
    {
        if(true === $clone){
            $object = clone $object;
        }

        $metaData = $this->getClassAndId($object);
        $logs = $this->findLogs($metaData['id'],$metaData['class'],null, $date);
        if (!$logs) {
            throw new \Gedmo\Exception\UnexpectedValueException('Could not find any log entries: '.$date->format('c'));
        }
        // be sure not persist object automatic
        $this->_em->detach($object);
        $wrapped = new EntityWrapper($object, $this->_em);

        while (($log = array_pop($logs))) {
            $this->revertObjectBySingleLog($log, $wrapped);
        }

        return $wrapped->getObject();
    }

    /**
     * @param object $object
     * @param DateTime $date
     * @return object
     */
    public function getObjectOnDate($object, \DateTime $date)
    {
        $object = clone $object;

        $metaData = $this->getClassAndId($object);
        $logs = $this->findLogs($metaData['id'],$metaData['class'],null, $date);

        // be sure not persist object automatic
        $this->_em->detach($object);
        $wrapped = new EntityWrapper($object, $this->_em);

        while (($log = array_pop($logs))) {
            $this->revertObjectBySingleLog($log, $wrapped);
        }

        return $wrapped->getObject();
    }

    public function findLogs($objectId, $objectClass, $version = null, $loggedAt = null)
    {
        $meta = $this->getClassMetadata();
        $dql = "SELECT log FROM {$meta->name} log";
        $dql .= " WHERE log.objectId = :objectId";
        $dql .= " AND log.objectClass = :objectClass";
        if ($version != null) {
            $dql .= " AND log.version >= :version";
        }
        if ($loggedAt != null) {
            $dql .= " AND log.loggedAt >= :loggedAt";
        }
        $dql .= " ORDER BY log.version ASC";
        $q = $this->_em->createQuery($dql);
        $q->setParameter('objectId', $objectId);
        $q->setParameter('objectClass', $objectClass);
        if ($version != null) {
            $q->setParameter('version', $version);
        }
        if ($loggedAt != null) {
            $q->setParameter('loggedAt', $loggedAt);
        }
        return $q->getResult();
    }

    protected function revertObjectBySingleLog(Log $log, EntityWrapper $wrapped)
    {
        if (!$log->getOlddata()) {
            return false;
        }
        $objectMeta = $wrapped->getMetadata();
        foreach ($log->getOlddata() as $field => $value) {
            if ($objectMeta->isSingleValuedAssociation($field)) {
                $mapping = $objectMeta->getAssociationMapping($field);
                $value = $value ? $this->_em->getReference($mapping['targetEntity'], $value) : null;
            }
            $wrapped->setPropertyValue($field, $value);
        }

        return true;
    }

    public function revertTo(Log $log)
    {
        $softconfig = $this->getSoftDeletableListener()->getConfiguration($this->_em, $log->getObjectClass());
        if (sizeof($softconfig) == 0) {
            throw new \Exception('use softdeleteable Filter');
        }
        $this->_em->getFilters()->disable('softdeleteable');
        $entity = $this->_em->find($log->getObjectClass(), $log->getObjectId());
        $this->_em->getFilters()->enable('softdeleteable');

        $wrapped = new EntityWrapper($entity, $this->_em);
        $objectMeta = $wrapped->getMetadata();
        $logs = $this->findLogs($log->getObjectId(), $log->getObjectClass(), $log->getVersion());

        if (!$logs) {
            throw new \Gedmo\Exception\UnexpectedValueException('Could not find any log entries under version: ' . $log->getVersion());
        }
        $this->getLoggableListener()->setEnabled(false);
        while (($log = array_pop($logs))) {
            $action = $log->getAction();
            if ($action === 'create') {
                $wrapped->setPropertyValue($softconfig['fieldName'], new \DateTime());
            } else {
                if ($action === 'remove') {
                    $wrapped->setPropertyValue($softconfig['fieldName'], null);
                } else {
                    $this->revertObjectBySingleLog($log, $wrapped);
                }
            }

            $this->_em->remove($log);
            $this->_em->flush();
        }
        $this->getLoggableListener()->setEnabled(true);

        return $wrapped->getObject();
    }

    /**
     * @throws \RuntimeException
     * @return \Gedmo\SoftDeleteable\SoftDeleteableListener
     */
    protected function getSoftDeletableListener()
    {
        $evm = $this->_em->getEventManager();

        foreach ($evm->getListeners() as $listeners) {
            foreach ($listeners as $key => $listener) {
                if ($listener instanceof SoftDeleteableListener) {
                    return $listener;
                }
            }
        }

        throw new \RuntimeException('Listener "SoftDeleteableListener" was not added to the EventManager!');
    }

    /**
     * @throws \RuntimeException
     * @return \Gedmo\Loggable\LoggableListener
     */
    public function getLoggableListener()
    {
        $evm = $this->_em->getEventManager();

        foreach ($evm->getListeners() as $listeners) {
            foreach ($listeners as $listener) {
                if ($listener instanceof LoggableListener) {
                    return $listener;
                }
            }
        }

        throw new \RuntimeException('Listener "LoggableListener" was not added to the EventManager!');
    }

    /**
     * @return boolean
     */
    public function getSearchChild()
    {
        return $this->searchChild;
    }

    /**
     * @param boolean $searchChild
     */
    public function setSearchChild($searchChild)
    {
        $this->searchChild = $searchChild;
    }

    /**
     * @return boolean
     */
    public function getSearchOnlyChild()
    {
        return $this->searchOnlyChild;
    }

    /**
     * @param boolean $searchOnlyChild
     */
    public function setSearchOnlyChild($searchOnlyChild)
    {
        $this->searchOnlyChild = $searchOnlyChild;
    }

    /**
     * @return boolean
     */
    public function getForcePartialLoad()
    {
        return $this->forcePartialLoad;
    }

    /**
     * @param boolean $forcePartialLoad
     */
    public function setForcePartialLoad($forcePartialLoad)
    {
        $this->forcePartialLoad = $forcePartialLoad;
    }


}
