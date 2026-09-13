<?php

use Illuminate\Database\Schema\Blueprint;

use Flarum\Database\Migration;

return Migration::createTable(
    'collector_global_condition',
    function (Blueprint $table) {
        $table->increments('id');
        $table->timestamps();
        $table->string('name');
        $table->integer("value");
        $table->mediumText("accumulation")->nullable();
    }
);