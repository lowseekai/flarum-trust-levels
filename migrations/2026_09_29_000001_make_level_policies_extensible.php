<?php

use Illuminate\Database\Schema\Builder;

return [
    'up' => function (Builder $schema) {
        if (! $schema->hasTable('trust_levels')) {
            return;
        }

        // Levels 4+ were previously forced to manual-only by the old
        // hard-coded policy. Make the shipped six-level setup auto-upgrade
        // capable; future manual-only levels are explicit admin choices.
        $schema->getConnection()->table('trust_levels')
            ->where('level', '>=', 4)
            ->update([
                'manual_only' => false,
                'allow_downgrade' => false,
                'downgrade_grace_days' => 0,
            ]);
    },
    'down' => function (Builder $schema) {
        if (! $schema->hasTable('trust_levels')) {
            return;
        }

        $schema->getConnection()->table('trust_levels')
            ->where('level', '>=', 4)
            ->update([
                'manual_only' => true,
                'allow_downgrade' => false,
                'downgrade_grace_days' => 0,
            ]);
    },
];
