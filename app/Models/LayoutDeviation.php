<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LayoutDeviation extends Model
{
    protected $fillable = [
        'start',
        'end',
        'layout_id',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'start' => 'date:d.m.Y',
            'end' => 'date:d.m.Y',
        ];
    }

    public function layout(): BelongsTo
    {
        return $this->belongsTo(Layout::class);
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
