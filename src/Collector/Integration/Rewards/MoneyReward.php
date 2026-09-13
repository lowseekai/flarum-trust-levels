<?php

namespace Xypp\Collector\Integration\Rewards;

use AntoineFr\Money\Event\MoneyUpdated;
use Xypp\Collector\RewardDefinition;
use Illuminate\Events\Dispatcher;

class MoneyReward extends RewardDefinition
{
    private Dispatcher $dispatcher;
    public function __construct(Dispatcher $dispatcher)
    {
        parent::__construct("money", null, "xypp-collector.ref.integration.reward.money");
        $this->dispatcher = $dispatcher;
    }
    public function perform(\Flarum\User\User $user, $value): bool
    {
        $user->lockForUpdate()->find($user->id)->increment("money", $value);
        $this->dispatcher->dispatch(new MoneyUpdated($user));
        return true;
    }
}