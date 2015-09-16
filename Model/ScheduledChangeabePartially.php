<?php

namespace Ibrows\LoggableBundle\Model;


interface ScheduledChangeabePartially extends ScheduledChangeable
{
    /**
     * @return array
     */
    public function getFieldsToSchedule();


}
