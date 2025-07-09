<?php


namespace App\Constants;


use MarcinOrlowski\ResponseBuilder\ApiCodesHelpers;

class AppConst
{
    use ApiCodesHelpers;

    public const APP_NAME = 'MVP Dating App';
    public const APP_VERSION = '1.0.0';
    public const APP_ENV = 'production';

    public const ADMIN_GUARD = 'admin';
    public const USER_GUARD = 'user';

    public const DEFAULT_LANGUAGE = 'en';

    public const DEFAULT_PAGINATION = 20;

    public const DEFAULT_LIMIT = 10;

}