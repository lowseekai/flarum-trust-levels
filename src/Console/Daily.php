<?php

namespace Xypp\Collector\Console;

use Carbon\Carbon;
use Flarum\User\User;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputArgument;
use Illuminate\Events\Dispatcher;
use Xypp\Collector\Event\DailyUpdate;
use Xypp\Collector\Helper\CommandContextHelper;
use Xypp\Collector\Helper\SettingHelper;
use Xypp\Collector\Helper\UpdateAndRecalculateHelper;
use Xypp\LocalizeDate\Helper\CarbonZoneHelper;

class Daily extends Command
{
    /**
     * @var string
     */
    protected $signature = 'collector:daily';

    /**
     * @var string
     */
    protected $description = 'Execute daily update and dispatch daily update event.';

    private $settingHelper;
    private $updateAndRecalculateHelper;
    private $events;
    private $carbonZoneHelper;
    public function __construct(SettingHelper $settingHelper, UpdateAndRecalculateHelper $updateAndRecalculateHelper, Dispatcher $events, CarbonZoneHelper $carbonZoneHelper)
    {
        parent::__construct();
        $this->settingHelper = $settingHelper;
        $this->updateAndRecalculateHelper = $updateAndRecalculateHelper;
        $this->events = $events;
        $this->carbonZoneHelper = $carbonZoneHelper;
    }
    public function handle()
    {
        if ($this->settingHelper->autoUpdate())
            if ($this->carbonZoneHelper->now()->hour == $this->settingHelper->autoUpdateHour()) {
                $this->updateAndRecalculateHelper
                    ->reConfig()
                    ->abs(false)
                    ->overwrite(false)
                    ->updateGlobal()
                    ->update()
                    ->dispatch();

                $this->events->dispatch(new DailyUpdate());
            }
    }
}