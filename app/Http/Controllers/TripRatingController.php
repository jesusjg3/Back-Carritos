<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTripRatingRequest;
use App\Models\Trip;
use App\Models\User;
use App\Services\TripRatingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class TripRatingController extends Controller
{
    protected TripRatingService $ratingService;

    public function __construct(TripRatingService $ratingService)
    {
        $this->ratingService = $ratingService;
    }

    public function store(StoreTripRatingRequest $request, int $tripId): JsonResponse
    {
        try {
            $rating = $this->ratingService->rate(
                $tripId,
                Auth::user(),
                $request->input('score'),
                $request->input('comment')
            );
            return response()->json($rating, 201);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => 'Carrera no encontrada'], 404);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}



