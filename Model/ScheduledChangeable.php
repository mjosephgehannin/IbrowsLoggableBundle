<?php

namespace Ibrows\LoggableBundle\Model;


interface ScheduledChangeable
{
    /**
     * @return \DateTime
     */
    public function getScheduledChangeDate();

    /**
     * set changeDate 2 null
     */
    public function removeScheduledChangeDate();
}
