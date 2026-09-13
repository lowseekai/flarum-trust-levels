<?php

namespace Xypp\Collector\Helper;

use Flarum\Foundation\ValidationException;
use Flarum\Locale\Translator;
use Flarum\User\User;
use Xypp\Collector\Extend\RewardDefinitionCollection;
use Xypp\Collector\RewardDefinition;

class RewardHelper
{
    public RewardDefinitionCollection $collection;
    public function __construct(RewardDefinitionCollection $collection)
    {
        $this->collection = $collection;
    }
    public function getRewardDefinition(string $name): RewardDefinition
    {
        return $this->collection->getRewardDefinition($name);
    }
    public function getAllRewardNames(): array
    {
        return $this->collection->getAllRewardNames();
    }

    public function reward(User $user, string $name, int $value)
    {
        $this->getRewardDefinition($name)->perform($user, $value);
    }
}