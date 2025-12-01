<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'user_id',
        'check_in_time',
        'check_out_time',
        'status',
        'notes',
    ];

    protected $casts = [
        'check_in_time' => 'datetime',
        'check_out_time' => 'datetime',
    ];

    /**
     * Get the event
     */
    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Get the user
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope: Present attendances
     */
    public function scopePresent($query)
    {
        return $query->where('status', 'present');
    }

    /**
     * Scope: Absent attendances
     */
    public function scopeAbsent($query)
    {
        return $query->where('status', 'absent');
    }

    /**
     * Scope: Late attendances
     */
    public function scopeLate($query)
    {
        return $query->where('status', 'late');
    }

    /**
     * Calculate duration in minutes
     */
    public function getDurationAttribute(): ?int
    {
        if (!$this->check_in_time || !$this->check_out_time) {
            return null;
        }
        
        return $this->check_in_time->diffInMinutes($this->check_out_time);
    }
}