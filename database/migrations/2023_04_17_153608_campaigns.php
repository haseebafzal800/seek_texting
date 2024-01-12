<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Campaigns extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('campaigns', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title');
            $table->enum('type', ['email', 'sms'])->default('sms');
            $table->enum('status', ['pending', 'success', 'cancel', 'fail'])->default('pending');
            $table->integer('user_id');
            $table->enum('campaign_interval', ['one time', 'daily', 'weekly', 'monthly', 'annually'])->nullable();
            $table->string('campaign_start_time');
            $table->string('campaign_start_date');
            $table->string('campaign_end_date');
            $table->string('campaign_time_zone');
            $table->string('tags');
            $table->text('subject_line');
            $table->text('preview_text');
            $table->text('message');
            $table->text('campaign_send_to_emails');
            $table->text('campaign_not_send_to_emails');
            $table->text('campaign_send_to_list_ids');
            $table->string('sender_name');
            $table->string('sender_email');
            $table->enum('sender_email_as_reply_to', ['no', 'yes'])->default('no');
            $table->enum('is_deleted', ['0', '1'])->default('0');
            $table->enum('send_now', ['false', 'true'])->default('false');
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
        Schema::dropIfExists('campaigns');
    }
}
