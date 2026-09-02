<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTripRatingRequest;
use App\Models\Trip;
use App\Models\User;
use App\Services\TripRatingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Exception;

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
                $request->input('comment'),
                $request->input('receiver_id')
            );
            return response()->json($rating, 201);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Carrera no encontrada'], 404);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $perPage = min(max($request->integer('per_page', 12), 1), 30);
            $ratings = $this->ratingService->getRatingsReceived($user, $perPage);
            return response()->json($ratings);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}


