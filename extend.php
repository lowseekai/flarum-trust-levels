<?php

/*
 * This file is part of xypp/flarum-trust-levels.
 *
 * Copyright (c) 2024 小鱼飘飘.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Xypp\TrustLevels;

use Xypp\Collector\Console\Daily as CollectorDaily;
use Xypp\Collector\Console\Debug as CollectorDebug;
use Xypp\Collector\Console\Migrate as CollectorMigrate;
use Xypp\Collector\Console\RecalculateCondition;
use Xypp\Collector\Console\UpdateCondition;
use Xypp\Collector\Api\Controller\AddCustomConditionController;
use Xypp\Collector\Api\Controller\DeleteCustomConditionController;
use Xypp\Collector\Api\Controller\EditCustomConditionController;
use Xypp\Collector\Api\Controller\FrontendConditionUpdateController;
use Xypp\Collector\Api\Controller\GetCollectorDefinitionController;
use Xypp\Collector\Api\Controller\ListCustomConditionController;
use Xypp\Collector\Api\Controller\ListUserConditionsController;
use Xypp\Collector\Listener\ConditionModifierListener;
use Xypp\Collector\Listener\GlobalConditionModifierListener;
use Xypp\Collector\Provider\CollectorServiceProvider;
use Flarum\Api\Endpoint;
use Flarum\Api\Resource\NotificationResource;
use Flarum\Api\Resource\UserResource;
use Flarum\Api\Schema;
use Flarum\Extend;
use Flarum\User\User;
use Xypp\Collector\Event\ConditionChange;
use Xypp\Collector\Event\DailyUpdate;
use Xypp\Collector\Event\DebugInfo;
use Xypp\TrustLevels\Api\Resource\TrustLevelResource;
use Xypp\TrustLevels\Console\UpdateLevel;
use Xypp\TrustLevels\Listener\DayChange;
use Xypp\TrustLevels\Listener\Debug;
use Xypp\TrustLevels\Notification\TrustLevelChangeNotification;
use Xypp\TrustLevels\Utils\TrustLevelUtils;

return array_merge([
    (new Extend\Frontend('forum'))
        ->js(__DIR__ . '/js/dist/forum.js')
        ->css(__DIR__ . '/less/forum.less')
        ->css(__DIR__ . '/less/collector/forum.less'),
    (new Extend\Frontend('admin'))
        ->js(__DIR__ . '/js/dist/admin.js')
        ->css(__DIR__ . '/less/admin.less')
        ->css(__DIR__ . '/less/collector/admin.less'),
    new Extend\Locales(__DIR__ . '/locale'),
    new Extend\Locales(__DIR__ . '/locale/collector'),
    new Extend\Locales(__DIR__ . '/locale/collector-integration'),
    (new Extend\Model(User::class))
        ->hasOne('trustLevel', TrustLevel::class, "level", "trust_level"),
    new Extend\ApiResource(TrustLevelResource::class),
    (new Extend\ApiResource(UserResource::class))
        ->fields(function () {
            return [
                Schema\Relationship\ToOne::make('trustLevel')
                    ->type('trust-levels')
                    ->includable()
                    ->get(fn (User $user) => TrustLevelUtils::getTrustLevel($user)),
            ];
        })
        ->endpoint(['show', 'index', 'update'], function (Endpoint\Endpoint $endpoint) {
            return $endpoint->eagerLoad('trustLevel');
        }),
    (new Extend\ApiResource(NotificationResource::class))
        // Notifications render the sender's badges. The core notification
        // resource only includes fromUser, so nested group resources must be
        // part of the default include as well.
        ->endpoint([Endpoint\Show::class, Endpoint\Index::class], function (Endpoint\Show|Endpoint\Index $endpoint): Endpoint\Show|Endpoint\Index {
            return $endpoint
                ->addDefaultInclude(['fromUser.groups'])
                ->eagerLoad('fromUser.groups');
        }),
    (new Extend\Event)
        ->listen(ConditionChange::class, \Xypp\TrustLevels\Listener\ConditionChange::class)
        ->listen(DailyUpdate::class, DayChange::class)
        ->listen(DebugInfo::class, Debug::class)
        ->listen(\Xypp\Collector\Event\UpdateCondition::class, ConditionModifierListener::class)
        ->listen(\Xypp\Collector\Event\UpdateGlobalCondition::class, GlobalConditionModifierListener::class),
    (new Extend\Routes('api'))
        ->post('/collector-condition', 'collector-condition.trigger', FrontendConditionUpdateController::class)
        ->get('/collector-condition', 'collector-condition.index', ListUserConditionsController::class)
        ->get('/collector-data', 'collector-data.index', GetCollectorDefinitionController::class)
        ->post('/custom-condition', 'custom-condition.add', AddCustomConditionController::class)
        ->patch('/custom-condition/{id}', 'custom-condition.edit', EditCustomConditionController::class)
        ->delete('/custom-condition/{id}', 'custom-condition.delete', DeleteCustomConditionController::class)
        ->get('/custom-condition', 'custom-condition.list', ListCustomConditionController::class),
    (new Extend\Console)
        ->command(UpdateLevel::class)
        ->command(UpdateCondition::class)
        ->command(RecalculateCondition::class)
        ->command(CollectorDebug::class)
        ->command(CollectorMigrate::class)
        ->command(CollectorDaily::class)
        ->schedule(CollectorDaily::class, function ($event) {
            $event->hourly();
        }),
    (new Extend\ServiceProvider())
        ->register(CollectorServiceProvider::class),
    (new Extend\Notification)
        ->type(TrustLevelChangeNotification::class, ['alert']),
    (new Extend\Settings)
        ->default("xypp-trust-levels.no-auto-update", false)
        ->default("xypp.collector.max_keep", 30)
        ->default("xypp.collector.emit_control", "{}")
        ->default("xypp.collector.auto_update", false)
        ->default("xypp.collector.auto_update_hour", 0)
        ->default("xypp.localize-date.timezone", "UTC")
        ->serializeToForum("xypp.collector.max_keep", "xypp.collector.max_keep")
        ->serializeToForum("xypp.localize-date.timezone", "xypp.localize-date.timezone")
], require __DIR__ . '/src/Collector/Integration/Integrations.php',
    require __DIR__ . '/src/Collector/Custom/extend.php');
