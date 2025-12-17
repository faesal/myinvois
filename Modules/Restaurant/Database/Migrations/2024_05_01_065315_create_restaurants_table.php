<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('restaurants', function (Blueprint $table) {
            $table->id();
            $table->string('logo');
            $table->string('cover_image')->nullable();
            $table->string('restaurant_name');
            $table->string('slug');
            $table->integer('city_id');
            $table->string('cuisines');
            $table->string('whatsapp')->nullable();
            $table->string('address')->nullable();
            $table->double('latitude')->nullable();
            $table->double('longitude')->nullable();
            $table->decimal('max_delivery_distance')->default(0);
            $table->string('owner_name')->nullable();
            $table->string('owner_email')->nullable();
            $table->string('owner_phone')->nullable();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->string('opening_hour')->nullable();
            $table->string('closing_hour')->nullable();
            $table->string('min_processing_time')->nullable();
            $table->string('max_processing_time')->nullable();
            $table->string('time_slot_separate')->nullable();
            $table->text('tags')->nullable();
            $table->string('is_featured')->default('disable');
            $table->string('is_pickup_order')->default('disable');
            $table->string('is_delivery_order')->default('disable');
            $table->string('admin_approval')->default('disable');
            $table->string('is_banned')->default('disable');
            $table->string('forget_password_token')->nullable();
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
        Schema::dropIfExists('restaurants');
    }
};
