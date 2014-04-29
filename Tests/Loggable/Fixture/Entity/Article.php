<?php

namespace Ibrows\LoggableBundle\Tests\Loggable\Fixture\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Ibrows\LoggableBundle\Model\ScheduledChangeable;

/**
 * @ORM\Entity()
 * @ORM\Table(name="fix_article")
 * @Gedmo\SoftDeleteable(fieldName="deletedAt")
 */
class Article implements ScheduledChangeable
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @ORM\Column(name="title", type="string", length=8)
     */
    private $title;
    /**
     * @var $deletedAt \DateTime
     * @ORM\Column(name="deleted_at", type="datetime", nullable=true)
     */
    private $deletedAt;

    /**
     * @var $someDate \DateTime
     * @ORM\Column(name="some_date", type="datetime", nullable=true)
     */
    private $someDate;
    /**
     * @var $scheduledChangeDate \DateTime
     */
    private $scheduledChangeDate;

    /**
     * @return \DateTime
     */
    public function getDeletedAt()
    {
        return $this->deletedAt;
    }

    /**
     * @param \DateTime $deletedAt
     */
    public function setDeletedAt($deletedAt = null)
    {
        $this->deletedAt = $deletedAt;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * @param \DateTime $someDate
     */
    public function setSomeDate($someDate)
    {
        $this->someDate = $someDate;
    }

    /**
     * @return \DateTime
     */
    public function getSomeDate()
    {
        return $this->someDate;
    }

    /**
     * @param \DateTime $scheduledChangeDate
     */
    public function setScheduledChangeDate($scheduledChangeDate = null)
    {
        $this->scheduledChangeDate = $scheduledChangeDate;
    }

    /**
     * @return \DateTime
     */
    public function getScheduledChangeDate()
    {
        return $this->scheduledChangeDate;
    }


    /**
     * set changeDate 2 null
     */
    public function removeScheduledChangeDate()
    {
        $this->setScheduledChangeDate(null);
    }
}
