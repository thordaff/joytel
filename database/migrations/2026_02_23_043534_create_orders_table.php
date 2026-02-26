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
            $table->string('order_tid')->unique()->comment('Order Transaction ID - Unique identifier');
            $table->string('order_code')->nullable()->comment('Order code from JoyTel API response');
            $table->string('product_code')->comment('Product code for the ordered item');
            $table->integer('quantity')->default(1)->comment('Quantity of items ordered');
            $table->string('status')->default('pending')->comment('Order status: pending, processing, completed, failed');
            $table->text('sn_pin')->nullable()->comment('SN/PIN information from API response');
            $table->text('qrcode')->nullable()->comment('QR code data for eSIM');
            $table->string('cid')->nullable()->comment('Customer ID or reference');
            $table->json('request_data')->nullable()->comment('Original request data');
            $table->json('response_data')->nullable()->comment('API response data');
            $table->string('system_type')->default('warehouse')->comment('warehouse or rsp');
            $table->timestamp('submitted_at')->nullable()->comment('When order was submitted to API');
            $table->timestamp('completed_at')->nullable()->comment('When order was completed');
            $table->timestamps();
            
            // Indexes
            $table->index(['status']);
            $table->index(['system_type']);
            $table->index(['created_at']);
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
