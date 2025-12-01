<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Models\Role;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // ============================================
    // RELATIONSHIPS
    // ============================================

    /**
     * Many-to-Many: User has many Roles
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class)->withTimestamps();
    }

    /**
     * One-to-Many: User creates many Events
     */
    public function events()
    {
        return $this->hasMany(Event::class, 'created_by');
    }

    /**
     * Alias for events relationship (better naming)
     */
    public function createdEvents()
    {
        return $this->hasMany(Event::class, 'created_by');
    }

    /**
     * One-to-Many: User has many Attendances
     */
    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    /**
     * One-to-One: User has one Streak
     */
    public function streak()
    {
        return $this->hasOne(Streak::class);
    }

    /**
     * One-to-Many: User has many Event Assignments (registrations)
     */
    public function eventAssignments()
    {
        return $this->hasMany(EventAssignment::class);
    }

    /**
     * Many-to-Many: User registered for many Events (through event_assignments)
     */
    public function registeredEvents()
    {
        return $this->belongsToMany(Event::class, 'event_assignments')
            ->withPivot('role_id', 'status')
            ->withTimestamps();
    }

    // ============================================
    // HELPER METHODS
    // ============================================

    /**
     * Check if user has a specific role
     */
    public function hasRole($roleName): bool
    {
        return $this->roles()->where('name', $roleName)->exists();
    }

    /**
     * Check if user has any of the given roles
     */
    public function hasAnyRole(array $roles): bool
    {
        return $this->roles()->whereIn('name', $roles)->exists();
    }

    /**
     * Check if user is registered for a specific event
     */
    public function isRegisteredFor(int $eventId): bool
    {
        return $this->eventAssignments()
            ->where('event_id', $eventId)
            ->exists();
    }

    /**
     * Get user's current streak count
     */
    public function getCurrentStreak(): int
    {
        return $this->streak ? $this->streak->current_streak : 0;
    }

    /**
     * Get user's longest streak count
     */
    public function getLongestStreak(): int
    {
        return $this->streak ? $this->streak->longest_streak : 0;
    }
}