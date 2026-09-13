<?php

use Illuminate\Database\Schema\Blueprint;

use Flarum\Database\Migration;

return Migration::createTable(
    'collector_custom_condition',
    function (Blueprint $table) {
        $table->increments('id');
        $table->string('name');
        $table->string("display_name");
        $table->text("evaluation");
    }
);