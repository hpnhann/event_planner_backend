<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_title',
        'event_description',
        'event_location',
        'event_date',
        'event_time',                 
        'created_by',      
        'event_image',
        'status',
        'max_volunteers',
        'published_at',
    ];

    protected $casts = [
        'event_date' => 'date',
        'published_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ========== RELATIONSHIPS ==========
    
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function eventAssignments()
    {
        return $this->hasMany(EventAssignment::class, 'event_id');
    }

    public function registrations()
    {
        return $this->hasMany(EventRegistration::class, 'event_id');
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class, 'event_id');
    }

    // ========== SCOPES ==========
    
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeUpcoming($query)
    {
        return $query->where('event_date', '>=', today());
    }

    public function scopePast($query)
    {
        return $query->where('event_date', '<', today());
    }

    // ========== HELPER METHODS ==========
    
    public function isFull(): bool
    {
        if (!$this->max_volunteers) {
            return false; 
        }
        
        $registered = $this->registrations()
                          ->whereIn('status', ['pending', 'approved'])
                          ->count();
        
        return $registered >= $this->max_volunteers;
    }

    public function getRegisteredCountAttribute(): int
    {
        return $this->registrations()
                    ->whereIn('status', ['pending', 'approved'])
                    ->count();
    }
}