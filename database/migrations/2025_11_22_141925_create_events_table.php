<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
    Schema::create('events', function (Blueprint $table) {
        $table->id();
        
        $table->string('title');        // Tên sự kiện
        $table->text('description')->nullable();    // Mô tả
        $table->string('location')->nullable();     // Địa điểm
        
        // Dùng dateTime để tính toán giờ giấc cho chuẩn (khớp với Carbon trong Controller)
        $table->dateTime('start_date'); 
        $table->dateTime('end_date');   
        
        // Khớp với code Controller: max_participants
        $table->integer('max_participants')->nullable(); 
        
        $table->string('image')->nullable(); // Ảnh bìa (cho Cloudinary sau này)
        $table->enum('status', ['draft', 'published', 'completed', 'cancelled'])->default('draft');
        
        // Khớp với code Controller: created_by
        $table->foreignId('created_by')->constrained('users')->onDelete('cascade');

        $table->timestamps();
    });
    }
    public function down(): void
    {
    DB::statement('SET FOREIGN_KEY_CHECKS=0;');
    Schema::dropIfExists('events');
    DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
};
