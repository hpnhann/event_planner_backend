<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $primaryKey = 's_no';
    
    protected $fillable = [
        'id',
        'name',
        'full_name',
        'email',
        'password_hash',
        'role',
        'theme',
        'remember_token',
    ];

    protected $hidden = [
        'password_hash',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function getAuthPassword()
    {
        return $this->password_hash;
    }

    // ========== RELATIONSHIPS ==========
    
    public function events()
    {
        return $this->hasMany(Event::class, 'created_by', 'id');
    }

    public function createdEvents()
    {
        return $this->hasMany(Event::class, 'created_by', 'id');
    }

    public function eventAssignments()
    {
        return $this->hasMany(EventAssignment::class, 'user_id', 's_no');
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class, 'user_id', 's_no');
    }

    public function streak()
    {
        return $this->hasOne(Streak::class, 'user_id', 's_no');
    }

    public function registeredEvents()
    {
        return $this->belongsToMany(Event::class, 'event_registrations', 'user_id', 'event_id')
            ->withPivot('role_id', 'status', 'notes')
            ->withTimestamps();
    }

    // ========== HELPER METHODS ==========
    
    public function hasRole($roleName): bool
    {
        return $this->role === $roleName;
    }

    public function hasAnyRole(array $roles): bool
    {
        return in_array($this->role, $roles);
    }

    public function isRegisteredFor(int $eventId): bool
    {
        return $this->eventAssignments()
            ->where('event_id', $eventId)
            ->exists();
    }

    public function getCurrentStreak(): int
    {
        return $this->streak ? $this->streak->current_streak : 0;
    }

    public function getLongestStreak(): int
    {
        return $this->streak ? $this->streak->longest_streak : 0;
    }
}