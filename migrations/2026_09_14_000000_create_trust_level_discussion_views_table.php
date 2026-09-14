<?php

use Flarum\Database\Migration;
use Illuminate\Database\Schema\Blueprint;

return Migration::createTableIfNotExists(
    'trust_level_discussion_views',
    function (Blueprint $table) {
        $table->increments('id');
        $table->timestamps();
        $table->integer('user_id')->unsigned();
        $table->integer('discussion_id')->unsigned();
        $table->unique(['user_id', 'discussion_id'], 'trust_level_discussion_views_user_discussion_unique');
        $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        $table->foreign('discussion_id')->references('id')->on('discussions')->onDelete('cascade');
    }
);
