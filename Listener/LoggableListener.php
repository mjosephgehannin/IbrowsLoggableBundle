<?php
namespace Ibrows\LoggableBundle\Listener;

use Doctrine\Common\EventArgs;
use Doctrine\ORM\Event\OnClearEventArgs;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\NoResultException;
use Gedmo\Loggable\Mapping\Event\LoggableAdapter;
use Gedmo\Tool\Wrapper\AbstractWrapper;
use Ibrows\LoggableBundle\Entity\ChangeSet;
use Ibrows\LoggableBundle\Entity\Log;
use Ibrows\LoggableBundle\Entity\LogMany2Many;
use Ibrows\LoggableBundle\Entity\LogParent;
use Ibrows\LoggableBundle\Model\ScheduledChangeable;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Role\SwitchUserRole;

/**
 * Class LoggableListener
 * @package Ibrows\LoggableBundle\Listener
 */
class LoggableListener extends \Gedmo\Loggable\LoggableListener
{
    /**
     * @var array
     */
    protected $pendingParents = array();
    /**
     * @var bool
     */
    protected $enabled = true;
    /**
     * @var bool
     */
    protected $useOnySingleIds = true;
    /**
     * @var bool
     */
    protected $defaultAllVersioned = true;
    /**
     * @var string
     */
    protected $abstractLogEntryClass = 'Ibrows\LoggableBundle\Entity\AbstractLog';
    /**
     * @var string
     */
    protected $defaultLogEntryClass = 'Ibrows\LoggableBundle\Entity\Log';
    /**
     * @var string
     */
    protected $logParentEntryClass = 'Ibrows\LoggableBundle\Entity\LogParent';
    /**
     * @var string
     */
    protected $logCollectionEntryClass = 'Ibrows\LoggableBundle\Entity\LogMany2Many';

    /**
     * @var string
     */
    protected $sourceUsername;

    /**
     * @param EventArgs $args
     */
    public function postPersist(EventArgs $args)
    {
        parent::postPersist($args);


        $ea = $this->getEventAdapter($args);
        $object = $ea->getObject();
        $om = $ea->getObjectManager();
        $oid = spl_object_hash($object);
        $uow = $om->getUnitOfWork();


        if ($this->pendingParents && array_key_exists($oid, $this->pendingParents)) {
            $wrapped = AbstractWrapper::wrap($object, $om);
            $id = $wrapped->getIdentifier($this->useOnySingleIds);
            foreach ($this->pendingParents[$oid] as $pending) {
                $logEntry = $pending['log'];
                $field = $pending['field'];
                $logEntryMeta = $om->getClassMetadata(get_class($logEntry));
                $logEntryMeta->getReflectionProperty($field)->setValue($logEntry, $id);
                $uow->scheduleExtraUpdate(
                    $logEntry,
                    array(
                        $field => array(null, $id)
                    )
                );
                $ea->setOriginalObjectProperty($uow, spl_object_hash($logEntry), $field, $id);
            }
            unset($this->pendingLogEntryInserts[$oid]);

        }

    }
    /**
     * {@inheritdoc}
     */
    public function getSubscribedEvents()
    {
        return array(
            'onClear',
            'onFlush',
            'loadClassMetadata',
            'postPersist',
        );
    }

    /**
     * {@inheritdoc}
     */
    public function onClear(OnClearEventArgs $eventArgs)
    {
        if($eventArgs->clearsAllEntities()){
            //there's never something pending if all cleared
            $this->pendingParents = array();
        }
    }
    /**
     * @param EventArgs $eventArgs
     */
    public function onFlush(EventArgs $eventArgs)
    {
        if (!$this->enabled) {
            return;
        }

        $ea = $this->getEventAdapter($eventArgs);
        $om = $ea->getObjectManager();
        $uow = $om->getUnitOfWork();

        foreach ($ea->getScheduledObjectInsertions($uow) as $object) {
            $this->createLogEntry(self::ACTION_CREATE, $object, $ea);
        }
        foreach ($ea->getScheduledObjectUpdates($uow) as $object) {
            $this->createLogEntry(self::ACTION_UPDATE, $object, $ea);
        }
        foreach ($ea->getScheduledObjectDeletions($uow) as $object) {
            $this->createLogEntry(self::ACTION_REMOVE, $object, $ea);
        }


        foreach ($uow->getScheduledCollectionUpdates() as $collection) {
            $this->createMany2ManyLogEntry(self::ACTION_UPDATE, $collection, $ea);
        }
        foreach ($uow->getScheduledCollectionDeletions() as $collection) {
            $this->createMany2ManyLogEntry(self::ACTION_REMOVE, $collection, $ea);
        }
    }

