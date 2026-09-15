<?php

namespace Xypp\Collector\Integration\Conditions;

use Carbon\Carbon;
use Illuminate\Database\ConnectionInterface;
use Xypp\Collector\ConditionDefinition;
use Xypp\Collector\Data\ConditionAccumulation;
use Xypp\Collector\RewardDefinition;

class LikeSend extends ConditionDefinition
{
    public bool $accumulateAbsolute = true;
    public ConnectionInterface $connection;
    public function __construct(ConnectionInterface $connection)
    {
        parent::__construct("like_send", null, "xypp-collector.ref.integration.condition.like_send");
        $this->connection = $connection;
    }
    public function getAbsoluteValue(\Flarum\User\User $user, ConditionAccumulation $conditionAccumulation): bool
    {

        $likes = $this->connection->table("post_likes")->where("user_id", $user->id)->get(['created_at']);
        foreach ($likes as $like) {
            $date = Carbon::createFromFormat($this->connection->getQueryGrammar()->getDateFormat(), $like->created_at);
            $conditionAccumulation->updateValue($date, 1);
        }
        return $conditionAccumulation->dirty;
    }
}
