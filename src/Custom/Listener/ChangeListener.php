<?php

namespace Xypp\Collector\Custom\Listener;
use Flarum\User\User;
use Xypp\Collector\Condition;
use Xypp\Collector\Custom\ConditionCustomCondition;
use Xypp\Collector\Event\ConditionChange;
use Xypp\Collector\Event\GlobalConditionChange;
use Xypp\Collector\Helper\SettingHelper;
use Xypp\ForumQuests\Helper\ConditionHelper;

class ChangeListener
{
    protected ConditionHelper $conditionHelper;
    protected SettingHelper $settingHelper;
    public function __construct(ConditionHelper $conditionHelper, SettingHelper $settingHelper)
    {
        $this->conditionHelper = $conditionHelper;
        $this->settingHelper = $settingHelper;
    }
    public function subscribe($events)
    {
        $events->listen(ConditionChange::class, [$this, 'conditionChange']);
        $events->listen(GlobalConditionChange::class, [$this, 'globalConditionChange']);
    }

    public function conditionChange(ConditionChange $event)
    {
        if (!$this->settingHelper->useCustom())
            return;
        if (!$this->settingHelper->normalChangeCustom())
            return;
        $effectedConditions = ConditionCustomCondition::where("name", $event->data->name)->get();
        $effectedConditions->each(function (ConditionCustomCondition $conditionCustom) use ($event) {
            $custom = $conditionCustom->custom()->first();
            if (!$this->settingHelper->enable("event", $custom->name))
                return;
            $this->updateForUser($custom->name, $event->user);
        });
    }

    public function globalConditionChange(GlobalConditionChange $event)
    {
        if (!$this->settingHelper->useCustom())
            return;
        if (!$this->settingHelper->globalChangeCustom())
            return;
        $allUsers = User::all();
        $effectedConditions = ConditionCustomCondition::where("name", $event->data->name)->get();
        $effectedConditions->each(function (ConditionCustomCondition $conditionCustom) use ($allUsers) {
            $custom = $conditionCustom->custom()->first();
            if (!$this->settingHelper->enable("event", $custom->name))
                return;
            $allUsers->each(function (User $user) use ($custom) {
                $this->updateForUser($custom->name, $user);
            });
        });
    }

    protected function updateForUser(string $name, User $user)
    {
        $model = Condition::lockForUpdate()->where('user_id', $user->id)->where('name', $name)->first();
        if (!$model) {
            $model = new Condition();
            $model->name = $name;
            $model->user_id = $user->id;
        }
        $definition = $this->conditionHelper->getConditionDefinition($name);
        if ($definition->updateValue($user, $model->getAccumulation())) {
            $model->value = $model->getAccumulation()->total;
            $model->save();
        }
    }
}