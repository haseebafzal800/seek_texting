<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ModelHasPermissions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //model_has_permissions
        Schema::create('model_has_permissions', function (Blueprint $table) {
            $table->integer('permission_id');
            $table->string('model_type');
            $table->integer('model_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('model_has_permissions');
        //
    }
}
