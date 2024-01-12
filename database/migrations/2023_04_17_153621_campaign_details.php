<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CampaignDetails extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('campaign_details', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('campaign_id'); 
            $table->enum('status', ['pending','success','cancel','fail'])->default('pending');
            $table->string('schedule_date');
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
        Schema::dropIfExists('campaign_details');
    }
}