    /**
     * @param string $action
     * @param object $object
     * @param LoggableAdapter $ea
     */
    protected function createLogEntry($action, $object, LoggableAdapter $ea)
    {
        $om = $ea->getObjectManager();
        $wrapped = AbstractWrapper::wrap($object, $om);
        $meta = $wrapped->getMetadata();
        $config = $this->getConfiguration($om, $meta->name);

        if (!$config && !$this->defaultAllVersioned) {
            return;
        }
        $logEntry = $this->newLogEntry($ea, $meta->name, $action);



        if ($action === self::ACTION_CREATE && $ea->isPostInsertGenerator($meta)) {
            $this->pendingLogEntryInserts[spl_object_hash($object)] = $logEntry;
        } else {
            $logEntry->setObjectId($wrapped->getIdentifier());
        }

        $uow = $om->getUnitOfWork();
        $newValues = array();
        $oldValues = array();

        if ($action !== self::ACTION_REMOVE && (isset($config['versioned']) || $this->defaultAllVersioned)) {
            $changeSet = $ea->getObjectChangeSet($uow, $object);
            foreach ($changeSet as $field => $changes) {
                if (isset($config['versioned']) && !in_array($field, $config['versioned'])) {
                    continue;
                }
                if(is_object($changes[1]) && is_object($changes[0])){
                    // use === if both an object
                    if ($changes[1] === $changes[0]) {
                        continue;
                    }
                }else{
                    if ($changes[1] == $changes[0]) {
                        continue;
                    }
                }


                $value = $changes[1];
                $oldValue = $changes[0];

                if (array_key_exists($field, $meta->associationMappings)) {
                    $fieldmetadata = $meta->associationMappings[$field];
                    if ($fieldmetadata['type'] == \Doctrine\ORM\Mapping\ClassMetadata::MANY_TO_ONE) {
                        //many2one
                        if ($value != null) {
                            $parentLogEntry = $this->addLogParent($ea, $logEntry, $value, self::ACTION_CREATE, $object, $field);
                        } else {
                            if ($oldValue != null) {
                                $parentLogEntry = $this->addLogParent($ea, $logEntry, $oldValue, self::ACTION_REMOVE, $object, $field);
                            }
                        }

                    }
                }

                if ($meta->isSingleValuedAssociation($field)) {
                    if ($value) {
                        $oid = spl_object_hash($value);
                        $wrappedAssoc = AbstractWrapper::wrap($value, $om);
                        $value = $wrappedAssoc->getIdentifier(false);
                        if (!is_array($value) && !$value) {
                            $this->pendingRelatedObjects[$oid][] = array(
                                'log' => $logEntry,
                                'field' => $field
                            );
                        }
                        $value = $wrappedAssoc->getIdentifier($this->useOnySingleIds);
                    }
                    if ($oldValue) {
                        $oid = spl_object_hash($oldValue);
                        $wrappedAssoc = AbstractWrapper::wrap($oldValue, $om);
                        $oldValue = $wrappedAssoc->getIdentifier(false);
                        if (!is_array($oldValue) && !$oldValue) {
                            $this->pendingRelatedObjects[$oid][] = array(
                                'log' => $logEntry,
                                'field' => $field
                            );
                        }
                        $oldValue = $wrappedAssoc->getIdentifier($this->useOnySingleIds);
                    }

                }
                $oldValues[$field] = $oldValue;
                $newValues[$field] = $value;
            }
            $logEntry->setData($newValues);
            $logEntry->setOldData($oldValues);
        }


        if ($action === self::ACTION_UPDATE && 0 === count($newValues)) {
            //nothing to log
            return;
        }


        $version = 1;
        $logEntryMeta = $this->getLogEntryMeta($ea, $meta->name);
        if ($action !== self::ACTION_CREATE) {
            try {
                $version = $ea->getNewVersion($logEntryMeta, $object);
            }catch (NoResultException $e){
                $version = 1;
            }
        }
        $logEntry->setVersion($version);
        if ($this->addChangeSet($object, $logEntry, $ea)) {
            //dont save log if changeset added
            return;
        }

        $this->prePersistLogEntry($logEntry, $object);

        $om->persist($logEntry);

        $parentLogEntryMeta = $ea->getObjectManager()->getClassMetadata(get_class(new LogParent()));
        foreach ($logEntry->getParents() as $parentLogEntry) {
            $om->persist($parentLogEntry);
            $uow->computeChangeSet($parentLogEntryMeta, $parentLogEntry);
        }

        $uow->computeChangeSet($logEntryMeta, $logEntry);


    }

