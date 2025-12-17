<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('homepage_translations', function (Blueprint $table) {
            $table->id();
            $table->integer('homepage_id');
            $table->string('lang_code');
            $table->string('intro_title')->nullable();
            $table->text('intro_tags')->nullable();
            $table->string('working_step_title1')->nullable();
            $table->string('working_step_title2')->nullable();
            $table->string('working_step_title3')->nullable();
            $table->string('working_step_title4')->nullable();
            $table->text('join_restaurant_title')->nullable();
            $table->string('join_restaurant_des')->nullable();
            $table->string('mobile_app_title')->nullable();
            $table->text('mobile_app_des')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('homepage_translations');
    }
};
