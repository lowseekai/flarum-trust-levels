<?php

namespace Xypp\Collector\Helper;
use Illuminate\Console\Command;

class CommandContextHelper
{
    public ?Command $commandContext = null;

    public function setCommand(Command $command)
    {
        $this->commandContext = $command;
    }
    public function clearCommand()
    {
        $this->commandContext = null;
    }
    public function getCommand(): ?Command
    {
        return $this->commandContext;
    }
    public function getProgressBar($max)
    {
        return $this->commandContext ? $this->commandContext->getOutput()->createProgressBar($max) : null;
    }
    public function withProgressBar($iter, $callback)
    {
        if ($this->commandContext && !$this->commandContext->isHidden()) {
            $this->commandContext->withProgressBar($iter, $callback);
            return;
        }
        foreach ($iter as $item) {
            $callback($item);
        }
    }
}