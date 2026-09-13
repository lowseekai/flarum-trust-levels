<?php

namespace Xypp\TrustLevels\Listener;

use Flarum\User\User;
use Xypp\Collector\Event\DailyUpdate;
use Xypp\TrustLevels\Utils\TrustLevelUtils;

class DayChange
{
    public function __invoke(DailyUpdate $event)
    {
        User::query()->each(function (User $actor) {
            TrustLevelUtils::checkLevel($actor);
        });
    }
}
