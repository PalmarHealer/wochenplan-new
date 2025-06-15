<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Layout extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'description',
        'notes',
        'layout',
        'active'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [];
    }


    /**
     * Ensures that only one layout can be active.
     */
    protected static function booted(): void
    {
        static::saving(function (Layout $layout) {
            if ($layout->active) {

                Layout::where('id', '!=', $layout->id)
                    ->where('active', true)
                    ->update(['active' => false]);
            }
        });
    }

}
