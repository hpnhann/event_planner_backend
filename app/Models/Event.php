<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;

    // Các trường cho phép thêm/sửa (khớp với migration mới)
    protected $fillable = [
        'title',
        'description',
        'location',
        'start_date',
        'start_time',
        'max_attendees',
        'image',
        'status',
        'user_id'
    ];

    // Quan hệ: Sự kiện thuộc về 1 User
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Quan hệ: Sự kiện có nhiều người tham gia
    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }
}