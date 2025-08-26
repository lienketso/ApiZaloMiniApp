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
        Schema::table('clubs', function (Blueprint $table) {
            $table->timestamp('trial_expired_at')->nullable()->comment('Hết hạn dùng thử');
            $table->timestamp('subscription_expired_at')->nullable()->comment('Hết hạn gói trả phí');
            $table->enum('subscription_status', ['trial', 'active', 'expired', 'canceled'])->default('trial')->comment('Trạng thái gói');
            $table->unsignedBigInteger('plan_id')->nullable()->comment('Gói hiện tại');
            $table->timestamp('last_payment_at')->nullable()->comment('Lần thanh toán cuối');
            
            // Thêm foreign key constraint
            $table->foreign('plan_id')->references('id')->on('plans')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clubs', function (Blueprint $table) {
            $table->dropForeign(['plan_id']);
            $table->dropColumn([
                'trial_expired_at',
                'subscription_expired_at', 
                'subscription_status',
                'plan_id',
                'last_payment_at'
            ]);
        });
    }
};
