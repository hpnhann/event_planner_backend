<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'user_id',
        'role_id',
        'status',
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
     * Get the role
     */
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Scope: Get registered assignments
     */
    public function scopeRegistered($query)
    {
        return $query->where('status', 'registered');
    }

    /**
     * Scope: Get confirmed assignments
     */
    public function scopeConfirmed($query)
    {
        return $query->where('status', 'confirmed');
    }

    /**
     * Scope: Get cancelled assignments
     */
    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    /**
     * Check if user has attended
     */
    public function hasAttended(): bool
    {
        return $this->status === 'attended';
    }
}