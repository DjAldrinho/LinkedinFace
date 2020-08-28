<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLinkedinApi extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('linkedin_api', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('user_id');
            $table->text('auth_code')->nullable();
            $table->text('auth_token')->nullable();
            $table->text('linkedin_id')->nullable();
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
        Schema::dropIfExists('linkedin_api');
    }
}
