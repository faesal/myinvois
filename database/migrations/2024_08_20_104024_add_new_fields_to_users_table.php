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
        Schema::table('users', function (Blueprint $table) {
            $table->string('image')->nullable();
            $table->string('phone')->unique()->nullable();
            $table->text('address')->nullable();
            $table->string('verification_token')->nullable();
            $table->string('forget_password_token')->nullable();
            $table->string('is_banned')->default('disable');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('image');
            $table->dropColumn('phone');
            $table->dropColumn('address');
            $table->dropColumn('verification_token');
            $table->dropColumn('forget_password_token');
            $table->dropColumn('is_banned');
            $table->dropColumn('status');
        });
    }
};
