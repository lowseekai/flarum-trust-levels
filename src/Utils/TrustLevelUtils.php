<?php

namespace Xypp\TrustLevels\Utils;

use Flarum\Notification\NotificationSyncer;
use Flarum\User\User;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\DB;
use Xypp\Collector\Condition;
use Xypp\Collector\Event\ConditionChange;
use Xypp\TrustLevels\Event\TrustLevelChange;
use Xypp\TrustLevels\Notification\TrustLevelChangeNotification;
use Xypp\TrustLevels\TrustLevel;
use Xypp\TrustLevels\TrustLevelCondition;

class TrustLevelUtils
{
    public static function normalizeGroupId(mixed $groupId): ?int
    {
        if (is_string($groupId)) {
            $groupId = trim($groupId);
        }

        if (
            $groupId === null
            || $groupId === ''
            || (! is_int($groupId) && ! (is_string($groupId) && preg_match('/^\d+$/D', $groupId)))
        ) {
            return null;
        }

        $groupId = (int) $groupId;

        return $groupId > 0 ? $groupId : null;
    }

    public static function getTrustLevel(User $user): ?TrustLevel
    {
        $trustLevel = $user->relationLoaded('trustLevel')
            ? $user->getRelation('trustLevel')
            : $user->trustLevel;

        if (! $trustLevel) {
            $trustLevel = TrustLevel::query()
                ->where('level', (int) ($user->trust_level ?? 0))
                ->first();
        }

        return $trustLevel ?: TrustLevel::query()->where('level', 0)->first();
    }

    public static function getNextTrustLevel(User|TrustLevel $subject): ?TrustLevel
    {
        $trustLevel = $subject instanceof User ? self::getTrustLevel($subject) : $subject;

        return $trustLevel?->nextLevel();
    }

    public static function getPreviousTrustLevel(User|TrustLevel $subject): ?TrustLevel
    {
        $trustLevel = $subject instanceof User ? self::getTrustLevel($subject) : $subject;

        return $trustLevel?->previousLevel();
    }

    public static function setTrustLevel(User $user, TrustLevel $trustLevel, bool $notify = true): bool
    {
        $currentLevel = self::getTrustLevel($user);
        $storedLevel = (int) ($user->getAttribute('trust_level') ?? 0);

        if (
            $currentLevel
            && (int) $currentLevel->level === (int) $trustLevel->level
            && $storedLevel === (int) $trustLevel->level
        ) {
            self::syncManagedGroup($user, $currentLevel->group_id, $trustLevel->group_id);
            $user->unsetRelation('groups');
            $user->unsetRelation('trustLevel');

            return false;
        }

        DB::transaction(function () use ($user, $trustLevel, $currentLevel) {
            self::syncManagedGroup(
                $user,
                $currentLevel?->group_id,
                $trustLevel->group_id
            );

            $user->trust_level = (int) $trustLevel->level;
            $user->save();
        });

        $user->unsetRelation('groups');
        $user->unsetRelation('trustLevel');

        if ($notify) {
            resolve(Dispatcher::class)->dispatch(new TrustLevelChange($user, $trustLevel, $currentLevel));
            resolve(NotificationSyncer::class)->sync(
                new TrustLevelChangeNotification($trustLevel, $user, $currentLevel),
                [$user]
            );
        }

        return true;
    }

    public static function checkLevel(User $user, ?ConditionChange $changeCondition = null): void
    {
        $visitedLevels = [];

        while (true) {
            $currentLevel = self::getTrustLevel($user);
            $nextLevel = self::getNextTrustLevel($user);
            $currentKey = $currentLevel ? (string) $currentLevel->getKey() : 'none';

            if (isset($visitedLevels[$currentKey])) {
                // Conditions should normally move in one direction. Stop
                // safely if inconsistent data would otherwise cause a cycle.
                if ($currentLevel) {
                    self::setTrustLevel($user, $currentLevel, false);
                }

                break;
            }

            $visitedLevels[$currentKey] = true;

            if ($nextLevel && self::checkConditionRelated($user, $nextLevel, $changeCondition)) {
                self::setTrustLevel($user, $nextLevel);
                continue;
            }

            if (
                $currentLevel
                && (int) $currentLevel->level > 0
                && self::checkConditionRelated($user, $currentLevel, $changeCondition) === false
            ) {
                $previousLevel = self::getPreviousTrustLevel($user);

                if ($previousLevel) {
                    self::setTrustLevel($user, $previousLevel);
                    continue;
                }
            }

            // Repair a missing group relation even when the numeric level did
            // not change, such as after a failed or interrupted downgrade.
            if ($currentLevel) {
                self::setTrustLevel($user, $currentLevel, false);
            }

            break;
        }
    }

