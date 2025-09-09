<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Lesson extends Model
{
    protected $fillable = [
        'name',
        'description',
        'notes',
        'disabled',
        'date',
        'color',
        'room',
        'lesson_time',
        'parent_id',
        'origin_day',
        'created_by',
        'updated_by',
    ];

    public function assignedUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'lesson_user');
    }

    public function colors(): BelongsTo
    {
        return $this->belongsTo(Color::class, 'color');
    }

    public function rooms(): BelongsTo
    {
        return $this->belongsTo(Room::class, 'room');
    }

    public function times(): BelongsTo
    {
        return $this->belongsTo(Time::class, 'lesson_time');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
