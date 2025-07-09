<?php

namespace App\Services\Admin;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Trips;
use App\Services\BaseService;

class AdminTripService extends BaseService
{
    public function model()
    {
        return Trips::class;
    }

    public function get($tripId)
    {
        try {
            return Trips::query()
                ->where('id', $tripId)
                ->firstOrFail();
        } catch (\Exception $e) {
            Log::error('Error in get trip', ['error' => $e->getMessage()]);
            return false;
        }
    }

    public function updateStatus($tripId, $status)
    {
        try {
            $trip = Trips::query()->findOrFail($tripId);
            $trip->status = $status;
            $trip->save();
            return $trip;
        } catch (\Exception $e) {
            Log::error('Error in update trip status', ['error' => $e->getMessage()]);
            return false;
        }
    }
}