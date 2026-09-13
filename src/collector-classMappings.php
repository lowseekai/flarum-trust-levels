<?php
use Xypp\Collector\Condition;
use Xypp\Collector\ConditionDefinition;
use Xypp\Collector\Data\ConditionAccumulation;
use Xypp\Collector\Data\ConditionData;
use Xypp\Collector\Extend\ConditionProvider;
use Xypp\Collector\Extend\RewardProvider;
use Xypp\Collector\Helper\ConditionHelper;
use Xypp\Collector\Helper\RewardHelper;
use Xypp\Collector\RewardDefinition;
foreach ([
    ConditionAccumulation::class => 'Xypp\\ForumQuests\\Data\\ConditionAccumulation',
    ConditionData::class => 'Xypp\\ForumQuests\\Data\\ConditionData',
    ConditionHelper::class => 'Xypp\\ForumQuests\\Helper\\ConditionHelper',
    RewardHelper::class => 'Xypp\\ForumQuests\\Helper\\RewardHelper',
    ConditionProvider::class => 'Xypp\\ForumQuests\\Extend\\ConditionProvider',
    RewardProvider::class => 'Xypp\\ForumQuests\\Extend\\RewardProvider',
    Condition::class => 'Xypp\\ForumQuests\\QuestCondition',
    RewardDefinition::class => 'Xypp\\ForumQuests\\RewardDefinition',
    ConditionDefinition::class => 'Xypp\\ForumQuests\\ConditionDefinition',
] as $source => $alias) {
    if (!class_exists($alias, false) && !interface_exists($alias, false)) {
        class_alias($source, $alias, true);
    }
}
