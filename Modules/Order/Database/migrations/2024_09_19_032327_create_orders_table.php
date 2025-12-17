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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('restaurant_id');
            $table->unsignedBigInteger('address_id')->nullable();
            $table->unsignedBigInteger('time_slot_id');
            $table->string('order_type');
            $table->string('delivery_day');
            $table->string('coupon')->nullable();
            $table->double('discount_amount')->nullable();
            $table->double('delivery_charge')->nullable();
            $table->double('vat')->nullable();
            $table->double('total')->nullable();
            $table->double('grand_total')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('payment_status')->nullable();
            $table->integer('order_status')->nullable();
            $table->tinyInteger('is_guest')->default(0);
            $table->string('tnx_info')->nullable();
            $table->text('delivery_address')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
