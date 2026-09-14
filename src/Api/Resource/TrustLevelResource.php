<?php

namespace Xypp\TrustLevels\Api\Resource;

use Flarum\Api\Context;
use Flarum\Api\Endpoint;
use Flarum\Api\Resource\AbstractDatabaseResource;
use Flarum\Api\Schema;
use Flarum\Api\Sort\SortColumn;
use Flarum\Foundation\ValidationException;
use Flarum\Group\Group;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;
use Laminas\Diactoros\Response\EmptyResponse;
use Tobyz\JsonApiServer\Context as JsonApiContext;
use Xypp\TrustLevels\TrustLevel;
use Xypp\TrustLevels\Utils\TrustLevelConditionUtils;
use Xypp\TrustLevels\Utils\TrustLevelUtils;

/**
 * @extends AbstractDatabaseResource<TrustLevel>
 */
class TrustLevelResource extends AbstractDatabaseResource
{
    public function type(): string
    {
        return 'trust-levels';
    }

    public function model(): string
    {
        return TrustLevel::class;
    }

    public function scope(Builder $query, JsonApiContext $context): void
    {
        $query->orderBy('level');
    }

    public function endpoints(): array
    {
        return [
            Endpoint\Endpoint::make('sort')
                ->route('POST', '/sort')
                ->authenticated()
                ->admin()
                ->action(function (Context $context): ?object {
                    $sorts = Arr::get($context->body() ?? [], 'sorts', []);

                    if (! is_array($sorts)) {
                        throw new ValidationException([
                            'sorts' => 'The sorts value must be an object.',
                        ]);
                    }

                    $this->sortLevels($sorts);

                    return null;
                })
                ->response(fn () => new EmptyResponse(204)),
            Endpoint\Show::make(),
            Endpoint\Create::make()
                ->authenticated()
                ->admin(),
            Endpoint\Update::make()
                ->authenticated()
                ->admin(),
            Endpoint\Delete::make()
                ->authenticated()
                ->admin(),
            Endpoint\Index::make()
                ->authenticated()
                ->admin()
                ->defaultSort('level'),
        ];
    }

    public function fields(): array
    {
        return [
            Schema\Str::make('name')
                ->requiredOnCreate()
                ->writable()
                ->minLength(1)
                ->maxLength(255)
                ->validate(function ($value, callable $fail) {
                    if (trim((string) $value) === '') {
                        $fail('The name cannot be blank.');
                    }
                }),
            Schema\Str::make('icon')
                ->nullable()
                ->writable(),
            Schema\Arr::make('conditions')
                ->writable()
                ->get(fn (TrustLevel $trustLevel) => $trustLevel->conditions ?? []),
            Schema\Integer::make('group_id')
                ->nullable()
                ->writable()
                ->deserialize(function ($value) {
                    if ($value === null) {
                        return null;
                    }

                    if (is_int($value) || (is_string($value) && preg_match('/^-?\d+$/', $value))) {
                        $value = (int) $value;

                        return $value > 0 ? $value : null;
                    }

                    return $value;
                })
                ->validate(function ($value, callable $fail) {
                    if ($value === null) {
                        return;
                    }

                    if ((int) $value === Group::ADMINISTRATOR_ID) {
                        $fail('The administrator group cannot be assigned to a trust level.');

                        return;
                    }

                    if (! Group::query()->whereKey($value)->exists()) {
                        $fail('The selected group does not exist.');
                    }
                })
                ->set(function (TrustLevel $trustLevel, ?int $value) {
                    $trustLevel->group_id = $value;
                }),
            Schema\Boolean::make('allow_downgrade')
                ->writable()
                ->get(fn (TrustLevel $trustLevel) => (bool) $trustLevel->allow_downgrade)
                ->set(function (TrustLevel $trustLevel, bool $value) {
                    $trustLevel->allow_downgrade = $value;
                }),
            Schema\Integer::make('downgrade_grace_days')
                ->writable()
                ->get(fn (TrustLevel $trustLevel) => max(0, (int) $trustLevel->downgrade_grace_days))
                ->deserialize(function ($value) {
                    if (is_int($value) || (is_string($value) && preg_match('/^\d+$/D', $value))) {
                        return (int) $value;
                    }

                    return $value;
                })
                ->validate(function ($value, callable $fail) {
                    if (! is_int($value) || $value < 0 || $value > 3650) {
                        $fail('The downgrade grace period must be between 0 and 3650 days.');
                    }
                })
                ->set(function (TrustLevel $trustLevel, int $value) {
                    $trustLevel->downgrade_grace_days = $value;
                }),
            Schema\Boolean::make('manual_only')
                ->writable()
                ->get(fn (TrustLevel $trustLevel) => (bool) $trustLevel->manual_only)
                ->set(function (TrustLevel $trustLevel, bool $value) {
                    $trustLevel->manual_only = $value;
                }),
            Schema\Integer::make('level')
                ->get(fn (TrustLevel $trustLevel) => (int) $trustLevel->level),
            Schema\Relationship\ToOne::make('next')
                ->type('trust-levels')
                ->includable()
                ->get(fn (TrustLevel $trustLevel) => $trustLevel->nextLevel()),
        ];
    }

    public function sorts(): array
    {
        return [
            SortColumn::make('level'),
        ];
    }

    public function newModel(JsonApiContext $context): object
    {
        $trustLevel = parent::newModel($context);
        $trustLevel->icon = '';
        $trustLevel->conditions = [];

        $maxLevel = TrustLevel::query()->max('level');
        $nextLevel = $maxLevel === null ? 0 : ((int) $maxLevel + 1);
        $trustLevel->allow_downgrade = $nextLevel === 3;
        $trustLevel->downgrade_grace_days = $nextLevel === 3 ? 14 : 0;
        $trustLevel->manual_only = $nextLevel >= 4;

        return $trustLevel;
    }

