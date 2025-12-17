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
        Schema::table('homepages', function (Blueprint $table) {
            $table->string('footer_img_one')->nullable();
            $table->string('footer_img_two')->nullable();
            $table->string('footer_img_three')->nullable();
            $table->string('footer_img_four')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('homepages', function (Blueprint $table) {
            $table->dropColumn('footer_img_one');
            $table->dropColumn('footer_img_two');
            $table->dropColumn('footer_img_three');
            $table->dropColumn('footer_img_four');
        });
    }
};
