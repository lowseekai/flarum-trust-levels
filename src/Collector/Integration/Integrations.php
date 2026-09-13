<?php

use Flarum\Extend;
use Flarum\Extension\ExtensionManager;
use Xypp\Collector\Integration\Listener\BestAnswerListener;
use Xypp\Collector\Integration\Listener\DiscussionCountListener;
use Xypp\Collector\Integration\Listener\DiscussionTagListener;
use Xypp\Collector\Integration\Listener\DiscussionViewed;
use Xypp\Collector\Integration\Listener\LikeEventsListener;
use Xypp\Collector\Integration\Listener\MoneyChangeListener;
use Xypp\Collector\Integration\Listener\PostCountListener;
use Xypp\Collector\Integration\Listener\StoreEventListener;
use Xypp\Collector\Integration\Listener\UserEventsListener;
use Xypp\Collector\Integration\Middleware\ApiVisitCheck;
/** @var ExtensionManager $extensionManager */
$extensionManager = resolve(ExtensionManager::class);

$events = (new Extend\Event)
    ->subscribe(PostCountListener::class)
    ->subscribe(DiscussionCountListener::class)
    ->subscribe(UserEventsListener::class);

if ($extensionManager->isEnabled('antoinefr-money') && class_exists(\AntoineFr\Money\Event\MoneyUpdated::class)) {
    $events->listen(\AntoineFr\Money\Event\MoneyUpdated::class, MoneyChangeListener::class);
}

if ($extensionManager->isEnabled('michaelbelgium-discussion-views') && class_exists(\Michaelbelgium\Discussionviews\Events\DiscussionWasViewed::class)) {
    $events->listen(\Michaelbelgium\Discussionviews\Events\DiscussionWasViewed::class, DiscussionViewed::class);
}

if ($extensionManager->isEnabled('xypp-store') && class_exists(\Xypp\Store\Event\PurchaseDone::class)) {
    $events->listen(\Xypp\Store\Event\PurchaseDone::class, StoreEventListener::class);
}

if ($extensionManager->isEnabled('flarum-likes') && class_exists(\Flarum\Likes\Event\PostWasLiked::class)) {
    $events->subscribe(LikeEventsListener::class);
}

if ($extensionManager->isEnabled('fof-best-answer') && class_exists(\FoF\BestAnswer\Events\BestAnswerSet::class)) {
    $events->subscribe(BestAnswerListener::class);
}

if ($extensionManager->isEnabled('flarum-tags') && class_exists(\Flarum\Tags\Tag::class)) {
    $events->subscribe(DiscussionTagListener::class);
}

return [
    $events,
    (new Extend\Middleware("forum"))
        ->add(ApiVisitCheck::class),

    (new Extend\Settings)
        ->default("xypp.collector.invalid_tags", "{}")
];
