<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AppSettings extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('app_settings', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('daily_text_limit'); 
            $table->integer('text_length'); 
            $table->string('site_name'); 
            $table->string('site_email'); 
            $table->string('site_logo'); 
            $table->string('site_favicon'); 
            $table->string('site_description'); 
            $table->string('site_copyright'); 
            $table->string('facebook_url'); 
            $table->string('twitter_url'); 
            $table->string('linkedin_url') ; 
            $table->string('language_option'); 
            $table->string('contact_email'); 
            $table->string('contact_number'); 
            $table->string('instagram_url'); 
            $table->string('notification_settings'); 
            $table->string('help_support_url');
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
        Schema::dropIfExists('app_settings');
        //
    }
}
