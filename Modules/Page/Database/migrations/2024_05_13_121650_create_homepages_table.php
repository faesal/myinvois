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
        Schema::create('homepages', function (Blueprint $table) {
            $table->id();
            $table->string('intro_banner_one')->nullable();
            $table->string('intro_banner_two')->nullable();
            $table->string('join_restaurant_image')->nullable();
            $table->string('mobile_app_image')->nullable();
            $table->string('working_step_icon1')->nullable();
            $table->string('working_step_icon2')->nullable();
            $table->string('working_step_icon3')->nullable();
            $table->string('working_step_icon4')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('homepages');
    }
};
