<?php

namespace Xypp\TrustLevels\Event;

use Flarum\User\User;
use Xypp\TrustLevels\TrustLevel;

class TrustLevelChange
{
    public function __construct(
        public User $user,
        public TrustLevel $trustLevel,
        public ?TrustLevel $fromLevel
    ) {
    }
}
