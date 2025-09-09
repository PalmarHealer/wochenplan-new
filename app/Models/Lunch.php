<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lunch extends Model
{
    protected $fillable = [
        'date',
        'lunch',
    ];
    protected function casts(): array
    {
        return [
            'date' => 'date:d.m.Y',
        ];
    }
}
