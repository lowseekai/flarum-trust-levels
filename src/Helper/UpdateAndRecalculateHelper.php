<?php

namespace Xypp\Collector\Helper;
use Flarum\User\User;
use Illuminate\Console\Command;
use Illuminate\Events\Dispatcher;
use Xypp\Collector\Condition;
use Xypp\Collector\Data\ConditionAccumulation;
use Xypp\Collector\Data\ConditionData;
use Xypp\Collector\Event\ConditionChange;
use Xypp\Collector\Event\GlobalConditionChange;
use Xypp\Collector\GlobalCondition;

class UpdateAndRecalculateHelper
{

    protected ConditionHelper $conditionHelper;
    protected Dispatcher $events;
    protected SettingHelper $settingHelper;
    protected CommandContextHelper $commandContextHelper;
    protected ?Command $command = null;
    protected bool $overwrite = false;
    protected bool $noDispatch = false;
    protected bool $abs = false;
    protected ?array $names = null;
    protected array $updatedGlobalCondition = [];
    protected array $userConditionChange = [];
    public function __construct(
        ConditionHelper $conditionHelper,
        Dispatcher $events,
        SettingHelper $settingHelper,
        CommandContextHelper $commandContextHelper
    ) {
        $this->conditionHelper = $conditionHelper;
        $this->events = $events;
        $this->settingHelper = $settingHelper;
        $this->commandContextHelper = $commandContextHelper;
    }
    public function reConfig()
    {
        $this->overwrite = false;
        $this->noDispatch = false;
        $this->names = null;
        $this->command = null;
        $this->updatedGlobalCondition = [];
        $this->userConditionChange = [];
        $this->commandContextHelper->clearCommand();
        return $this;
    }
    public function command(Command $command)
    {
        $this->command = $command;
        $this->commandContextHelper->setCommand($command);
        return $this;
    }
    public function abs(bool $abs)
    {
        $this->abs = $abs;
        return $this;
    }
    public function info($text)
    {
        if ($this->command && !$this->command->isHidden())
            $this->command->info($text);
        return $this;
    }
    public function withProgressBar($iter, $callback)
    {
        if ($this->command && !$this->command->isHidden()) {
            $this->command->withProgressBar($iter, $callback);
        } else {
            foreach ($iter as $item) {
                $callback($item);
            }
        }
        return $this;
    }
    public function names(array $names)
    {
        $this->names = $names;
        return $this;
    }
    public function overwrite(bool $overwrite)
    {
        $this->overwrite = $overwrite;
        return $this;
    }

    public function updateGlobal()
    {
        $this->info("Recalculate Global Condition");
        foreach ($this->conditionHelper->getGlobalConditionName() as $conditionDefinitionName) {
            $this->info("Global Condition:$conditionDefinitionName");
            //过滤条件
            if ($this->names && !in_array($conditionDefinitionName, $this->names))
                continue;
            if (!$this->settingHelper->enable($this->abs ? "abs" : "update", $conditionDefinitionName))
                continue;

            //调用更新事件

            $globalConditionDefinition = $this->conditionHelper->getGlobalConditionDefinition($conditionDefinitionName);
            if (
                !(
                    ($this->abs && $globalConditionDefinition->accumulateAbsolute)
                    ||
                    (!$this->abs && $globalConditionDefinition->accumulateUpdate)
                )
            ) {
                if (!$this->overwrite) {
                    $this->info("Skipped");
                    continue;
                }
            }
            $condition = GlobalCondition::where("name", $conditionDefinitionName)->first();
            if (!$condition) {
                $condition = new GlobalCondition();
                $condition->name = $conditionDefinitionName;
            }

            if ($this->abs) {
                $accumulation = new ConditionAccumulation("{}");
                $result = $globalConditionDefinition->getAbsoluteValue($accumulation);
            } else {
                $accumulation = $condition->getAccumulation();
                $result = $globalConditionDefinition->updateValue($accumulation);
            }

            $condition->setAccumulation($accumulation);
            $condition->value = $accumulation->total;
            $condition->updateTimestamps();
            $condition->save();

            if ($result)
                $this->updatedGlobalCondition[$conditionDefinitionName] = $condition;
            $this->info("Done");
        }
        return $this;
    }

    public function update()
    {
        $users = User::all();
        $this->info("Recalculate all conditions for " . $users->count() . " users.");

        // 清空用户条件更新标记
        $this->userConditionChange = [];
        $users->each(function (User $user) use (&$userConditionChange) {
            $this->userConditionChange[$user->id] = [];
        });

        foreach ($this->conditionHelper->getAllConditionName() as $conditionDefinitionName) {
            $this->info("Condition:$conditionDefinitionName");
            if ($this->names && !in_array($conditionDefinitionName, $this->names))
                continue;
            if (!$this->settingHelper->enable($this->abs ? "abs" : "update", $conditionDefinitionName))
                continue;

            $conditionDefinition = $this->conditionHelper->getConditionDefinition($conditionDefinitionName);
            if (
                !(
                    ($this->abs && $conditionDefinition->accumulateAbsolute)
                    ||
                    (!$this->abs && $conditionDefinition->accumulateUpdate)
                )
            ) {
                if (!$this->overwrite) {
                    $this->info("Skipped");
                    continue;
                }
            }

            $this->withProgressBar(
                $users,
                function (User $user) use ($conditionDefinitionName, $conditionDefinition, &$userConditionChange) {
                    $condition = Condition::where("name", $conditionDefinitionName)->where("user_id", $user->id)->first();
                    if (!$condition) {
                        $condition = new Condition();
                        $condition->name = $conditionDefinitionName;
                        $condition->user_id = $user->id;
                    }

                    if ($this->abs) {
                        $accumulation = new ConditionAccumulation("{}");
                        $result = $conditionDefinition->getAbsoluteValue($user, $accumulation);
                    } else {
                        $accumulation = $condition->getAccumulation();
                        $result = $conditionDefinition->updateValue($user, $accumulation);
                    }

                    $condition->setAccumulation($accumulation);
                    $condition->value = $accumulation->total;
                    $condition->updateTimestamps();
                    $condition->save();

                    if ($result)
                        $this->userConditionChange[$user->id][] = $condition;
                }
            );
            $this->info("Done");
        }
        return $this;
    }
    public function dispatch()
    {
        $users = User::all();
        $this->info("Dispatch changes");
        $this->withProgressBar(
            $users,
            function (User $user) use (&$userConditionChange) {
                /**
                 * @var Condition $condition
                 */
                foreach ($this->userConditionChange[$user->id] as $condition) {
                    $this->events->dispatch(new ConditionChange(
                        $user,
                        new ConditionData($condition->name, $condition->value, $condition->getAccumulation()->updateFlag, true),
                        $condition
                    ));
                }
            }
        );
        $this->info("Done");
        foreach ($this->conditionHelper->getGlobalConditionName() as $conditionDefinitionName) {
            if (isset($this->updatedGlobalCondition[$conditionDefinitionName])) {
                $condition = $this->updatedGlobalCondition[$conditionDefinitionName];
                $this->info("Dispatch Global $conditionDefinitionName");
                $this->events->dispatch(new GlobalConditionChange(
                    new ConditionData($condition->name, $condition->value, $condition->getAccumulation()->updateFlag, true),
                    $this->updatedGlobalCondition[$conditionDefinitionName]
                ));
            }
        }
        $this->info("Done");
        return $this;
    }
}