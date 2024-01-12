<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Lists extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('lists', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name'); 
            $table->enum('status', ['active','de-active'])->default('active'); 
            $table->string('category_id');
            $table->integer('user_id');
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
        Schema::dropIfExists('lists');
    }
}