    public static function checkConditionRelated(
        User $user,
        TrustLevel $level,
        ?ConditionChange $changedCondition
    ): ?bool {
        if ($changedCondition) {
            if (
                ! TrustLevelCondition::query()
                    ->where('trust_level_id', $level->id)
                    ->where('condition_name', $changedCondition->data->name)
                    ->exists()
            ) {
                return null;
            }

            if (
                ! TrustLevelConditionUtils::checkFirstCondition(
                    $level->conditions ?? [],
                    $changedCondition->condition
                )
            ) {
                return false;
            }
        }

        $conditionNames = TrustLevelCondition::query()
            ->where('trust_level_id', $level->id)
            ->pluck('condition_name')
            ->all();
        $conditions = Condition::query()
            ->whereIn('name', $conditionNames)
            ->where('user_id', $user->id)
            ->get();

        return (bool) TrustLevelConditionUtils::checkConditions(
            $level->conditions ?? [],
            $conditions
        );
    }

    public static function syncUsersForLevel(TrustLevel $trustLevel, ?int $oldGroupId): void
    {
        self::syncUsersForNumericLevel(
            (int) $trustLevel->level,
            $oldGroupId,
            self::normalizeGroupId($trustLevel->group_id)
        );
    }

    public static function syncUsersForNumericLevel(int $level, ?int $oldGroupId, ?int $newGroupId): void
    {
        $oldGroupId = self::normalizeGroupId($oldGroupId);
        $newGroupId = self::normalizeGroupId($newGroupId);

        User::query()
            ->where('trust_level', $level)
            ->chunkById(100, function ($users) use ($oldGroupId, $newGroupId) {
                foreach ($users as $user) {
                    self::syncManagedGroup($user, $oldGroupId, $newGroupId);
                }
            });
    }

    public static function removeUsersForDeletedLevel(TrustLevel $deletedLevel): void
    {
        $replacement = self::getPreviousTrustLevel($deletedLevel);

        if (! $replacement) {
            throw new \RuntimeException('A trust level cannot be deleted without a previous level.');
        }

        self::moveUsersToLevel($deletedLevel, $replacement);

        TrustLevel::query()
            ->where('level', '>', (int) $deletedLevel->level)
            ->orderBy('level')
            ->get()
            ->each(function (TrustLevel $level) {
                self::moveUsersToNumber($level, (int) $level->level - 1);
            });
    }

    protected static function moveUsersToLevel(TrustLevel $from, TrustLevel $to): void
    {
        User::query()
            ->where('trust_level', (int) $from->level)
            ->chunkById(100, function ($users) use ($from, $to) {
                foreach ($users as $user) {
                    self::syncManagedGroup($user, $from->group_id, $to->group_id);
                    $user->trust_level = (int) $to->level;
                    $user->save();
                    $user->unsetRelation('groups');
                    $user->unsetRelation('trustLevel');
                }
            });
    }

    protected static function moveUsersToNumber(TrustLevel $from, int $toLevel): void
    {
        if ((int) $from->level === $toLevel) {
            return;
        }

        User::query()
            ->where('trust_level', (int) $from->level)
            ->chunkById(100, function ($users) use ($from, $toLevel) {
                foreach ($users as $user) {
                    self::syncManagedGroup($user, $from->group_id, $from->group_id);
                    $user->trust_level = $toLevel;
                    $user->save();
                    $user->unsetRelation('trustLevel');
                }
            });
    }

    protected static function syncManagedGroup(User $user, ?int $oldGroupId, ?int $newGroupId): void
    {
        $oldGroupId = self::normalizeGroupId($oldGroupId);
        $newGroupId = self::normalizeGroupId($newGroupId);

        if ($newGroupId !== null) {
            $user->groups()->syncWithoutDetaching([$newGroupId]);
        }

        if ($oldGroupId !== null && $oldGroupId !== $newGroupId) {
            $user->groups()->detach($oldGroupId);
        }

        // Eloquent does not update an already-loaded many-to-many collection
        // after attach/detach. Clear both relations so later serialization,
        // permission checks, and badge rendering see the new group set.
        $user->unsetRelation('groups');
        $user->unsetRelation('visibleGroups');
    }
}
