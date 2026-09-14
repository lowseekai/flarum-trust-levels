<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;

return [
    'up' => function (Builder $schema) {
        if (! $schema->hasTable('users')) {
            return;
        }

        $schema->table('users', function (Blueprint $table) {
            $table->timestamp('trust_level_changed_at')->nullable();
        });
    },
    'down' => function (Builder $schema) {
        if (! $schema->hasTable('users')) {
            return;
        }

        $schema->table('users', function (Blueprint $table) {
            $table->dropColumn('trust_level_changed_at');
        });
    },
];
