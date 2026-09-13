<?php

namespace Xypp\Collector\Event;
use Flarum\User\User;
use Illuminate\Console\Command;

class DebugInfo
{
    public User $user;
    public Command $command;
    public function __construct(User $user, Command $command)
    {
        $this->user = $user;
        $this->command = $command;
    }
}