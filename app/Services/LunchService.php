<?php

namespace App\Services;

use App\Models\Lunch;
use Carbon\Carbon;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class LunchService
{
    public function getLunch(string $date): string
    {
        // Callers pass "Y-m-d" or a Carbon with time; store and ask the API for the plain date.
        $date = Carbon::parse($date)->toDateString();

        $menu = Lunch::whereDate('date', $date)->first();
        if ($menu) {
            return $menu->lunch;
        }

        try {
            $data = $this->fetchFromApi($date);
        } catch (Exception $e) {
            // Never print raw exception text into the plan or the PDF.
            Log::warning('Lunch API request failed', ['date' => $date, 'error' => $e->getMessage()]);

            return 'Mittagessen nicht verfügbar';
        }

        if ($data['available']) {
            Lunch::create([
                'date' => $date,
                'lunch' => $data['lunch'],
            ]);

            return $data['lunch'];
        }

        return 'Mittagessen noch nicht eingetragen';
    }

    public function clearLunch(string $date): bool
    {
        return Lunch::whereDate('date', $date)->delete() > 0;
    }

    protected function fetchFromApi(string $date): array
    {
        $client = new Client;

        $response = $client->post(env('LUNCH_API_URL'), [
            'form_params' => [
                'secret' => env('LUNCH_API_KEY'),
                'date' => $date,
            ],
            'timeout' => 2,
        ]);

        $json = json_decode($response->getBody(), true);

        if (isset($json['error'])) {
            throw new Exception('Lunch API Fehler: '.$json['error']);
        }

        return [
            'available' => $json['available'],
            'lunch' => $json['lunch'],
        ];
    }
}
