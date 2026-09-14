<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;
use Illuminate\Support\Facades\DB;

return [
    'up' => function (Builder $schema) {
        if (! $schema->hasTable('trust_levels')) {
            return;
        }

        $schema->table('trust_levels', function (Blueprint $table) {
            $table->boolean('allow_downgrade')->default(false);
            $table->unsignedInteger('downgrade_grace_days')->default(0);
            $table->boolean('manual_only')->default(false);
        });

        // Match the Discourse-style defaults for existing levels.
        DB::table('trust_levels')
            ->where('level', 3)
            ->update([
                'allow_downgrade' => true,
                'downgrade_grace_days' => 14,
            ]);

        DB::table('trust_levels')
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

        $schema->table('trust_levels', function (Blueprint $table) {
            $table->dropColumn([
                'allow_downgrade',
                'downgrade_grace_days',
                'manual_only',
            ]);
        });
    },
];
