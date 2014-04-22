<?php

namespace Ibrows\LoggableBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Gedmo\Tool\Wrapper\EntityWrapper;
use Ibrows\LoggableBundle\Entity\ChangeSet;
use Ibrows\LoggableBundle\Listener\LoggableListener;

/**
 * Class ChangeSetRepository
 * @package Ibrows\LoggableBundle\Repository
 */
class ChangeSetRepository extends EntityRepository
{

    /**
     * @param \DateTime $changeAt
     * @return ChangeSet[]
     */
    public function findAllByChangeAt(\DateTime $changeAt)
    {
        $qb = $this->createQueryBuilder('c');
        $qb->andWhere("c.changeAt <= :changeAt");
        $qb->setParameter('changeAt', $changeAt);
        return $qb->getQuery()->execute();
    }

    /**
     * @param ChangeSet $changeset
     * @return bool|object
     */
    public function applyChangeSet(ChangeSet $changeset)
    {
        $now = new \DateTime();
        if ($changeset->getChangeAt() == null || $changeset->getChangeAt()->diff($now)->invert) {
            return false;
        }
        if ($changeset->getAction() == LoggableListener::ACTION_CREATE && $changeset->getObjectId() != null) {
            throw new \Exception("changeSet invalid {$changeset->getId()}  create with object id");
        }
        if ($changeset->getAction() != LoggableListener::ACTION_CREATE && $changeset->getObjectId() == null) {
            throw new \Exception("changeSet invalid {$changeset->getId()}  no objectId found but no create Action");
        }

        $data = $changeset->getData();

        if ($changeset->getObjectId() == null) {
            //create a new one
            $r = new \ReflectionClass($changeset->getObjectClass());
            $entity = $r->newInstanceWithoutConstructor();
        } else {
            //edit
            $entity = $this->_em->find($changeset->getObjectClass(), $changeset->getObjectId());
        }

        if ($changeset->getAction() == LoggableListener::ACTION_REMOVE) {
            //very rudimental remove... dont care about sitdeffects
            $this->_em->remove($entity);
            $this->_em->remove($changeset);
            $this->_em->flush();
            return true;
        }

        $wrapped = new EntityWrapper($entity, $this->_em);
        $objectMeta = $wrapped->getMetadata();

        foreach ($data as $field => $value) {
            if ($objectMeta->isSingleValuedAssociation($field)) {
                $mapping = $objectMeta->getAssociationMapping($field);
                $value = $value ? $this->_em->getReference($mapping['targetEntity'], $value) : null;
            }
            $wrapped->setPropertyValue($field, $value);
        }

        if ($changeset->getObjectId() == null) {
            //MANY_TO_ONE create new one
            foreach ($objectMeta->getAssociationMappings() as $associationMapping) {
                if ($associationMapping['type'] != \Doctrine\ORM\Mapping\ClassMetadata::MANY_TO_ONE) {
                    continue;
                }
                $one = $objectMeta->getReflectionProperty($associationMapping['fieldName'])->getValue($entity);
                if (array_key_exists('inversedBy', $associationMapping)) {
                    $field = $associationMapping['inversedBy'];
                    $associationMeta = $this->_em->getClassMetadata($associationMapping['targetEntity']);
                    $associationMeta->getReflectionProperty($field)->getValue($one)->add($entity);
                }
            }
        }


        $object = $wrapped->getObject();
        $object->removeScheduledChangeDate();

        $this->_em->persist($object);
        $this->_em->remove($changeset);
        $this->_em->flush();
        return $object;
    }

    public function getChangeSets(array $classesids, $offset = null, $limit = null)
    {
        $qb = $this->getQB($classesids);
        $qb->setMaxResults($limit);
        $qb->setFirstResult($offset);
        $q = $qb->getQuery();
        $changeSets = $q->getResult();
        return $changeSets;
    }


    protected function getQB(array $classesIds)
    {
        $qb = $this->createQueryBuilder('cs');
        $qb->orderBy('cs.changeAt', 'DESC');
        $counter = 0;
        foreach ($classesIds as $class => $ids) {
            if (sizeof($ids) == 0) {
                continue;
            }
            if (is_int($ids)) {
                $ids = array($ids);
            }

            $ids = array_map(
                function ($value) {
                    return (string)"$value";
                },
                $ids
            );
            $counter++;
            $qb->orWhere(" cs.objectId IN (:ids$counter) AND cs.objectClass = :class$counter");
            $qb->setParameter("ids$counter", $ids);
            $qb->setParameter("class$counter", $class);
        }

        return $qb;
    }

}
