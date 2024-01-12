<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChMessages extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::create('ch_messages', function (Blueprint $table) {
            $table->increments('id');
            $table->string('type');
            $table->integer('from_id');
            $table->integer('to_id');
            $table->text('message');
            $table->string('attachment');
            $table->integer('seen')->default('0');
            $table->string('msg_sid');
            $table->string('status')->default('delivered');
            $table->integer('campaign_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ch_messages');
    }
}
