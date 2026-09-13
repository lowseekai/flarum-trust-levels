<?php

namespace Xypp\Collector\Console;

use Flarum\User\User;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputArgument;
use Xypp\Collector\Helper\ConditionHelper;
use Illuminate\Events\Dispatcher;
use Xypp\Collector\Helper\SettingHelper;
use Xypp\Collector\Helper\UpdateAndRecalculateHelper;

class RecalculateCondition extends Command
{
    /**
     * @var string
     */
    protected $signature = 'collector:recalculate';

    /**
     * @var string
     */
    protected $description = 'Recalculate condition from database absolutely.';
    private UpdateAndRecalculateHelper $updateAndRecalculateHelper;
    public function __construct(UpdateAndRecalculateHelper $updateAndRecalculateHelper)
    {
        parent::__construct();

        $this->addArgument("names", InputArgument::OPTIONAL | InputArgument::IS_ARRAY, "Condition names to update");
        $this->addOption("overwrite", "o", InputArgument::OPTIONAL, "Overwrite item that cannot get absolute accumulation data");
        $this->addOption("no-dispatch-update", "a", InputArgument::OPTIONAL, "Do not dispatch update event");

        $this->updateAndRecalculateHelper = $updateAndRecalculateHelper;
    }
    public function handle()
    {
        $names = $this->argument("names");
        if ($names && !is_array($names)) {
            $names = [$names];
        }

        $this->info("Recalculate");
        $this->updateAndRecalculateHelper
            ->reConfig()
            ->command($this)
            ->abs(true)
            ->names($names)
            ->overwrite($this->option("overwrite") ?? false)
            ->updateGlobal()
            ->update();

        if (!$this->option("no-dispatch-update"))
            $this->updateAndRecalculateHelper->dispatch();

        $this->info("All Done");
    }
}