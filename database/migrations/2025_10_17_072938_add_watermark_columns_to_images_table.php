<?php
// database/migrations/xxxx_xx_xx_xxxxxx_add_watermark_columns_to_images_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('images', function (Blueprint $table) {
            $table->string('original_file_path')->nullable()->after('file_path');
            $table->string('watermarked_file_path')->nullable()->after('original_file_path');
        });
    }

    public function down()
    {
        Schema::table('images', function (Blueprint $table) {
            $table->dropColumn(['original_file_path', 'watermarked_file_path']);
        });
    }
};
