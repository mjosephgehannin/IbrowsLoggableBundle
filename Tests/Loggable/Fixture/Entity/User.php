<?php

namespace Ibrows\LoggableBundle\Tests\Loggable\Fixture\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Ibrows\LoggableBundle\Model\ScheduledChangeable;

/**
 * @ORM\Entity
 * @ORM\Table(name="fix_user")
 * @Gedmo\SoftDeleteable(fieldName="deletedAt")
 */
class User implements ScheduledChangeable
{
    /**
     * @var Article[]
     * @ORM\ManyToMany(targetEntity="Article", cascade="all")
     * @ORM\JoinTable(name="fix_user_articele",
     *      joinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="article_id", referencedColumnName="id")}
     *      )
     */
    protected $articles;
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;
    /**
     * @ORM\Column(name="title", type="string", length=8)
     */
    private $name;
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

    public function addArticle(Article $article)
    {
        if (!$this->getArticles()->contains($article)) {
            $this->getArticles()->add($article);
        }

        return $this;
    }

    public function getArticles()
    {
        return $this->articles ? : $this->articles = new ArrayCollection();
    }

    public function removeArticle(Article $article)
    {
        if ($this->getArticles()->contains($article)) {
            $removed = $this->getArticles()->removeElement($article);
        }

        return $this;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

}
