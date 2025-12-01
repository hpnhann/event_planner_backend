<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'permissions',
    ];

    protected $casts = [
        'permissions' => 'array',
    ];

    /**
     * Get all event assignments with this role
     */
    public function eventAssignments()
    {
        return $this->hasMany(EventAssignment::class);
    }

    /**
     * Check if role is in use
     */
    public function isInUse(): bool
    {
        return $this->eventAssignments()->count() > 0;
    }
}