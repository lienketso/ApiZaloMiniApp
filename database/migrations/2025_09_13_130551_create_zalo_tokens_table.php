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
        Schema::create('zalo_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('access_token', 500)->nullable();
            $table->string('refresh_token', 255)->nullable();
            $table->integer('expires_in')->nullable(); // số giây còn hạn
            $table->timestamp('last_refreshed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('zalo_tokens');
    }
};
