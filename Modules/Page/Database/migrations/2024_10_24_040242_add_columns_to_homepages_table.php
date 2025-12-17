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
            $table->string('promotional_banner_one')->nullable();
            $table->string('promotional_banner_one_status')->nullable();
            $table->string('promotional_banner_one_url')->nullable();
            $table->string('promotional_banner_two')->nullable();
            $table->string('promotional_banner_two_status')->nullable();
            $table->string('promotional_banner_two_url')->nullable();
            $table->string('promotional_banner_restaurant')->nullable();
            $table->string('promotional_banner_restaurant_status')->nullable();
            $table->string('promotional_banner_restaurant_url')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('homepages', function (Blueprint $table) {
            $table->dropColumn('promotional_banner_one');
            $table->dropColumn('promotional_banner_two');
            $table->dropColumn('promotional_banner_restaurant');
            $table->dropColumn('promotional_banner_restaurant_status');
            $table->dropColumn('promotional_banner_restaurant_url');
            $table->dropColumn('promotional_banner_two_status');
            $table->dropColumn('promotional_banner_two_url');
            $table->dropColumn('promotional_banner_one_status');
            $table->dropColumn('promotional_banner_one_url');
        });
    }
};
