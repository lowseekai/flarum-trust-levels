<?php

namespace Xypp\Collector\Integration\Rewards;

use Flarum\Group\Group;
use Xypp\Collector\RewardDefinition;
use Xypp\Store\Helper\StoreHelper;

class UserGroupReward extends RewardDefinition
{
    public function __construct()
    {
        parent::__construct("group", null, "xypp-collector.ref.integration.reward.group");
    }
    public function perform(\Flarum\User\User $user, $value): bool
    {
        $group = Group::find($value);
        if (!$group)
            return false;
        if ($user->groups()->where('id', $group->id)->exists())
            return true;
        $user->groups()->attach($group);
        return true;
    }
}