<?php

use AntoineFr\Money\Event\MoneyUpdated;
use Flarum\Discussion\Event\Started;
use Flarum\Extend;
use Flarum\Post\Event\Posted;
use Flarum\Tags\Event\DiscussionWasTagged;
use Michaelbelgium\Discussionviews\Events\DiscussionWasViewed;
use Xypp\Collector\Integration\ControllerCheck\ModeratorWarnings;
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
use Xypp\Store\Event\PurchaseDone;

$ret = [
    (new Extend\Event)
        ->subscribe(PostCountListener::class)
        ->subscribe(DiscussionCountListener::class)
        ->subscribe(UserEventsListener::class)
        //Integrate with AntoineFr/money
        ->listen(MoneyUpdated::class, MoneyChangeListener::class)
        //Integrate with michaelbelgium/flarum-discussion-views
        ->listen(DiscussionWasViewed::class, DiscussionViewed::class)
        //Integrate with xypp/store
        ->listen(PurchaseDone::class, StoreEventListener::class)
        //Integrate with flarum/likes
        ->subscribe(LikeEventsListener::class)
        //Integrate with fof/best-answer
        ->subscribe(BestAnswerListener::class)
        //Integrate with flarum/tags
        ->subscribe(DiscussionTagListener::class)
    ,
    (new Extend\Middleware("forum"))
        ->add(ApiVisitCheck::class),

    (new Extend\Settings)
        ->default("xypp.collector.invalid_tags", "{}")
];

return $ret;
