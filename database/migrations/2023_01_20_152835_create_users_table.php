<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->unique();
            $table->string('contact_number')->nullable();
            $table->string('username'); 
            $table->string('address'); 
            $table->string('last_notification_seen'); 
            $table->string('status')->default('active'); 
            $table->string('display_name'); 
            $table->string('timezone'); 
            $table->string('daily_text_limit'); 
            $table->enum('is_deleted', ['0', '1'])->default('0');
            $table->string('text_length');
            $table->string('user_type')->nullable();
            $table->tinyInteger('is_online')->nullable()->default('0');
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
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
        Schema::dropIfExists('users');
    }
}
