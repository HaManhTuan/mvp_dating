<?php

namespace App\Services\Admin;

use App\Models\CreditTransaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\BaseService;
use Illuminate\Database\Eloquent\Builder;
use App\Models\UserCredits;

class AdminCreditTransactionsService extends BaseService
{
  public function model()
  {
      return CreditTransaction::class;
  }

  public function prependQuery(Builder $query): Builder
  {
    $filters = request('q') ? json_decode(request('q'), true) : [];

    if (isset($filters['from_user_id'])) {
        $query->where('from_user_id', $filters['from_user_id']);
    }
    if (isset($filters['to_user_id'])) {
        $query->where('to_user_id', $filters['to_user_id']);
    }
    if (isset($filters['type'])) {
        $query->where('type', $filters['type']);
    }
    if (isset($filters['date_from'])) {
        $query->where('created_at', '>=', Carbon::parse($filters['date_from']));
    }
    if (isset($filters['date_to'])) {
        $query->where('created_at', '<=', Carbon::parse($filters['date_to']));
    }
    return $query;
  }

  public function getBalances()
    {
        // Xem số dư của toàn bộ user
        $balances = UserCredits::with('user')->get();

        return response()->json($balances);
    }

    public function updateBalance($userId, $balance)
    {
        $userCredits = UserCredits::where('user_id', $userId)->first();

        if (!$userCredits) {
            return false;
        }

        $userCredits->balance = $balance;
        return $userCredits->save();
    }
}