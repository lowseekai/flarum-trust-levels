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

return [
    (new Extend\Frontend('forum'))
        ->js(__DIR__ . '/js/dist/forum.js')
        ->css(__DIR__ . '/less/forum.less'),
    (new Extend\Frontend('admin'))
        ->js(__DIR__ . '/js/dist/admin.js')
        ->css(__DIR__ . '/less/admin.less'),
    new Extend\Locales(__DIR__ . '/locale'),
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
        ->listen(DebugInfo::class, Debug::class),
    (new Extend\Console)
        ->command(UpdateLevel::class),
    (new Extend\Notification)
        ->type(TrustLevelChangeNotification::class, ['alert']),
    (new Extend\Settings)
        ->default("xypp-trust-levels.no-auto-update", false)
];
