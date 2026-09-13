<?php

namespace Xypp\Collector\Integration\Global;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Xypp\Collector\ConditionDefinition;
use Xypp\Collector\Data\ConditionAccumulation;
use Xypp\Collector\GlobalConditionDefinition;
use Xypp\Collector\Helper\CommandContextHelper;
use Xypp\Collector\RewardDefinition;

class GlobalLike extends GlobalConditionDefinition
{
    public bool $accumulateAbsolute = true;
    public ConnectionInterface $connection;
    protected CommandContextHelper $commandContextHelper;
    public function __construct(ConnectionInterface $connection, CommandContextHelper $commandContextHelper)
    {
        parent::__construct("like", null, "xypp-collector.ref.integration.global-condition.like");
        $this->connection = $connection;
        $this->commandContextHelper = $commandContextHelper;
    }
    public function getAbsoluteValue(ConditionAccumulation $conditionAccumulation): bool
    {
        $likes = $this->connection->table("post_likes")->get(['created_at']);
        $this->commandContextHelper->withProgressBar($likes, function ($like) use (&$conditionAccumulation) {
            $date = Date::createFromFormat($this->connection->getQueryGrammar()->getDateFormat(), $like->created_at);
            $conditionAccumulation->updateValue($date, 1);
        });
        return $conditionAccumulation->dirty;
    }
}