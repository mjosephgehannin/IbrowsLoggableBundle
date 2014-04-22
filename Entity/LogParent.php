<?php
namespace Ibrows\LoggableBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ibrows\Bundle\SonataAdminAnnotationBundle\Annotation as Sonata;

/**
 * @ORM\Entity(repositoryClass="Ibrows\LoggableBundle\Repository\LogParentRepository")
 */
class LogParent extends Log
{

    /**
     * @var string $data
     *
     * @ORM\Column(name="field_name", nullable=true)
     */
    protected $fieldName;

    public function __construct(){
        parent::__construct();
        $this->version = 0;
    }


    /**
     * @var Log
     * @ORM\ManyToOne(targetEntity="Ibrows\LoggableBundle\Entity\Log", inversedBy="parents")
     * @ORM\JoinColumn(name="child_log_id")
     */
    protected $childLog;

    /**
     * @return string
     */
    public function getChildClass()
    {
        return $this->childClass;
    }

    /**
     * @param string $childClass
     */
    public function setChildClass($childClass)
    {
        $this->childClass = $childClass;
    }

    /**
     * @return string
     */
    public function getChildId()
    {
        return $this->childId;
    }

    /**
     * @param string $childId
     */
    public function setChildId($childId)
    {
        $this->childId = $childId;
    }

    /**
     * @return Log
     */
    public function getChildLog()
    {
        return $this->childLog;
    }

    /**
     * @param Log $childLog
     */
    public function setChildLog(Log $childLog = null)
    {
        $this->childLog = $childLog;
        if ($childLog == null) {
            $this->setChildClass(null);
            $this->setChildId(null);
            $this->setLoggedAt();
        } else {
            $this->setChildClass($childLog->getObjectClass());
            $this->setChildId($childLog->getObjectId());
            $this->loggedAt = ($childLog->getLoggedAt());
        }


    }


    public function getLogType()
    {
        return self::LOG_TYPE_PARENT;
    }

    /**
     * @param string $fieldName
     */
    public function setFieldName($fieldName)
    {
        $this->fieldName = $fieldName;
    }

    /**
     * @return string
     */
    public function getFieldName()
    {
        return $this->fieldName;
    }

}
