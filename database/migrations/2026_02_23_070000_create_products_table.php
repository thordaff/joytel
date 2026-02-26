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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('product_code')->unique();
            $table->string('name');
            $table->string('category'); // 'esim', 'recharge', etc.
            $table->string('region')->nullable(); // 'global', 'asia', 'europe'
            $table->integer('data_amount_mb'); // dalam MB untuk standarisasi
            $table->decimal('price', 10, 2)->nullable();
            $table->text('description')->nullable();
            $table->string('system_type'); // 'warehouse', 'rsp'
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable(); // untuk data tambahan
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};