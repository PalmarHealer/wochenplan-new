<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class LessonTemplate extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'notes',
        'disabled',
        'weekday',
        'color',
        'room',
        'lesson_time',
        'created_by',
        'updated_by',
    ];

    public function assignedUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'lesson_template_user');
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
