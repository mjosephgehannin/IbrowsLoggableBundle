<?php
namespace Ibrows\LoggableBundle\Entity;


use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Loggable\Entity\MappedSuperclass\AbstractLogEntry;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="Ibrows\LoggableBundle\Repository\ChangeSetRepository")
 * @ORM\Table(
 *     name="changeset",
 *  indexes={
 *      @ORM\Index(name="log_change_class_lookup_idx", columns={"object_class"}),
 *      @ORM\Index(name="log_change_date_lookup_idx", columns={"change_at"}),
 *      @ORM\Index(name="log_change_user_lookup_idx", columns={"username"})
 *  }
 * )
 */
class ChangeSet
{

    /**
     * @var integer $id
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected $id;

    /**
     * @var string $action
     *
     * @ORM\Column(type="string")
     */
    protected $action;

    /**
     * @var \DateTime $changeAt
     *
     * @ORM\Column(name="change_at", type="datetime")
     */
    protected $changeAt;

    /**
     * @var string $objectId
     *
     * @ORM\Column(name="object_id", length=64, nullable=true)
     */
    protected $objectId;

    /**
     * @var string $objectClass
     *
     * @ORM\Column(name="object_class", type="string", length=255)
     */
    protected $objectClass;


    /**
     * @var array $data
     *
     * @ORM\Column(type="json_array", nullable=true)
     */
    protected $data;

    /**
     * @var array $olddata
     *
     * @ORM\Column(type="json_array", nullable=true, name="old_data")
     */
    protected $oldData;


    /**
     * @var string $data
     *
     * @ORM\Column(length=255, nullable=true)
     */
    protected $username;


    public function __construct()
    {
    }

    /**
     * @param string $key
     * @param mixed $value
     * @param mixed $oldvalue
     */
    public function addData($key, $value, $oldvalue)
    {
        $this->data[$key] = $value;
        $this->oldData[$key] = $oldvalue;
    }

    /**
     * @return string
     */
    public function getType()
    {
        $pos = strrpos($this->objectClass, '\\');
        ++$pos;
        $type = substr($this->objectClass, $pos);

        return $type;
    }

    /**
     * @param \DateTime $changeAt
     */
    public function setChangeAt(\DateTime $changeAt = null)
    {
        $this->changeAt = $changeAt;
    }

    /**
     * @return \DateTime
     */
    public function getChangeAt()
    {
        return $this->changeAt;
    }

    /**
     * @param array $data
     */
    public function setData($data)
    {
        $this->data = $data;
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $objectClass
     */
    public function setObjectClass($objectClass)
    {
        $this->objectClass = $objectClass;
    }

    /**
     * @return string
     */
    public function getObjectClass()
    {
        return $this->objectClass;
    }

    /**
     * @param string $objectId
     */
    public function setObjectId($objectId)
    {
        $this->objectId = $objectId;
    }

    /**
     * @return string
     */
    public function getObjectId()
    {
        return $this->objectId;
    }

    /**
     * @param array $oldData
     */
    public function setOldData($oldData)
    {
        $this->oldData = $oldData;
    }

    /**
     * @return array
     */
    public function getOldData()
    {
        return $this->oldData;
    }

    /**
     * @param string $username
     */
    public function setUsername($username)
    {
        $this->username = $username;
    }

    /**
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @param string $action
     */
    public function setAction($action)
    {
        $this->action = $action;
    }

    /**
     * @return string
     */
    public function getAction()
    {
        return $this->action;
    }



}
