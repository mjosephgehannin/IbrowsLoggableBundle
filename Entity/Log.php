<?php
namespace Ibrows\LoggableBundle\Entity;


use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Loggable\Entity\MappedSuperclass\AbstractLogEntry;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="Ibrows\LoggableBundle\Repository\LogRepository")
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\Table(
 *     name="log",
 *  indexes={
 *      @ORM\Index(name="log_object_class_lookup_idx", columns={"object_class"}),
 *      @ORM\Index(name="log_object_id_lookup_idx", columns={"object_id"}),
 *      @ORM\Index(name="log_child_class_lookup_idx", columns={"child_class"}),
 *      @ORM\Index(name="log_child_id_lookup_idx", columns={"child_id"}),
 *      @ORM\Index(name="log_date_lookup_idx", columns={"logged_at"}),
 *      @ORM\Index(name="log_user_lookup_idx", columns={"username"})
 *  }
 * )
 */
class Log extends AbstractLogEntry
{

    const LOG_TYPE_STANDARD = 'standard';
    const LOG_TYPE_MANY2MANY = 'many';
    const LOG_TYPE_PARENT = 'parent';
    /**
     * @var array $olddata
     *
     * @ORM\Column(type="array", nullable=true, name="old_data")
     */
    protected $oldData;
    /**
     * @var LogParent[]
     * @ORM\OneToMany(targetEntity="Ibrows\LoggableBundle\Entity\LogParent", mappedBy="childLog")
     */
    protected $parents;
    /**
     * @var string $objectId
     *
     * @ORM\Column(name="child_id", length=64, nullable=true)
     */
    protected $childId;
    /**
     * @var string $objectClass
     *
     * @ORM\Column(name="child_class", type="string", length=255, nullable=true)
     */
    protected $childClass;


    public function __construct()
    {
        $this->parents = new ArrayCollection();
        $this->setLoggedAt();
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
     * @return string
     */
    public function getTypeChild()
    {
        $pos = strrpos($this->childClass, '\\');
        ++$pos;
        $type = substr($this->childClass, $pos);

        return $type;
    }

    public function getLogType()
    {
        return self::LOG_TYPE_STANDARD;
    }

    /**
     * @return array
     */
    public function getComparedData()
    {
        $newData = $this->getData();
        $oldData = $this->getOldData();

        $comparedData = array();

        foreach ($newData as $key => $data) {
            $comparedData[$key] = array(
                'new' => self::normalizeData($data),
                'old' => isset($oldData[$key]) ? self::normalizeData($oldData[$key]) : null
            );
        }

        return $comparedData;
    }

    /**
     * @return array
     */
    public function getOldData()
    {
        return $this->oldData;
    }

    /**
     * @param array $oldData
     * @return $this
     */
    public function setOldData(array $oldData)
    {
        $this->oldData = $oldData;

        return $this;
    }

    /**
     * @param $value
     * @return array|mixed|string
     */
    protected static function normalizeData($value)
    {
        if ($value instanceof \DateTime) {
            return $value->format("d M Y");
        }

        if (is_array($value)) {
            if (sizeof($value) == 1) {
                return reset($value);
            } else {
                return print_r($value, true);
            }
        }

        return $value;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return LogParent[]
     */
    public function getParents()
    {
        return $this->parents;
    }

    /**
     * @param LogParent[] $parents
     */
    public function setParents($parents)
    {
        foreach ($this->getParents() as $parent) {
            $this->removeParent($parent);
        }
        foreach ($parents as $parent) {
            $this->addParent($parent);
        }
    }

    /**
     * @param LogParent $parent
     */
    public function removeParent(LogParent $parent)
    {
        $parent->setChildLog(null);
        $this->parents->remove($parent);
    }

    /**
     * @param LogParent $parent
     */
    public function addParent(LogParent $parent)
    {
        $parent->setChildLog($this);
        $this->parents->add($parent);
    }


}