    public function creating(object $model, JsonApiContext $context): ?object
    {
        /** @var TrustLevel $model */
        $maxLevel = TrustLevel::query()->max('level');
        $model->level = $maxLevel === null ? 0 : ((int) $maxLevel + 1);

        return $model;
    }

    public function saving(object $model, JsonApiContext $context): ?object
    {
        /** @var TrustLevel $model */
        $model->groupIdBeforeSave = $model->exists
            ? TrustLevelUtils::normalizeGroupId($model->getRawOriginal('group_id'))
            : null;
        $model->name = trim((string) $model->name);
        $model->icon = trim((string) ($model->icon ?? ''));
        $model->conditions = is_array($model->conditions) ? array_values($model->conditions) : [];
        $model->group_id = TrustLevelUtils::normalizeGroupId($model->group_id);
        $this->applyPolicyConstraints($model);

        return $model;
    }

    public function saved(object $model, JsonApiContext $context): ?object
    {
        /** @var TrustLevel $model */
        TrustLevelConditionUtils::updateTrustLevelCondition($model);

        TrustLevelUtils::syncUsersForLevel($model, $model->groupIdBeforeSave);

        $model->groupIdBeforeSave = null;

        return $model;
    }

    public function deleting(object $model, JsonApiContext $context): void
    {
        /** @var TrustLevel $model */
        if ((int) $model->level === 0) {
            throw new ValidationException([
                'level' => 'The base trust level cannot be deleted.',
            ]);
        }
    }

    public function delete(object $model, JsonApiContext $context): void
    {
        /** @var TrustLevel $model */
        DB::transaction(function () use ($model) {
            TrustLevelUtils::removeUsersForDeletedLevel($model);
            TrustLevelConditionUtils::removeTrustLevelCondition($model);

            $level = (int) $model->level;
            $model->delete();

            TrustLevel::query()
                ->where('level', '>', $level)
                ->orderBy('level')
                ->get()
                ->each(function (TrustLevel $trustLevel) {
                    $trustLevel->level = (int) $trustLevel->level - 1;
                    $this->applyPolicyConstraints($trustLevel);
                    $trustLevel->save();
                });
        });
    }

    protected function sortLevels(array $sorts): void
    {
        if (empty($sorts)) {
            return;
        }

        $levels = TrustLevel::query()->get()->keyBy(fn (TrustLevel $level) => (string) $level->id);

        foreach ($sorts as $id => $level) {
            if (
                ! $levels->has((string) $id)
                || (! is_int($level) && ! (is_string($level) && preg_match('/^-?\d+$/D', $level)))
            ) {
                throw new ValidationException([
                    'sorts' => 'The submitted trust level order is invalid.',
                ]);
            }
        }

        $finalLevels = [];

        foreach ($levels as $id => $trustLevel) {
            $finalLevels[$id] = array_key_exists($id, $sorts)
                ? (int) $sorts[$id]
                : (int) $trustLevel->level;
        }

        $expected = range(0, count($finalLevels) - 1);
        $actual = array_values($finalLevels);
        sort($actual);

        if ($actual !== $expected || count($actual) !== count(array_unique($actual))) {
            throw new ValidationException([
                'sorts' => 'Trust levels must be ordered from 0 without gaps or duplicates.',
            ]);
        }

        $baseLevel = $levels->first(fn (TrustLevel $trustLevel) => (int) $trustLevel->level === 0);

        if (! $baseLevel || (int) $finalLevels[(string) $baseLevel->getKey()] !== 0) {
            throw new ValidationException([
                'sorts' => 'The base trust level must remain at level 0.',
            ]);
        }

        $oldGroupsByLevel = [];
        $newGroupsByLevel = [];

        foreach ($levels as $id => $trustLevel) {
            $oldGroupsByLevel[(int) $trustLevel->level] = TrustLevelUtils::normalizeGroupId($trustLevel->group_id);
            $newGroupsByLevel[$finalLevels[$id]] = TrustLevelUtils::normalizeGroupId($trustLevel->group_id);
        }

        DB::transaction(function () use ($levels, $finalLevels, $oldGroupsByLevel, $newGroupsByLevel) {
            foreach ($levels as $id => $trustLevel) {
                $newLevel = $finalLevels[$id];

                if ((int) $trustLevel->level !== $newLevel) {
                    $trustLevel->level = $newLevel;
                }

                $this->applyPolicyConstraints($trustLevel);
                $trustLevel->save();
            }

            // Users store the numeric level, so swapping level records also
            // swaps the group that belongs to each numeric slot.
            foreach ($newGroupsByLevel as $level => $newGroupId) {
                TrustLevelUtils::syncUsersForNumericLevel(
                    (int) $level,
                    $oldGroupsByLevel[$level] ?? null,
                    $newGroupId
                );
            }
        });
    }

    protected function applyPolicyConstraints(TrustLevel $trustLevel): void
    {
        $level = (int) $trustLevel->level;

        if ($level < 3) {
            $trustLevel->allow_downgrade = false;
            $trustLevel->downgrade_grace_days = 0;
            $trustLevel->manual_only = false;

            return;
        }

        $trustLevel->downgrade_grace_days = max(0, min(3650, (int) $trustLevel->downgrade_grace_days));

        if ($level >= 4) {
            $trustLevel->allow_downgrade = false;
            $trustLevel->downgrade_grace_days = 0;
            $trustLevel->manual_only = true;

            return;
        }

        $trustLevel->manual_only = (bool) $trustLevel->manual_only;
        $trustLevel->allow_downgrade = ! $trustLevel->manual_only && (bool) $trustLevel->allow_downgrade;
    }
}
