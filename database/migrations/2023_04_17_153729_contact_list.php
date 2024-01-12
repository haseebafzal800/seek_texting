<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ContactList extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::create('contactlists', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->nullable();
            $table->string('contact');
            $table->string('email')->nullable();
            $table->string('zip_code')->nullable();
            $table->string('notes')->nullable();
            $table->string('state')->nullable();
            $table->string('address')->nullable();
            $table->integer('user_id');
            $table->enum('status', ['active', 'de-active'])->default('active');
            $table->integer('list_id')->nullable();
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
        Schema::dropIfExists('contactlists');
    }
}
