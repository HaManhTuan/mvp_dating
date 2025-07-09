<?php

namespace App\Services\Admin;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Services\BaseService;

class AdminUserManagerService extends BaseService
{
    public function model()
    {
        return User::class;
    }

    public function get($userId)
    {
      try {
        return User::query()
            ->where('id', $userId)
            ->firstOrFail();
      } catch (\Exception $e) {
          Log::error('Error in get user', ['error' => $e->getMessage()]);
          return false;
      }
    }

    public function updateStatus($userId, $status)
    {
      try {
          $user = User::query()->findOrFail($userId);
          $user->status = $status;
          $user->save();
          return $user;
      } catch (\Exception $e) {
          Log::error('Error in update user status', ['error' => $e->getMessage()]);
          return false;
      }
    }
    
}
