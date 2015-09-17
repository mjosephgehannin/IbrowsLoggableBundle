<?php

namespace Ibrows\LoggableBundle\Model;


interface ScheduledChangeablePartially extends ScheduledChangeable
{
    /**
     * @return array
     */
    public function getFieldsToSchedule();


}
