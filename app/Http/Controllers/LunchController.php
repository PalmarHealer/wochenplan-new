<?php

namespace App\Http\Controllers;

use App\Services\LunchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LunchController
{
    public function __construct(
        protected LunchService $lunchService
    ) {}

    public function clear(Request $request): JsonResponse
    {
        // Check permission
        if (! auth()->user()->can('edit_layout')) {
            return response()->json([
                'success' => false,
                'message' => 'Keine Berechtigung für diese Aktion',
            ], 403);
        }

        $request->validate([
            'date' => 'required|date_format:Y-m-d',
        ]);

        $date = $request->input('date');
        $cleared = $this->lunchService->clearLunch($date);

        return response()->json([
            'success' => true,
            'message' => $cleared
                ? "Mittagessen für $date wurde gelöscht und wird beim nächsten Abruf neu geladen"
                : "Kein Mittagessen für $date gefunden",
        ]);
    }
}
