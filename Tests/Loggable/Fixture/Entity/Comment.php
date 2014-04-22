<?php

namespace Ibrows\LoggableBundle\Tests\Loggable\Fixture\Entity;

use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;
use Ibrows\LoggableBundle\Model\ScheduledChangeable;

/**
 * @ORM\Entity
 * @ORM\Table(name="fix_comment")
 * @Gedmo\Loggable()
 * @Gedmo\SoftDeleteable(fieldName="deletedAt")
 */
class Comment implements ScheduledChangeable
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @Gedmo\Versioned
     * @ORM\Column(length=128)
     */
    private $subject;

    /**
     * @Gedmo\Versioned
     * @ORM\Column(type="text")
     */
    private $message;

    /**
     * @Gedmo\Versioned
     * @ORM\ManyToOne(targetEntity="RelatedArticle", inversedBy="comments", cascade="persist")
     */
    private $article;

    /**
     * @var $deletedAt \DateTime
     * @ORM\Column(name="deleted_at", type="datetime", nullable=true)
     */

    private $deletedAt;

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
    public function setArticle($article)
    {
        $this->article = $article;
    }

    public function getArticle()
    {
        return $this->article;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setSubject($subject)
    {
        $this->subject = $subject;
    }

    public function getSubject()
    {
        return $this->subject;
    }

    public function setMessage($message)
    {
        $this->message = $message;
    }

    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @param \DateTime $scheduledChangeDate
     */
    public function setScheduledChangeDate($scheduledChangeDate)
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
