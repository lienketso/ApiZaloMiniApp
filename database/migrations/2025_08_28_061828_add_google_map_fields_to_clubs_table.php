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
            $table->decimal('latitude', 10, 8)->nullable()->comment('Vĩ độ của câu lạc bộ');
            $table->decimal('longitude', 11, 8)->nullable()->comment('Kinh độ của câu lạc bộ');
            $table->string('place_id')->nullable()->comment('Google Place ID của địa điểm');
            $table->text('formatted_address')->nullable()->comment('Địa chỉ được định dạng bởi Google');
            $table->string('map_url')->nullable()->comment('URL Google Maps của địa điểm');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clubs', function (Blueprint $table) {
            $table->dropColumn([
                'latitude',
                'longitude',
                'place_id',
                'formatted_address',
                'map_url'
            ]);
        });
    }
};
