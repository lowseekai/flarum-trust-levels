<?php

namespace Xypp\Collector\Provider;

use Flarum\Extension\ExtensionManager;
use Flarum\Foundation\AbstractServiceProvider;
use Flarum\Locale\Translator;
use Xypp\Collector\ConditionDefinition;
use Xypp\Collector\Custom\Util\AddCustomConditionUtils;
use Xypp\Collector\Extend\ConditionDefinitionCollection;
use Xypp\Collector\Extend\RewardDefinitionCollection;
use Xypp\Collector\Helper\CommandContextHelper;
use Xypp\Collector\Helper\ConditionHelper;
use Xypp\Collector\Helper\RewardHelper;
use Illuminate\Contracts\Container\Container;
use Xypp\Collector\Helper\SettingHelper;
use Xypp\Collector\Integration\Conditions\BadgeReceived;
use Xypp\Collector\Integration\Conditions\BestAnswer;
use Xypp\Collector\Integration\Conditions\DiscussionCount;
use Xypp\Collector\Integration\Conditions\DiscussionReplied;
use Xypp\Collector\Integration\Conditions\DiscussionViews;
use Xypp\Collector\Integration\Conditions\LikeRecv;
use Xypp\Collector\Integration\Conditions\LikeSend;
use Xypp\Collector\Integration\Conditions\ModeratorWarnings;
use Xypp\Collector\Integration\Conditions\ModeratorWarningStrikes;
use Xypp\Collector\Integration\Conditions\Money;
use Xypp\Collector\Integration\Conditions\PostCount;
use Xypp\Collector\Integration\Conditions\StoreItemPurchase;
use Xypp\Collector\Integration\Conditions\ValidDiscussionCount;
use Xypp\Collector\Integration\Conditions\ValidPostCount;
use Xypp\Collector\Integration\Global\GlobalDiscussionCount;
use Xypp\Collector\Integration\Global\GlobalPostCount;
use Xypp\Collector\Integration\Global\GlobalValidDiscussionCount;
use Xypp\Collector\Integration\Global\GlobalValidPostCount;
use Xypp\Collector\Integration\Global\GlobalLike;
use Xypp\Collector\Integration\Rewards\BadgeReward;
use Xypp\Collector\Integration\Rewards\MoneyReward;
use Xypp\Collector\Integration\Rewards\StoreItemReward;
use Xypp\Collector\Integration\Rewards\UserGroupReward;
use Xypp\Collector\RewardDefinition;

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
            /**
             * @var ExtensionManager $extensionManager
             */
            $extensionManager = resolve(ExtensionManager::class);
            // Core features
            $collector->addDefinition(new ConditionDefinition("user_page_view", true, "xypp-collector.ref.integration.condition.user_page_view"));
            $collector->addDefinition(new ConditionDefinition("reloads", null, "xypp-collector.ref.integration.condition.reloads"));
            $collector->addDefinition(new ConditionDefinition("email_changed", null, "xypp-collector.ref.integration.condition.email_changed"));
            $collector->addDefinition(new ConditionDefinition("avatar_changed", null, "xypp-collector.ref.integration.condition.avatar_changed"));

            $collector->addDefinition($container->make(DiscussionCount::class));
            $collector->addDefinition($container->make(PostCount::class));
            $collector->addDefinition($container->make(DiscussionReplied::class));

            $collector->addGlobalDefinition($container->make(GlobalDiscussionCount::class));
            $collector->addGlobalDefinition($container->make(GlobalPostCount::class));

            // Integrate with AntoineFr/money
            if ($extensionManager->isEnabled("antoinefr-money"))
                $collector->addDefinition($container->make(Money::class));

            // Integrate with michaelbelgium/flarum-discussion-views
            if ($extensionManager->isEnabled("michaelbelgium-discussion-views"))
                $collector->addDefinition($container->make(DiscussionViews::class));

            // Integrate with flarum-likes
            if ($extensionManager->isEnabled("flarum-likes")) {
                $collector->addDefinition($container->make(LikeRecv::class));
                $collector->addDefinition($container->make(LikeSend::class));
                $collector->addGlobalDefinition($container->make(GlobalLike::class));
            }

            // Integrate with xypp-store
            if ($extensionManager->isEnabled("xypp-store"))
                $collector->addDefinition($container->make(StoreItemPurchase::class));

            // v17development/flarum-user-badges
            if ($extensionManager->isEnabled("v17development-user-badges"))
                $collector->addDefinition($container->make(BadgeReceived::class));

            // fof/best-answer
            if ($extensionManager->isEnabled("fof-best-answer"))
                $collector->addDefinition($container->make(BestAnswer::class));
            // flarum/tags
            if ($extensionManager->isEnabled("flarum-tags")) {
                $collector->addDefinition($container->make(ValidPostCount::class));
                $collector->addDefinition($container->make(ValidDiscussionCount::class));
                $collector->addGlobalDefinition($container->make(GlobalValidPostCount::class));
                $collector->addGlobalDefinition($container->make(GlobalValidDiscussionCount::class));
            }

            if ($extensionManager->isEnabled("askvortsov-moderator-warnings")) {
                $collector->addDefinition($container->make(ModeratorWarnings::class));
                $collector->addDefinition($container->make(ModeratorWarningStrikes::class));
            }
            return $collector;
        });
        $this->container->singleton(RewardDefinitionCollection::class, function (Container $container) {
            $collector = new RewardDefinitionCollection(
                $container->make(Translator::class)
            );

            $extensionManager = resolve(ExtensionManager::class);

            $collector->addDefinition($container->make(UserGroupReward::class));

            // Integrate with AntoineFr/money
            if ($extensionManager->isEnabled("antoinefr-money"))
                $collector->addDefinition($container->make(MoneyReward::class));

            // Integrate with v17development/flarum-user-badges
            if ($extensionManager->isEnabled("v17development-user-badges"))
                $collector->addDefinition($container->make(BadgeReward::class));

            // Integrate with xypp-store
            if ($extensionManager->isEnabled("xypp-store"))
                $collector->addDefinition($container->make(StoreItemReward::class));

            return $collector;
        });
    }
}