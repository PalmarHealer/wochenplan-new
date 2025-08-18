<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class LastSeenService
{
    /**
     * Ensure there is exactly one row in last_seen and set its date to now.
     */
    public function touch(): void
    {
        $now = Carbon::now();

        $count = DB::table('last_seen')->count();
        if ($count === 0) {
            DB::table('last_seen')->insert([
                'date' => $now,
            ]);
        } else {
            // Update all rows (there should be only one per requirements)
            DB::table('last_seen')->update([
                'date' => $now,
            ]);
        }
    }

    /**
     * Get the current last_seen date as string (Y-m-d H:i:s). If none exists, create it and return now.
     */
    public function current(): string
    {
        $value = DB::table('last_seen')->value('date');
        if (!$value) {
            $now = Carbon::now();
            DB::table('last_seen')->insert([
                'date' => $now,
            ]);
            return $now->toDateTimeString();
        }

        // Normalize to string
        if ($value instanceof Carbon) {
            return $value->toDateTimeString();
        }
        // $value may be a string depending on DB driver
        try {
            return Carbon::parse($value)->toDateTimeString();
        } catch (\Throwable) {
            return Carbon::now()->toDateTimeString();
        }
    }
}
