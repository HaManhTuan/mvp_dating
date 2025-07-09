<?php

namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Services\Admin\AdminCreditTransactionsService;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\Request;

class AdminWithdrawRequestController extends Controller
{
  protected $adminCreditTransactionsService;

  public function __construct(AdminCreditTransactionsService $adminCreditTransactionsService)
  {
    parent::__construct($adminCreditTransactionsService);
  }

  public function withdrawRequests(Request $request)
  {
    $params = $request->only(['page', 'limit']);
    $columns = [
      'withdraw_requests.*'
    ];
    return $this->respond($this->service->paginate($params, ['user'], $columns));
  }

  public function approveWithdrawRequest($id)
  {
    // $result = $this->service->approveWithdrawRequest($id);
    // if (!$result) {
    //   return $this->respondError(['error' => 'Withdraw request not found or already processed'], Response::HTTP_NOT_FOUND);
    // }
    return $this->respondSuccessWithCode(['message' => 'Withdraw request approved successfully'], Response::HTTP_OK);
  }

  public function rejectWithdrawRequest($id)
  {
    // $result = $this->service->rejectWithdrawRequest($id);
    // if (!$result) {
    //   return $this->respondError(['error' => 'Withdraw request not found or already processed'], Response::HTTP_NOT_FOUND);
    // }
    return $this->respondSuccessWithCode(['message' => 'Withdraw request rejected successfully'], Response::HTTP_OK);
  }
}