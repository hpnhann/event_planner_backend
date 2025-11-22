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
        Schema::create('events', function (Blueprint $table) {
            $table->id(); 
            
            $table->string('title');        // Tên sự kiện
            $table->text('description');    // Mô tả
            $table->string('location');     // Địa điểm
            
            $table->date('start_date');     // Ngày bắt đầu
            $table->time('start_time');     // Giờ bắt đầu
            
            $table->integer('max_attendees')->default(0); // Số người tham gia tối đa
            
            $table->string('image')->nullable(); // Ảnh bìa
            $table->enum('status', ['draft', 'published'])->default('draft'); // Trạng thái
            
            // Liên kết với người tạo (User)
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });
    }
};
