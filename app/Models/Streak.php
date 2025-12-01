<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Streak extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'current_streak',
        'longest_streak',
        'last_attendance_date',
    ];

    protected $casts = [
        'last_attendance_date' => 'date',
        'current_streak' => 'integer',
        'longest_streak' => 'integer',
    ];

    /**
     * Get the user that owns this streak
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if streak is active (attended within last 24 hours)
     */
    public function isActive(): bool
    {
        if (!$this->last_attendance_date) {
            return false;
        }
        
        return $this->last_attendance_date->isToday() || 
               $this->last_attendance_date->isYesterday();
    }
}