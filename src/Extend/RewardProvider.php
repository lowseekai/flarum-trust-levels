<?php

namespace Xypp\Collector\Extend;

use Flarum\Extend\ExtenderInterface;
use Flarum\Extension\Extension;
use \Illuminate\Contracts\Container\Container;
use Xypp\Collector\ConditionDefinition;
use Xypp\Collector\Helper\ConditionHelper;
use Xypp\Collector\RewardDefinition;

class RewardProvider implements ExtenderInterface
{
    protected array $conditionsDefinitions = [];

    protected array $simpleConditionsDefinitions = [];

    public function simple(string $name, callable $perform)
    {
        $this->simpleConditionsDefinitions[] = [$name, $perform];
        return $this;
    }
    public function provide($className)
    {
        $this->conditionsDefinitions[] = $className;
        return $this;
    }
    public function extend(Container $container, Extension $extension = null)
    {
        $container->resolving(
            RewardDefinitionCollection::class,
            function (RewardDefinitionCollection $collection, Container $container) {
                foreach ($this->conditionsDefinitions as $conditionDefinition) {
                    $obj = $container->make($conditionDefinition);
                    $obj->extend($container);
                    $collection->addDefinition($obj);
                }
                foreach ($this->simpleConditionsDefinitions as [$name, $perform]) {
                    $obj = new RewardDefinition($name, $perform);
                    $obj->extend($container);
                    $collection->addDefinition($obj);
                }
            }
        );
        return $this;
    }
}