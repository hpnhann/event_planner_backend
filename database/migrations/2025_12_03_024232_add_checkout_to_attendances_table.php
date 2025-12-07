<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            // Đổi tên checked_in_at thành check_in_time
            $table->renameColumn('checked_in_at', 'check_in_time');
            
            // Thêm check_out_time
            $table->dateTime('check_out_time')->nullable()->after('check_in_time');
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->renameColumn('check_in_time', 'checked_in_at');
            $table->dropColumn('check_out_time');
        });
    }
};