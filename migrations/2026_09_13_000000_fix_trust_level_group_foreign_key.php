<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;

$replaceGroupForeignKey = function (Builder $schema, string $onDelete): void {
    if (! $schema->hasTable('trust_levels')) {
        return;
    }

    $existingForeignKey = null;

    foreach ($schema->getForeignKeys('trust_levels') as $foreignKey) {
        if ($foreignKey['columns'] === ['group_id']) {
            $existingForeignKey = $foreignKey;
            break;
        }
    }

    $schema->table('trust_levels', function (Blueprint $table) use ($existingForeignKey, $onDelete) {
        if ($existingForeignKey) {
            $table->dropForeign($existingForeignKey['name'] ?: ['group_id']);
        }

        $table->foreign('group_id')
            ->references('id')
            ->on('groups')
            ->onDelete($onDelete);
    });
};

return [
    'up' => fn (Builder $schema) => $replaceGroupForeignKey($schema, 'set null'),
    'down' => fn (Builder $schema) => $replaceGroupForeignKey($schema, 'cascade'),
];
