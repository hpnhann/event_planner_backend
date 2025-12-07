<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventRegistration extends Model
{
    use HasFactory;

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

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 's_no');
    }

    public function event()
    {
        return $this->belongsTo(Event::class, 'event_id');
    }
}