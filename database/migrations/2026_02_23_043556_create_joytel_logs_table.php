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
        Schema::create('joytel_logs', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_id')->nullable()->comment('Unique transaction ID for tracking');
            $table->string('system_type')->comment('warehouse or rsp');
            $table->string('endpoint')->comment('API endpoint called');
            $table->string('method')->default('POST')->comment('HTTP method');
            $table->json('request_headers')->nullable()->comment('Request headers sent');
            $table->json('request_body')->nullable()->comment('Request body sent');
            $table->json('response_headers')->nullable()->comment('Response headers received');
            $table->json('response_body')->nullable()->comment('Response body received');
            $table->integer('response_status')->nullable()->comment('HTTP response status code');
            $table->string('response_code')->nullable()->comment('JoyTel API response code');
            $table->decimal('response_time', 8, 2)->nullable()->comment('Response time in seconds');
            $table->string('signature')->nullable()->comment('Request signature used');
            $table->string('order_tid')->nullable()->comment('Related order transaction ID');
            $table->text('error_message')->nullable()->comment('Error message if any');
            $table->timestamps();
            
            // Indexes
            $table->index(['system_type']);
            $table->index(['endpoint']);
            $table->index(['order_tid']);
            $table->index(['response_code']);
            $table->index(['created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('joytel_logs');
    }
};
