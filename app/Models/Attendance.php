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

    // ========== RELATIONSHIPS ==========
    
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
        return $this->belongsTo(User::class, 'user_id', 's_no');
    }

    // ========== SCOPES ==========
    
    /**
     * Present attendances
     */
    public function scopePresent($query)
    {
        return $query->where('status', 'present');
    }

    /**
     * Absent attendances
     */
    public function scopeAbsent($query)
    {
        return $query->where('status', 'absent');
    }

    /**
     * Late attendances
     */
    public function scopeLate($query)
    {
        return $query->where('status', 'late');
    }

    // ========== HELPER METHODS ==========
    
    /**
     * Check in user
     */
    public function checkIn(): bool
    {
        $this->check_in_time = now();
        $this->status = 'present';
        return $this->save();
    }

    /**
     * Check out user
     */
    public function checkOut(): bool
    {
        $this->check_out_time = now();
        return $this->save();
    }

    /**
     * Mark as late
     */
    public function markAsLate(): bool
    {
        $this->status = 'late';
        return $this->save();
    }

    /**
     * Mark as absent
     */
    public function markAsAbsent(): bool
    {
        $this->status = 'absent';
        $this->check_in_time = null;
        $this->check_out_time = null;
        return $this->save();
    }

    // ========== ATTRIBUTES ==========
    
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

    /**
     * Get duration in human readable format
     */
    public function getDurationHumanAttribute(): ?string
    {
        $duration = $this->duration;
        
        if (!$duration) {
            return null;
        }
        
        $hours = floor($duration / 60);
        $minutes = $duration % 60;
        
        if ($hours > 0) {
            return "{$hours}h {$minutes}m";
        }
        
        return "{$minutes}m";
    }

    /**
     * Check if user is currently checked in
     */
    public function getIsCheckedInAttribute(): bool
    {
        return $this->check_in_time && !$this->check_out_time;
    }

    /**
     * Check if attendance is completed
     */
    public function getIsCompletedAttribute(): bool
    {
        return $this->check_in_time && $this->check_out_time;
    }
}