<?php

/*
 * This file is part of xypp/collector.
 *
 * Copyright (c) 2024 小鱼飘飘.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Xypp\Collector;

use Flarum\Extend;
use Xypp\Collector\Console\Daily;
use Xypp\Collector\Console\Debug;
use Xypp\Collector\Console\Migrate;
use Xypp\Collector\Console\RecalculateCondition;
use Xypp\Collector\Listener\ConditionModifierListener;
use Xypp\Collector\Console\UpdateCondition;
use Xypp\Collector\Console\UpdateRefreshCommand;
use Xypp\Collector\Listener\GlobalConditionModifierListener;
use Xypp\Collector\Provider\CollectorServiceProvider;
use Xypp\Collector\Provider\QuestSeriviceProvider;

return array_merge(
    [
        (new Extend\Frontend('forum'))
            ->js(__DIR__ . '/js/dist/forum.js')
            ->css(__DIR__ . '/less/forum.less'),
        (new Extend\Frontend('admin'))
            ->js(__DIR__ . '/js/dist/admin.js')
            ->css(__DIR__ . '/less/admin.less'),
        new Extend\Locales(__DIR__ . '/locale'),
        new Extend\Locales(__DIR__ . '/integration-locale'),
        (new Extend\Event())
            ->listen(\Xypp\Collector\Event\UpdateCondition::class, ConditionModifierListener::class)
            ->listen(\Xypp\Collector\Event\UpdateGlobalCondition::class, GlobalConditionModifierListener::class),
        (new Extend\Routes('api'))
            ->post('/collector-condition', 'collector-condition.trigger', Api\Controller\FrontendConditionUpdateController::class)
            ->get('/collector-condition', 'collector-condition.index', Api\Controller\ListUserConditionsController::class)
            ->get('/collector-data', 'collector-data.index', Api\Controller\GetCollectorDefinitionController::class)
            ->post('/custom-condition', 'custom-condition.add', Api\Controller\AddCustomConditionController::class)
            ->patch('/custom-condition/{id}', 'custom-condition.edit', Api\Controller\EditCustomConditionController::class)
            ->delete('/custom-condition/{id}', 'custom-condition.delete', Api\Controller\DeleteCustomConditionController::class)
            ->get('/custom-condition', 'custom-condition.list', Api\Controller\ListCustomConditionController::class),
        (new Extend\Console())
            ->command(UpdateCondition::class)
            ->command(RecalculateCondition::class)
            ->command(Debug::class)
            ->command(Migrate::class)
            ->command(Daily::class)
            ->schedule(Daily::class, function ($event) {
                $event->hourly();
            }),
        (new Extend\ServiceProvider())
            ->register(CollectorServiceProvider::class),
        (new Extend\Settings)
            ->default("xypp.collector.max_keep", 30)
            ->default('xypp.collector.emit_control', "{}")
            ->default('xypp.collector.auto_update', false)
            ->default('xypp.collector.auto_update_hour', 0)
            ->serializeToForum('xypp.collector.max_keep', "xypp.collector.max_keep"),
    ],
    require __DIR__ . '/src/Integration/Integrations.php',
    require __DIR__ . '/src/Custom/extend.php'
);