    /**
     * @param LoggableAdapter $ea
     * @param string $class
     * @return string
     */
    protected function getLogEntryClass(LoggableAdapter $ea, $class)
    {
        return isset(self::$configurations[$this->name][$class]['logEntryClass']) ?
            self::$configurations[$this->name][$class]['logEntryClass'] :
            $this->defaultLogEntryClass;
    }

    /**
     * @param $action
     * @param $col
     * @param LoggableAdapter $ea
     */
    protected function createMany2ManyLogEntry($action, $col, LoggableAdapter $ea)
    {
        $om = $ea->getObjectManager();
        $uow = $om->getUnitOfWork();
        $logs = array();
        /* @var $col \Doctrine\ORM\PersistentCollection */
        if ($action == self::ACTION_UPDATE) {
            foreach ($col->getInsertDiff() as $value) {
                $logEntry = $this->newLogMany2Many($ea, $col->getOwner(), $value, self::ACTION_CREATE);
                $logs[] = $logEntry;
            }
            foreach ($col->getDeleteDiff() as $value) {
                $logEntry = $this->newLogMany2Many($ea, $col->getOwner(), $value, self::ACTION_REMOVE);
                $logs[] = $logEntry;
            }
        }
        if ($action == self::ACTION_REMOVE) {
            /* @var $col \Doctrine\ORM\PersistentCollection */
            $logEntry = $this->newLogMany2Many($ea, $col->getOwner(), null, self::ACTION_REMOVE);
            $logEntry->setInverseClass($col->getTypeClass()->rootEntityName);
            $logs[] = $logEntry;
        }
        $logEntryMeta = $ea->getObjectManager()->getClassMetadata(get_class(new LogMany2Many()));
        foreach ($logs as $logEntry) {
            $om->persist($logEntry);
            $uow->computeChangeSet($logEntryMeta, $logEntry);
        }

    }

    /**
     * @param \Gedmo\Loggable\Mapping\Event\LoggableAdapter $ea
     * @param $object
     * @param $object2
     * @param $message
     * @return \Ibrows\MedSuiteBundle\Entity\LogMany2Many
     */
    protected function newLogMany2Many(LoggableAdapter $ea, $object, $object2, $message)
    {
        $om = $ea->getObjectManager();

        $logEntry = new LogMany2Many();
        $logEntry->setAction($message);
        $logEntry->setUsername($this->username);
        $logEntry->setSourceUsername($this->sourceUsername);
        $wrapped1 = AbstractWrapper::wrap($object, $om);
        $logEntry->setObjectClass($wrapped1->getMetadata()->name);
        $logEntry->setObjectId($wrapped1->getIdentifier());
        $this->pendingParents[spl_object_hash($object)][] = array(
            'log' => $logEntry,
            'field' => 'objectId'
        );
        if ($object2 != null) {
            $wrapped2 = AbstractWrapper::wrap($object2, $om);
            $logEntry->setInverseClass($wrapped2->getMetadata()->name);
            $logEntry->setInverseId($wrapped2->getIdentifier());
            $this->pendingParents[spl_object_hash($object2)][] = array(
                'log' => $logEntry,
                'field' => 'childId'
            );
        }


        return $logEntry;
    }

    /**
     * @param $enabled
     * @return $this
     */
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;

