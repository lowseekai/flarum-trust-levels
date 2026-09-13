<?php

use Flarum\Extend;
use Xypp\Collector\Custom\Listener\ChangeListener;
use Xypp\Collector\Custom\Listener\DateChangeListener;
use Xypp\LocalizeDate\Event\DateChangeEvent;

return [
    (new Extend\Event)
        ->subscribe(ChangeListener::class),
    (new Extend\Settings)
        ->default("xypp.collector.custom-global-update", false)
        ->default("xypp.collector.use_custom", false),
];