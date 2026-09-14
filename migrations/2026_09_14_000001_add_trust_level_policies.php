<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;

return [
    'up' => function (Builder $schema) {
        if (! $schema->hasTable('trust_levels')) {
            return;
        }

        if (! $schema->hasColumn('trust_levels', 'allow_downgrade')) {
            $schema->table('trust_levels', function (Blueprint $table) {
                $table->boolean('allow_downgrade')->default(false);
            });
        }

        if (! $schema->hasColumn('trust_levels', 'downgrade_grace_days')) {
            $schema->table('trust_levels', function (Blueprint $table) {
                $table->unsignedInteger('downgrade_grace_days')->default(0);
            });
        }

        if (! $schema->hasColumn('trust_levels', 'manual_only')) {
            $schema->table('trust_levels', function (Blueprint $table) {
                $table->boolean('manual_only')->default(false);
            });
        }

        // Match the Discourse-style defaults for existing levels.
        $schema->getConnection()->table('trust_levels')
            ->where('level', 3)
            ->update([
                'allow_downgrade' => true,
                'downgrade_grace_days' => 14,
            ]);

        $schema->getConnection()->table('trust_levels')
            ->where('level', '>=', 4)
            ->update([
                'allow_downgrade' => false,
                'manual_only' => true,
            ]);
    },
    'down' => function (Builder $schema) {
        if (! $schema->hasTable('trust_levels')) {
            return;
        }

        foreach (['allow_downgrade', 'downgrade_grace_days', 'manual_only'] as $column) {
            if ($schema->hasColumn('trust_levels', $column)) {
                $schema->table('trust_levels', function (Blueprint $table) use ($column) {
                    $table->dropColumn($column);
                });
            }
        }
    },
];
