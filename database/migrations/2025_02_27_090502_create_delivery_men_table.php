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
        Schema::create('delivery_men', function (Blueprint $table) {
            $table->id();
            $table->string('man_image')->nullable();
            $table->string('fname')->nullable();
            $table->string('lname');
            $table->string('email')->unique();
            $table->string('man_type')->nullable();
            $table->string('idn_type')->nullable();
            $table->string('idn_num')->nullable();
            $table->string('idn_image')->nullable();
            $table->string('phone');
            $table->string('password');
            $table->tinyInteger('status')->deafult(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_men');
    }
};
