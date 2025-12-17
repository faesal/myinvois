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
        Schema::table('homepage_translations', function (Blueprint $table) {
            $table->string('working_step_des1')->nullable();
            $table->string('working_step_des2')->nullable();
            $table->string('working_step_des3')->nullable();
            $table->string('working_step_des4')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('homepage_translations', function (Blueprint $table) {
            $table->dropColumn('working_step_des1');
            $table->dropColumn('working_step_des2');
            $table->dropColumn('working_step_des3');
            $table->dropColumn('working_step_des4');
        });
    }
};
