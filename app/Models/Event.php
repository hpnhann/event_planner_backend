<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'start_date',
        'end_date',
        'location',
        'max_participants',
        'status',
        'created_by',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'max_participants' => 'integer',
    ];

    /**
     * Get the user who created this event
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get all event assignments (registrations)
     */
    public function eventAssignments()
    {
        return $this->hasMany(EventAssignment::class);
    }

    /**
     * Get all attendances (check-ins) for this event
     */
    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    /**
     * Scope: Get only published events
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    /**
     * Scope: Get upcoming events
     */
    public function scopeUpcoming($query)
    {
        return $query->where('start_date', '>', now());
    }

    /**
     * Scope: Get past events
     */
    public function scopePast($query)
    {
        return $query->where('end_date', '<', now());
    }

    /**
     * Check if event is full
     */
    public function isFull(): bool
    {
        if (!$this->max_participants) {
            return false;
        }
        
        return $this->eventAssignments()->count() >= $this->max_participants;
    }

    /**
     * Get available slots
     */
    public function getAvailableSlotsAttribute(): ?int
    {
        if (!$this->max_participants) {
            return null;
        }
        
        $registered = $this->eventAssignments()->count();
        return max(0, $this->max_participants - $registered);
    }

    /**
     * Get attendance rate (percentage)
     */
    public function getAttendanceRateAttribute(): float
    {
        $registered = $this->eventAssignments()->count();
        if ($registered === 0) {
            return 0;
        }
        
        $attended = $this->attendances()->where('status', 'present')->count();
        return round(($attended / $registered) * 100, 2);
    }
}