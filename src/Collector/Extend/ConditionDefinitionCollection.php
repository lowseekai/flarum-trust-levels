<?php

namespace Xypp\Collector\Extend;

use Flarum\Extend\ExtenderInterface;
use Flarum\Extension\Extension;
use Flarum\Foundation\ValidationException;
use Flarum\Locale\Translator;
use \Illuminate\Contracts\Container\Container;
use Xypp\Collector\ConditionDefinition;
use Xypp\Collector\Custom\Util\AddCustomConditionUtils;
use Xypp\Collector\GlobalConditionDefinition;
use Xypp\Collector\Helper\ConditionHelper;
use Xypp\Collector\Helper\SettingHelper;

class ConditionDefinitionCollection
{
    protected array $conditionsDefinitions = [];
    protected array $globalConditionDefinitions = [];
    public Translator $translator;
    protected SettingHelper $settingHelper;
    protected $hasLoadCustom = false;
    public function __construct(Translator $translator, SettingHelper $settingHelper)
    {
        $this->translator = $translator;
        $this->settingHelper = $settingHelper;
    }
    protected function loadCustom()
    {
        if (!$this->hasLoadCustom)
            if ($this->settingHelper->useCustom())
                AddCustomConditionUtils::addAll($this);
    }
    public function addDefinition(ConditionDefinition $conditionDefinition)
    {
        $this->conditionsDefinitions[$conditionDefinition->name] = $conditionDefinition;
    }
    public function addGlobalDefinition(GlobalConditionDefinition $conditionDefinition)
    {
        $this->globalConditionDefinitions[$conditionDefinition->name] = $conditionDefinition;
    }
    public function getAllConditionName(): array
    {
        $this->loadCustom();
        return array_keys($this->conditionsDefinitions);
    }
    public function getGlobalConditionName(): array
    {
        return array_keys($this->globalConditionDefinitions);
    }
    public function getConditionDefinition(string $name): ConditionDefinition
    {
        $this->loadCustom();
        if (!isset($this->conditionsDefinitions[$name])) {
            throw new ValidationException([
                "error" => $this->translator->trans("xypp-collector.forum.condition_not_found", ["condition" => $name])
            ]);
        }
        return $this->conditionsDefinitions[$name];
    }
    public function getGlobalConditionDefinition(string $name): GlobalConditionDefinition
    {
        if (!isset($this->globalConditionDefinitions[$name])) {
            throw new ValidationException([
                "error" => $this->translator->trans("xypp-collector.forum.condition_not_found", ["condition" => $name])
            ]);
        }
        return $this->globalConditionDefinitions[$name];
    }

}