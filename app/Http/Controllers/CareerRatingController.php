<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCareerRatingRequest;
use App\Models\Career;
use App\Models\User;
use App\Services\CareerRatingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class CareerRatingController extends Controller
{
    protected CareerRatingService $ratingService;

    public function __construct(CareerRatingService $ratingService)
    {
        $this->ratingService = $ratingService;
    }

    public function store(StoreCareerRatingRequest $request, int $careerId): JsonResponse
    {
        $career = Career::findOrFail($careerId);
        $fromUser = Auth::user();

        // Determine "to" user. 
        // If "from" is passenger, "to" is driver.
        // If "from" is driver, "to" is passenger.
        // Simplified logic:
        if ($fromUser->id === $career->passenger_id) {
            $toUser = User::findOrFail($career->driver_id);
        } else {
            $toUser = User::findOrFail($career->passenger_id);
        }

        try {
            $rating = $this->ratingService->rate(
                $career,
                $fromUser,
                $toUser,
                $request->input('score'),
                $request->input('comment')
            );
            return response()->json($rating, 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}
