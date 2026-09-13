<?php

namespace Xypp\Collector\Extend;

use Flarum\Extend\ExtenderInterface;
use Flarum\Extension\Extension;
use \Illuminate\Contracts\Container\Container;
use Xypp\Collector\ConditionDefinition;
use Xypp\Collector\GlobalConditionDefinition;
use Xypp\Collector\Helper\ConditionHelper;

class ConditionProvider implements ExtenderInterface
{
    protected array $conditionsDefinitions = [];

    protected array $simpleConditionsDefinitions = [];

    protected array $simpleFeConditionsDefinitions = [];
    protected array $globalConditionsDefinitions = [];
    protected array $simpleGlobalConditionsDefinitions = [];
    protected array $simpleFeGlobalConditionsDefinitions = [];


    public function simple(string $name, ?string $translateKey)
    {
        $this->simpleConditionsDefinitions[] = [$name, $translateKey];
        return $this;
    }
    public function simpleFrontend(string $name, ?string $translateKey)
    {
        $this->simpleFeConditionsDefinitions[] = [$name, $translateKey];
        return $this;
    }
    public function provide($className)
    {
        $this->conditionsDefinitions[] = $className;
        return $this;
    }
    public function global(string $className)
    {
        $this->globalConditionsDefinitions[] = $className;
        return $this;
    }
    public function simpleGlobal(string $name, ?string $translateKey)
    {
        $this->simpleFeGlobalConditionsDefinitions[] = [$name, $translateKey];
        return $this;
    }
    public function simpleFrontendGlobal(string $name, ?string $translateKey)
    {
        $this->simpleGlobalConditionsDefinitions[] = [$name, $translateKey];
        return $this;
    }
    public function extend(Container $container, Extension $extension = null)
    {
        $container->resolving(
            ConditionDefinitionCollection::class,
            function (ConditionDefinitionCollection $collection, Container $container) {
                foreach ($this->conditionsDefinitions as $conditionDefinition) {
                    $obj = $container->make($conditionDefinition);
                    $collection->addDefinition($obj);
                }
                foreach ($this->simpleConditionsDefinitions as $nameDef) {
                    $collection->addDefinition(new ConditionDefinition($nameDef[0], null, $nameDef[1]));
                }
                foreach ($this->simpleFeConditionsDefinitions as $nameDef) {
                    $collection->addDefinition(new ConditionDefinition($nameDef[0], true, $nameDef[1]));
                }

                foreach ($this->globalConditionsDefinitions as $conditionDefinition) {
                    $obj = $container->make($conditionDefinition);
                    $collection->addGlobalDefinition($obj);
                }
                foreach ($this->simpleGlobalConditionsDefinitions as $nameDef) {
                    $collection->addGlobalDefinition(new GlobalConditionDefinition($nameDef[0], null, $nameDef[1]));
                }
                foreach ($this->simpleFeGlobalConditionsDefinitions as $nameDef) {
                    $collection->addGlobalDefinition(new GlobalConditionDefinition($nameDef[0], true, $nameDef[1]));
                }
            }
        );
        return $this;
    }
}