<?php

namespace App\Services;

use App\Models\Lunch;
use Exception;
use GuzzleHttp\Client;

class LunchService
{
    public function getLunch(string $date): string
    {
        $menu = Lunch::where('date', $date)->first();
        if ($menu) {
            return $menu->lunch;
        }

        try {
            $data = $this->fetchFromApi($date);
        } catch (Exception $e) {
            return $e->getMessage();
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
        return Lunch::where('date', $date)->delete() > 0;
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
