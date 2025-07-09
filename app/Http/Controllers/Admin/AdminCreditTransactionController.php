<?php

namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Services\Admin\AdminCreditTransactionsService;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\Request;

class AdminCreditTransactionController extends Controller
{
  protected $adminCreditTransactionsService;

  public function __construct(AdminCreditTransactionsService $adminCreditTransactionsService)
  {
    parent::__construct($adminCreditTransactionsService);
  }

  public function index(Request $request)
  {
    $params = $request->only(['page', 'limit']);
    $columns = [
      'credit_transactions.*'
    ];
    return $this->respond($this->service->paginate($params, ['fromUser', 'toUser'], $columns));
  }

  public function show($id)
  {
    $transaction = $this->service->show($id, ['fromUser', 'toUser']);
    if (!$transaction) {
      return $this->respondError(['error' => 'Transaction not found'], Response::HTTP_NOT_FOUND);
    }
    return $this->respondSuccessWithCode($transaction, Response::HTTP_OK);
  }

  public function balances()
  {
    $balances = $this->service->getBalances();
    if (!$balances) {
      return $this->respondError(['error' => 'No balances found'], Response::HTTP_NOT_FOUND);
    }
    return $this->respondSuccessWithCode($balances, Response::HTTP_OK);
  }

  public function updateBalance(Request $request, $id)
  {
    $request->validate([
        'balance' => 'required|integer|min:0',
    ]);

    $result = $this->service->updateBalance($id, $request->input('balance'));
    if (!$result) {
      return $this->respondError(['error' => 'Failed to update balance'], Response::HTTP_BAD_REQUEST);
    }
    return $this->respondSuccessWithCode(['message' => 'Balance updated successfully'], Response::HTTP_OK);
  }
}