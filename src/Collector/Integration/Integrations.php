<?php

use Flarum\Extend;
use Flarum\Extension\ExtensionManager;
use Xypp\Collector\Integration\Listener\BestAnswerListener;
use Xypp\Collector\Integration\Listener\DiscussionCountListener;
use Xypp\Collector\Integration\Listener\LikeEventsListener;
use Xypp\Collector\Integration\Listener\PostCountListener;

/** @var ExtensionManager $extensionManager */
$extensionManager = resolve(ExtensionManager::class);

$events = (new Extend\Event)
    ->subscribe(PostCountListener::class)
    ->subscribe(DiscussionCountListener::class);

if ($extensionManager->isEnabled('flarum-likes') && class_exists(\Flarum\Likes\Event\PostWasLiked::class)) {
    $events->subscribe(LikeEventsListener::class);
}

if ($extensionManager->isEnabled('fof-best-answer') && class_exists(\FoF\BestAnswer\Events\BestAnswerSet::class)) {
    $events->subscribe(BestAnswerListener::class);
}

return [
    $events,
];