        return $this;
    }

    /**
     * @param string $abstractLogEntryClass
     */
    public function setAbstractLogEntryClass($abstractLogEntryClass)
    {
        $this->abstractLogEntryClass = $abstractLogEntryClass;
    }

    /**
     * @return string
     */
    public function getAbstractLogEntryClass()
    {
        return $this->abstractLogEntryClass;
    }

    /**
     * @return string
     */
    public function getDefaultLogEntryClass()
    {
        return $this->defaultLogEntryClass;
    }

    /**
     * @param string $defaultLogEntryClass
     */
    public function setDefaultLogEntryClass($defaultLogEntryClass)
    {
        $this->defaultLogEntryClass = $defaultLogEntryClass;
    }

    /**
     * @return string
     */
    public function getLogCollectionEntryClass()
    {
        return $this->logCollectionEntryClass;
    }

    /**
     * @param string $logCollectionEntryClass
     */
    public function setLogCollectionEntryClass($logCollectionEntryClass)
    {
        $this->logCollectionEntryClass = $logCollectionEntryClass;
    }

    /**
     * @return string
     */
    public function getLogParentEntryClass()
    {
        return $this->logParentEntryClass;
    }

    /**
     * @param string $logParentEntryClass
     */
    public function setLogParentEntryClass($logParentEntryClass)
    {
        $this->logParentEntryClass = $logParentEntryClass;
    }

    /**
     * @param boolean $useOnySingleIds
     */
    public function setUseOnySingleIds($useOnySingleIds)
    {
        $this->useOnySingleIds = $useOnySingleIds;
    }

    /**
     * @param boolean $defaultAllVersioned
     */
    public function setDefaultAllVersioned($defaultAllVersioned)
    {
        $this->defaultAllVersioned = $defaultAllVersioned;
    }



    /**
     * @param $object
     * @param Log $logEntry
     * @param LoggableAdapter $ea
     * @return bool
     */
    protected function addChangeSet($object, Log $logEntry, LoggableAdapter $ea)
    {

        if (!$object instanceof ScheduledChangeable) {
            return false;
        }
        $date = $object->getScheduledChangeDate();

        if ($date == null) {
            return false;
        }

        $om = $ea->getObjectManager();
        /* @var $om EntityManager */
        $uow = $om->getUnitOfWork();
        $meta = $om->getClassMetadata(get_class($object));
        $hash = spl_object_hash($object);

        if ($logEntry->getAction() == self::ACTION_REMOVE) {
            //MANY_TO_ONE
            foreach ($meta->getAssociationMappings() as $associationMapping) {
                if(  $associationMapping['type'] != \Doctrine\ORM\Mapping\ClassMetadata::MANY_TO_ONE ){
                    continue;
                }
                $one = $meta->getReflectionProperty($associationMapping['fieldName'])->getValue($object) ;
                if (array_key_exists('inversedBy', $associationMapping)) {
                    $field = $associationMapping['inversedBy'];
                    $associationMeta = $om->getClassMetadata($associationMapping['targetEntity']);
                    $associationMeta->getReflectionProperty($field)->getValue($one)->add($object) ;
                }
            }
            $om->persist($object);
            $uow->clearEntityChangeSet($hash);
        } else {
            if ($logEntry->getAction() == self::ACTION_CREATE) {
                //MANY_TO_ONE
                foreach ($logEntry->getParents() as $parent) {
                    $one = $om->getReference($parent->getObjectClass(), $parent->getObjectId());
                    $associationMapping = ($meta->getAssociationMapping($parent->getFieldName()));
                    if (array_key_exists('inversedBy', $associationMapping)) {
                        $field = $associationMapping['inversedBy'];
                        $associationMeta = $om->getClassMetadata($parent->getObjectClass());
                        $associationMeta->getReflectionProperty($field)->getValue($one)->removeElement($object);
                    }
                }
                $om->remove($object);
                $uow->clearEntityChangeSet($hash);
            } else {
                //  $undoSet = array();
                foreach ($logEntry->getOldData() as $field => $oldValue) {
                    if ($meta->isSingleValuedAssociation($field)) {
                        $mapping = $meta->getAssociationMapping($field);
                        $oldValue = $oldValue ? $om->getReference($mapping['targetEntity'], $oldValue) : null;
                    }
                    $meta->getReflectionProperty($field)->setValue($object, $oldValue);
                    $ea->setOriginalObjectProperty($uow, $hash, $field, $oldValue);
                    // $undoSet[$field] = array(null,$oldValue);
                }
                // $uow->scheduleExtraUpdate($object,$undoSet);
                //   $changeSetMeta = $ea->getObjectManager()->getClassMetadata(get_class($object));
                //   $uow->computeChangeSet($changeSetMeta, $object);
                $uow->clearEntityChangeSet($hash);
            }
        }

        $data = $logEntry->getData();
        $changeSet = new ChangeSet();
        $changeSet->setChangeAt($date);
        $changeSet->setObjectId($logEntry->getObjectId());
        $changeSet->setObjectClass($logEntry->getObjectClass());
        $changeSet->setData($data);
        $changeSet->setOldData($logEntry->getOldData());
        $changeSet->setUsername($logEntry->getUsername());
        $changeSet->setAction($logEntry->getAction());
        $om->persist($changeSet);
        $changeSetMeta = $ea->getObjectManager()->getClassMetadata(get_class($changeSet));
        $uow->computeChangeSet($changeSetMeta, $changeSet);

        return true;
    }

    /**
     * @param LoggableAdapter $ea
     * @param $class
     * @param $action
     * @return Log
     */
    protected function newLogEntry(LoggableAdapter $ea, $class, $action)
    {
        $logEntryMeta = $this->getLogEntryMeta($ea, $class);
        $logEntry = $logEntryMeta->newInstance();
        $logEntry->__construct();
        $logEntry->setUsername($this->username);
        $logEntry->setSourceUsername($this->sourceUsername);
        $logEntry->setObjectClass($class);
        $logEntry->setLoggedAt();
        $logEntry->setAction($action);

        return $logEntry;
    }

    /**
     * @param $ea
     * @param $class
     * @return mixed
     */
    protected function getLogEntryMeta($ea, $class)
    {
        $logEntryClass = $this->getLogEntryClass($ea, $class);

        return $ea->getObjectManager()->getClassMetadata($logEntryClass);
    }

    /**
     * @param string|TokenInterface|object $username
     */
    public function setUsername($username)
    {
        parent::setUsername($username);

        if(!$username instanceof TokenInterface){
            return;
        }

        if(!$originalToken = $this->getOriginalToken($username)){
            return;
        }

        $this->sourceUsername = $originalToken->getUsername();
    }

    /**
     * @param \Gedmo\Loggable\Mapping\Event\LoggableAdapter $ea
     * @param Log $logEntry
     * @param $object
     * @param $message
     * @return \Ibrows\MedSuiteBundle\Entity\LogParent
     */
    protected function addLogParent(LoggableAdapter $ea, Log $logEntry, $object, $message, $childObject, $fieldName)
    {
        $om = $ea->getObjectManager();

        $parentLogEntry = new LogParent();
        $parentLogEntry->setAction($message);
        $parentLogEntry->setUsername($this->username);
        $parentLogEntry->setSourceUsername($this->sourceUsername);
        $wrappedParent = AbstractWrapper::wrap($object, $om);
        $parentLogEntry->setObjectClass($wrappedParent->getMetadata()->name);
        $parentLogEntry->setObjectId($wrappedParent->getIdentifier());
        $parentLogEntry->setFieldName($fieldName);
        $logEntry->addParent($parentLogEntry);

        $this->pendingParents[spl_object_hash($object)][] = array(
            'log' => $parentLogEntry,
            'field' => 'objectId'
        );
        $this->pendingParents[spl_object_hash($childObject)][] = array(
            'log' => $parentLogEntry,
            'field' => 'childId'
        );

        return $parentLogEntry;
    }

    /**
     * @param TokenInterface $token
     * @return bool|TokenInterface
     */
    protected function getOriginalToken(TokenInterface $token)
    {
        foreach ($token->getRoles() as $role) {
            if ($role instanceof SwitchUserRole) {
                return $role->getSource();
            }
        }

        return false;
    }
}
