<?php

namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Services\Admin\AdminUserManagerService;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\Request;

class AdminUserController extends Controller
{
  protected $adminUserManagerService;

  public function __construct(AdminUserManagerService $adminUserManagerService)
  {
    parent::__construct($adminUserManagerService);
  }

  public function index(Request $request)
  {
    $params = $request->only(['page', 'limit']);
    $columns = array(
            'id',
            'email',
            'name',
            'gender',
            'age',
            'location',
            'bio',
            'profile_photo',
            'credits',
            'created_at',
            'updated_at',
        );
    return $this->respond($this->service->paginate($params, [], $columns));
  }

  public function show($userId)
  {
    $result = $this->service->get($userId);
    if (!$result) {
        return $this->respondWithEmptyData(Response::HTTP_NOT_FOUND);
    }
    return $this->respond($result);
  }

  public function updateStatus(Request $request, $userId) {
    $status = $request->input('status');

    if (!in_array($status, ['active', 'banned'])) {
        return $this->respondError(['error' => 'Invalid status'], Response::HTTP_BAD_REQUEST);
    }
    $result = $this->service->updateStatus($userId, $status);
    if (!$result) {
        return $this->respondWithEmptyData(Response::HTTP_NOT_FOUND);
    }
    return $this->respond($result);
  }
}
