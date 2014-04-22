<?php

namespace Ibrows\LoggableBundle\Repository;

use DateTime;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Gedmo\SoftDeleteable\SoftDeleteableListener;
use Gedmo\Tool\Wrapper\AbstractWrapper;
use Ibrows\LoggableBundle\Listener\LoggableListener;

class LogParentRepository extends LogRepository
{


}
