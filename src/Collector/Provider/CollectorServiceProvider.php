<?php

namespace Xypp\Collector\Provider;

use Flarum\Extension\ExtensionManager;
use Flarum\Foundation\AbstractServiceProvider;
use Flarum\Locale\Translator;
use Illuminate\Contracts\Container\Container;
use Xypp\Collector\Extend\ConditionDefinitionCollection;
use Xypp\Collector\Extend\RewardDefinitionCollection;
use Xypp\Collector\Helper\CommandContextHelper;
use Xypp\Collector\Helper\ConditionHelper;
use Xypp\Collector\Helper\RewardHelper;
use Xypp\Collector\Helper\SettingHelper;
use Xypp\Collector\Integration\Conditions\AccountAge;
use Xypp\Collector\Integration\Conditions\ActiveDays;
use Xypp\Collector\Integration\Conditions\BestAnswer;
use Xypp\Collector\Integration\Conditions\DiscussionCount;
use Xypp\Collector\Integration\Conditions\DiscussionViews;
use Xypp\Collector\Integration\Conditions\LikeRecv;
use Xypp\Collector\Integration\Conditions\LikeSend;
use Xypp\Collector\Integration\Conditions\PostCount;
use Xypp\Collector\Integration\Conditions\ReadingTime;
use Xypp\Collector\Integration\Conditions\RepliedDiscussions;
use Xypp\Collector\Integration\Global\GlobalDiscussionCount;
use Xypp\Collector\Integration\Global\GlobalLike;
use Xypp\Collector\Integration\Global\GlobalPostCount;
use Xypp\Collector\Integration\Rewards\BadgeReward;
use Xypp\Collector\Integration\Rewards\MoneyReward;
use Xypp\Collector\Integration\Rewards\StoreItemReward;
use Xypp\Collector\Integration\Rewards\UserGroupReward;

class CollectorServiceProvider extends AbstractServiceProvider
{
    public function register()
    {
        $this->container->singleton(ConditionHelper::class);
        $this->container->singleton(RewardHelper::class);
        $this->container->singleton(CommandContextHelper::class);
        $this->container->singleton(SettingHelper::class);
        $this->container->singleton(ConditionDefinitionCollection::class, function (Container $container) {
            $collector = new ConditionDefinitionCollection(
                $container->make(Translator::class),
                $container->make(SettingHelper::class)
            );

            /** @var ExtensionManager $extensionManager */
            $extensionManager = resolve(ExtensionManager::class);

            // Conditions used by the trust-level rules.
            $collector->addDefinition($container->make(AccountAge::class));
            $collector->addDefinition($container->make(ActiveDays::class));
            $collector->addDefinition($container->make(DiscussionCount::class));
            $collector->addDefinition($container->make(DiscussionViews::class));
            $collector->addDefinition($container->make(PostCount::class));
            $collector->addDefinition($container->make(ReadingTime::class));
            $collector->addDefinition($container->make(RepliedDiscussions::class));

            if ($extensionManager->isEnabled('flarum-likes')) {
                $collector->addDefinition($container->make(LikeRecv::class));
                $collector->addDefinition($container->make(LikeSend::class));
            }

            if ($extensionManager->isEnabled('fof-best-answer')) {
                $collector->addDefinition($container->make(BestAnswer::class));
            }

            // Keep global counters available to the collector internals.
            $collector->addGlobalDefinition($container->make(GlobalDiscussionCount::class));
            $collector->addGlobalDefinition($container->make(GlobalPostCount::class));

            if ($extensionManager->isEnabled('flarum-likes')) {
                $collector->addGlobalDefinition($container->make(GlobalLike::class));
            }

            return $collector;
        });

        $this->container->singleton(RewardDefinitionCollection::class, function (Container $container) {
            $collector = new RewardDefinitionCollection(
                $container->make(Translator::class)
            );

            $collector->addDefinition($container->make(UserGroupReward::class));

            if ($extensionManager->isEnabled('antoinefr-money')) {
                $collector->addDefinition($container->make(MoneyReward::class));
            }

            if ($extensionManager->isEnabled('v17development-user-badges')) {
                $collector->addDefinition($container->make(BadgeReward::class));
            }

            if ($extensionManager->isEnabled('xypp-store')) {
                $collector->addDefinition($container->make(StoreItemReward::class));
            }

            return $collector;
        });
    }
}
