<?php

use Illuminate\Database\Schema\Blueprint;

use Flarum\Database\Migration;

return Migration::createTable(
    'collector_condition_custom_condition',
    function (Blueprint $table) {
        $table->string('name');
        $table->integer("custom_condition_id");
    }
);