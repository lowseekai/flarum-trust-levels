<?php

namespace Xypp\Collector\Console;

use Carbon\Carbon;
use Flarum\User\User;
use Illuminate\Console\Command;
use Illuminate\Events\Dispatcher;
use Symfony\Component\Console\Input\InputArgument;

use Xypp\Collector\Data\ConditionAccumulation;
use Xypp\Collector\Event\DebugInfo;
use Xypp\Collector\Helper\ConditionHelper;
use Xypp\Collector\Condition;
use Xypp\LocalizeDate\Helper\CarbonZoneHelper;
class Debug extends Command
{
    /**
     * @var string
     */
    protected $signature = 'collector:debug';

    /**
     * @var string
     */
    protected $description = 'Check user condition.';

    protected ConditionHelper $conditionHelper;
    public CarbonZoneHelper $cz;
    protected Dispatcher $dispatcher;

    public function __construct(CarbonZoneHelper $carbonZoneHelper, ConditionHelper $conditionHelper, Dispatcher $dispatcher)
    {
        parent::__construct();
        $this->conditionHelper = $conditionHelper;
        $this->cz = $carbonZoneHelper;
        $this->dispatcher = $dispatcher;
        $this->addArgument("id", InputArgument::REQUIRED, "UserId");
    }
    public function handle()
    {
        $user = User::find($this->argument("id"));
        $this->info("User: " . $user->username);
        $now = $this->cz->now();
        $this->info("Now Time: " . $now);
        Condition::where("user_id", $user->id)->get()->each(function ($condition) use ($user) {
            $conditionName = $condition->name;
            $this->info("Condition: " . $conditionName);
            $this->info("\t- Value: " . $condition->value);
            $now = $this->cz->now();

            /**
             * @var ConditionAccumulation $accumulation
             */
            $accumulation = $condition->getAccumulation();
            $this->info("Accumulation(Today): " . $accumulation->getSpan($now, 1, intval($condition->calculate)));
            $this->info("Accumulation(5 days): " . $accumulation->getSpan($now, 5, intval($condition->calculate)));
            $this->info("Accumulation(Total): " . $accumulation->getTotal(intval($condition->calculate)));
        });

        $this->dispatcher->dispatch(new DebugInfo($user, $this));
    }

    protected function getValue(int $span, Condition $condition): ?int
    {
        $currentTime = $this->cz->now();
        if ($span)
            $currentValue = $condition->getAccumulation()->getSpan($currentTime, $span);
        else
            $currentValue = $condition->getAccumulation()->total;
        return $currentValue;
    }
}