<?php
namespace Ibrows\LoggableBundle\Entity;


use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Loggable\Entity\MappedSuperclass\AbstractLogEntry;
use Gedmo\Mapping\Annotation as Gedmo;
use Ibrows\LoggableBundle\Model\AbstractLogModel;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="Ibrows\LoggableBundle\Repository\LogRepository")
 */
class Log extends AbstractLog
{

    public function __construct()
    {
        $this->parents = new ArrayCollection();
        $this->setLoggedAt();
    }

    public function getLogType()
    {
        return self::LOG_TYPE_STANDARD;
    }

}
