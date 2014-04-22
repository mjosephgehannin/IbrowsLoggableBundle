<?php

namespace Ibrows\LoggableBundle\Repository;

use DateTime;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Gedmo\SoftDeleteable\SoftDeleteableListener;
use Gedmo\Tool\Wrapper\AbstractWrapper;
use Ibrows\LoggableBundle\Listener\LoggableListener;

class LogMany2ManyRepository extends LogRepository
{



    public function getLogsByObject($object, $searchChild = true, $searchOnlyChild = false){
        $wrapped = AbstractWrapper::wrap($object, $this->getEntityManager());
        $id = $wrapped->getIdentifier();
        $class = $wrapped->getRootObjectName();
        return $this->getLogsByClassId($class,$id,$searchChild,$searchOnlyChild);
    }

    public function getLogsByClassId($class,$id, $searchChild = true, $searchOnlyChild = false){
        $logs = $this->getLogs(array($class => array($id),$searchChild,$searchOnlyChild) );
        return $logs;
    }


    protected  function getQB(array $classesids, $searchChild = true, $searchOnlyChild = false){
        if(!$searchChild && $searchOnlyChild){
            throw new \Exception("don't use searchChild=false and searchOnlyChild=true");
        }
        $qb = $this->createQueryBuilder('log');
        $qb->orderBy('log.loggedAt', 'DESC');
        $counter = 0;
        foreach ($classesids as $class => $ids) {
            if (sizeof($ids) == 0) {
                continue;
            }
            $counter++;
            if(!$searchOnlyChild)
                $qb->orWhere(" log.objectId IN (:ids$counter) AND log.objectClass = :class$counter");
            if($searchChild)
                $qb->orWhere(" log.childId IN (:ids$counter) AND log.childClass = :class$counter");
            $qb->setParameter("ids$counter", $ids);
            $qb->setParameter("class$counter", $class);
        }
        return $qb;
    }

    public function getLogs(array $classesids, $searchChild = true, $searchOnlyChild = false)
    {
        $logs = $this->getQB($classesids,$searchChild,$searchOnlyChild)->getQuery()->getResult();

        return $this->prepare($logs);
    }

}
