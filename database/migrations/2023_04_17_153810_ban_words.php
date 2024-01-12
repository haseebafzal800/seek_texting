<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class BanWords extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::create('banned_words', function (Blueprint $table) {
            $table->increments('id');
            $table->string('phrase');
            $table->enum('status', ['active','de-active'])->default('active'); 
            $table->enum('is_deleted', ['0', '1'])->default('0'); 
            $table->softDeletes();
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
        Schema::dropIfExists('banned_words');
        //
    }
}
