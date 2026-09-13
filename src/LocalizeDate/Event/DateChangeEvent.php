<?php

namespace Xypp\LocalizeDate\Event;

use Carbon\Carbon;

class DateChangeEvent
{
    public function __construct(
        public Carbon $date
    ) {
    }
}
