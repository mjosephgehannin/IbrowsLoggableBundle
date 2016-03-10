<?php
namespace Ibrows\LoggableBundle\Entity;


use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Ibrows\LoggableBundle\Model\AbstractLogModel;
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
 *      @ORM\Index(name="log_user_lookup_idx", columns={"username"}),
 *      @ORM\Index(name="log_version_lookup_max", columns={"object_class","object_id","dtype","version"})
 *  }
 * )
 */
abstract class AbstractLog extends AbstractLogModel
{

    const LOG_TYPE_STANDARD = 'standard';
    const LOG_TYPE_MANY2MANY = 'many';
    const LOG_TYPE_PARENT = 'parent';

    /**
     * @var integer $id
     *
     * @ORM\Column(type="bigint")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected $id;

    /**
     * @var string $action
     *
     * @ORM\Column(type="string", length=8)
     */
    protected $action;

    /**
     * @var string $loggedAt
     *
     * @ORM\Column(name="logged_at", type="datetime")
     */
    protected $loggedAt;

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
     * @var string $username
     *
     * @ORM\Column(length=255, nullable=true)
     */
    protected $username;

    /**
     * @var string $sourceUsername
     *
     * @ORM\Column(length=255, nullable=true)
     */
    protected $sourceUsername;

    /**
     * @var integer $version
     *
     * @ORM\Column(type="integer")
     */
    protected $version;

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

    abstract public function getLogType();

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

    /**
     * @return string
     */
    public function getSourceUsername()
    {
        return $this->sourceUsername;
    }

    /**
     * @param string $sourceUsername
     */
    public function setSourceUsername($sourceUsername)
    {
        $this->sourceUsername = $sourceUsername;
    }

}
