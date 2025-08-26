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
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Tên gói (ví dụ: Basic, Premium, Enterprise)
            $table->text('description')->nullable(); // Mô tả gói
            $table->decimal('price', 10, 2); // Giá gói
            $table->string('billing_cycle'); // Chu kỳ thanh toán (monthly, yearly)
            $table->integer('duration_days'); // Số ngày có hiệu lực
            $table->json('features')->nullable(); // Các tính năng của gói
            $table->boolean('is_active')->default(true); // Gói có đang hoạt động không
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
