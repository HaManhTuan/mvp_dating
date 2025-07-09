<?php

namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Services\Admin\AdminTripService;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\Request;

class AdminTripController extends Controller
{
  protected $adminTripService;

  public function __construct(AdminTripService $adminTripService)
  {
    parent::__construct($adminTripService);
  }

  public function index(Request $request)
  {
    $params = $request->only(['page', 'limit']);
    $columns = array(
            'id',
            'user_id',
            'title',
            'description',
            'created_at',
            'updated_at',
        );
    return $this->respond($this->service->paginate($params, ['user', 'images'], $columns));
  }

  public function show($tripId)
  {
    $result = $this->service->get($tripId);
    if (!$result) {
        return $this->respondWithEmptyData(Response::HTTP_NOT_FOUND);
    }
    return $this->respond($result);
  }

  public function updateStatus(Request $request, $tripId) {
    $status = $request->input('status');

    if (!in_array($status, ['pending', 'approved', 'hidden'])) {
        return $this->respondError(['error' => 'Invalid status'], Response::HTTP_BAD_REQUEST);
    }
    $result = $this->service->updateStatus($tripId, $status);
    if (!$result) {
        return $this->respondWithEmptyData(Response::HTTP_NOT_FOUND);
    }
    return $this->respond($result);
  }
}
