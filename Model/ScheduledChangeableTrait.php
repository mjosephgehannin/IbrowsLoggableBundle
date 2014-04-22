<?php

namespace Ibrows\LoggableBundle\Model;

trait ScheduledChangeableTrait
{
    /**
     * @var \DateTime
     */
    protected $scheduledChangeDate;

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
        $this->scheduledChangeDate = null;
    }

    /**
     * @param \DateTime $scheduledChangeDate
     */
    public function setScheduledChangeDate(\DateTime $scheduledChangeDate = null)
    {
        $this->scheduledChangeDate = $scheduledChangeDate;
    }
}