<?php

namespace App\Services\Admin;

use App\Constants\AppConst;
use App\Models\Admin\Admin;
use DateTime;
use Exception;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Carbon;
class AdminAuthService
{
    protected $oClient;
    protected $guards;
    protected $this;

    public function __construct()
    {
        $this->this = $this;
    }

    /**
     * @throws Exception
     */
    public function login($email, $password)
    {
        $admin = Admin::query()->where('email', $email)->first();

        if (empty($admin)) {
            return false;
        }

        // Kiểm tra mật khẩu
        if (!Hash::check($password, $admin->password)) {
            return false;
        }

        // create token by passport
        $createToken = $admin->createToken(AppConst::APP_NAME, ['admin']);
        $token = $createToken->accessToken;

        // convert expires_at to DateTime: expiresIn
        $expiresAt = $createToken->token && $createToken->token->expires_at ? $createToken->token->expires_at->format('Y-m-d H:i:s') : null;

        if (!$token) {
            return false;
        }

        return [
          'token' => $token,
          'expires_at' => $expiresAt
        ];
    }
}