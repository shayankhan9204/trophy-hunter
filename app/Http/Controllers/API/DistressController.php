<?php

namespace App\Http\Controllers\API;

use App\Helpers\APIResponse;
use App\Http\Controllers\Controller;
use App\Services\DistressService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class DistressController extends Controller
{
    public function __construct(private readonly DistressService $distressService) {}

    /**
     * Handle incoming Distress (SOS) request.
     *
     * Accepts:
     *  - event_id   (required|integer|exists:events,id)
     *  - team_id    (required|integer|exists:teams,id)
     *  - angler_id  (required|integer|exists:users,id)
     *  - latitude   (optional|numeric|between:-90,90)
     *  - longitude  (optional|numeric|between:-180,180)
     */
    public function distress(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'event_id'  => ['required', 'integer', 'exists:events,id'],
            'team_id'   => ['required', 'integer'],
            'angler_id' => ['required', 'integer'],
            'latitude'  => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
        ]);

        if ($validator->fails()) {
            return APIResponse::error('Validation failed', $validator->errors()->toArray(), 422);
        }

        $validated = $validator->validated();

        $lat = isset($validated['latitude']) && $validated['latitude'] !== null ? (float) $validated['latitude'] : null;
        $lng = isset($validated['longitude']) && $validated['longitude'] !== null ? (float) $validated['longitude'] : null;

        // If one coordinate is provided without the other, that's not a complete coordinate set
        if (($lat !== null && $lng === null) || ($lat === null && $lng !== null)) {
            $lat = null;
            $lng = null;
        }

        try {
            $result = $this->distressService->processDistress(
                (int) $validated['event_id'],
                (int) $validated['team_id'],
                (int) $validated['angler_id'],
                $lat,
                $lng,
                Carbon::now()
            );

            return APIResponse::success('Distress alert processed successfully', $result);

        } catch (\InvalidArgumentException $e) {
            return APIResponse::error($e->getMessage(), [], 422);
        } catch (\RuntimeException $e) {
            return APIResponse::error($e->getMessage(), [], 422);
        } catch (\Throwable $e) {
            Log::error('Distress SOS: Unexpected error during distress processing.', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return APIResponse::error('An unexpected error occurred while processing the distress alert.', [
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
