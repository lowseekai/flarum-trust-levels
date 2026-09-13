<?php

namespace Xypp\Collector\Console;

use Carbon\Carbon;
use Flarum\User\User;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputArgument;
use Xypp\Collector\Data\ConditionData;
use Xypp\Collector\Event\ConditionChange;
use Xypp\Collector\Event\GlobalConditionChange;
use Xypp\Collector\GlobalCondition;
use Xypp\Collector\Helper\ConditionHelper;
use Xypp\Collector\Condition;
use Illuminate\Events\Dispatcher;
use Xypp\Collector\Helper\SettingHelper;
use Xypp\Collector\Helper\UpdateAndRecalculateHelper;

class UpdateCondition extends Command
{
    /**
     * @var string
     */
    protected $signature = 'collector:update';

    /**
     * @var string
     */
    protected $description = 'Update condition from database.';

    protected UpdateAndRecalculateHelper $updateAndRecalculateHelper;
    public function __construct(UpdateAndRecalculateHelper $updateAndRecalculateHelper)
    {
        parent::__construct();
        $this->addArgument("names", InputArgument::OPTIONAL | InputArgument::IS_ARRAY, "Condition names to update");
        $this->addOption("no-dispatch-update", "a", InputArgument::OPTIONAL, "Not update achievement");
        $this->updateAndRecalculateHelper = $updateAndRecalculateHelper;
    }
    public function handle()
    {
        $names = $this->argument("names");
        if ($names && !is_array($names)) {
            $names = [$names];
        }


        $this->info("Update");
        $this->updateAndRecalculateHelper
            ->reConfig()
            ->command($this)
            ->abs(false)
            ->names($names)
            ->overwrite(false)
            ->updateGlobal()
            ->update();
            
        if (!$this->option("no-dispatch-update"))
            $this->updateAndRecalculateHelper->dispatch();


        $this->info("All Done");
    }
}