<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Requests\Admin\LoginAdminRequest;
use App\Services\Admin\AdminAuthService;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Controllers\Controller;

class AdminAuthController extends Controller
{
    protected $adminAuthService;

    public function __construct(AdminAuthService $adminAuthService)
    {
        $this->adminAuthService = $adminAuthService;
    }


    public function login(LoginAdminRequest $request)
    {
        try {
            $credentials = $request->only('email', 'password');

            if ($result = $this->adminAuthService->login($credentials['email'], $credentials['password'])) {
                return $this->respondSuccessWithCode($result, Response::HTTP_OK);
            }

            return $this->respondError(["error" => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        } catch (\Exception $e) {
            return $this->respondError([
                "error" => $e->getMessage(),
                "trace" => $e->getTraceAsString()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function profile()
    {
        return $this->respondSuccessWithCode(auth('admin-api')->user(), Response::HTTP_OK);
    }
}
