<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventAssignment extends Model
{
    use HasFactory;

    // Map to table 'event_registrations'
    protected $table = 'event_registrations';

    protected $fillable = [
        'user_id',
        'event_id',
        'role_id',
        'notes',
        'registration_date',
        'status',
    ];

    protected $casts = [
        'registration_date' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ========== RELATIONSHIPS ==========
    
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 's_no');
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    // ========== SCOPES ==========
    
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    // ========== HELPER METHODS ==========
    
    public function approve(): bool
    {
        $this->status = 'approved';
        return $this->save();
    }

    public function reject(): bool
    {
        $this->status = 'rejected';
        return $this->save();
    }

    public function cancel(): bool
    {
        $this->status = 'cancelled';
        return $this->save();
    }
}