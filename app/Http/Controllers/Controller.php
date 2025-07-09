<?php

namespace App\Http\Controllers;

use App\Constants\ApiCodes;
use App\Http\ResponseBuilder\ResponseBuilder;
use App\Services\BaseService;
use Error;
use Exception;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller as BaseController;
use MarcinOrlowski\ResponseBuilder\Exceptions\ArrayWithMixedKeysException;
use MarcinOrlowski\ResponseBuilder\Exceptions\ConfigurationNotFoundException;
use MarcinOrlowski\ResponseBuilder\Exceptions\IncompatibleTypeException;
use MarcinOrlowski\ResponseBuilder\Exceptions\InvalidTypeException;
use MarcinOrlowski\ResponseBuilder\Exceptions\MissingConfigurationKeyException;
use MarcinOrlowski\ResponseBuilder\Exceptions\NotIntegerException;
use Symfony\Component\HttpFoundation\Response;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    protected $service;
    protected $optionsColumns = ['key' => 'id', 'label' => 'name', 'other_cols' => []];

    /**
     * @param BaseService $service
     */
    public function __construct(BaseService $service)
    {
        $this->service = $service;
    }

    /**
     * @throws InvalidTypeException
     * @throws NotIntegerException
     * @throws IncompatibleTypeException
     * @throws ConfigurationNotFoundException
     * @throws ArrayWithMixedKeysException
     * @throws MissingConfigurationKeyException
     */
    public function respond($data = null, $msg = null): Response {
        return ResponseBuilder::asSuccess()->withData($data)->withMessage($msg)->build();
    }

    /**
     * @throws InvalidTypeException
     * @throws NotIntegerException
     * @throws ConfigurationNotFoundException
     * @throws IncompatibleTypeException
     * @throws ArrayWithMixedKeysException
     * @throws MissingConfigurationKeyException
     */
    public function respondSuccessWithCode($data = null, $HttpCode)
    {
        return ResponseBuilder::asSuccess()->withData($data)->withHttpCode($HttpCode)->build();
    }

    /**
     * @throws InvalidTypeException
     * @throws NotIntegerException
     * @throws IncompatibleTypeException
     * @throws ConfigurationNotFoundException
     * @throws ArrayWithMixedKeysException
     * @throws MissingConfigurationKeyException
     */
    public function respondWithMessage($msg = null): Response {
        return ResponseBuilder::asSuccess()->withMessage($msg)->build();
    }

    /**
     * @throws InvalidTypeException
     * @throws NotIntegerException
     * @throws ConfigurationNotFoundException
     * @throws IncompatibleTypeException
     * @throws ArrayWithMixedKeysException
     * @throws MissingConfigurationKeyException
     */
    public function respondWithError($apiCode, $HttpCode, $message = null, $error = null): Response {
        return ResponseBuilder::asError($apiCode)->withHttpCode($HttpCode)->withMessage($message)->withData($error)->build();
    }

    /**
     * @throws InvalidTypeException
     * @throws NotIntegerException
     * @throws ArrayWithMixedKeysException
     * @throws MissingConfigurationKeyException
     * @throws ConfigurationNotFoundException
     * @throws IncompatibleTypeException
     */
    public function respondBadRequest($apiCode = ApiCodes::UNCAUGHT_EXCEPTION): Response {
        return $this->respondWithError($apiCode, Response::HTTP_BAD_REQUEST);
    }

    /**
     * @throws InvalidTypeException
     * @throws NotIntegerException
     * @throws ArrayWithMixedKeysException
     * @throws MissingConfigurationKeyException
     * @throws IncompatibleTypeException
     * @throws ConfigurationNotFoundException
     */
    public function respondUnauthorizedRequest($apiCode = ApiCodes::UNAUTHORIZED_EXCEPTION): Response {
        return $this->respondWithError($apiCode, Response::HTTP_UNAUTHORIZED);
    }

    /**
     * @throws InvalidTypeException
     * @throws NotIntegerException
     * @throws ArrayWithMixedKeysException
     * @throws MissingConfigurationKeyException
     * @throws IncompatibleTypeException
     * @throws ConfigurationNotFoundException
     */
    public function respondNotFound($apiCode = ApiCodes::HTTP_NOT_FOUND): Response {
        return $this->respondWithError($apiCode, Response::HTTP_NOT_FOUND);
    }

    public function parseGivenData(array $data = [], int $statusCode = 200, array $headers = []): array
    {
        $responseStructure = $data['message'] ?? 'Error';
        if(!empty($data['result'])){
            $responseStructure['result'] = $data['result'];
        }
        if (isset($data['errors'])) {
            $responseStructure['errors'] = $data['errors'];
        }
        if (isset($data['status'])) {
            $statusCode = $data['status'];
        }


        if (isset($data['exception']) && ($data['exception'] instanceof Error || $data['exception'] instanceof Exception)) {
            if (config('app.env') !== 'production') {
                $responseStructure['exception'] = [
                    'message' => $data['exception']->getMessage(),
                    'file' => $data['exception']->getFile(),
                    'line' => $data['exception']->getLine(),
                    'code' => $data['exception']->getCode(),
                    'trace' => $data['exception']->getTrace(),
                ];
            } else {
                $responseStructure['exception'] = [
                    'message' => $data['exception']->getMessage()
                ];
            }

            if ($statusCode === 200) {
                $statusCode = 500;
            }
        }

        return ["content" => $responseStructure, "statusCode" => $statusCode, "headers" => $headers];
    }

    protected function apiResponse(array $data = [], int $statusCode = 200, array $headers = []): JsonResponse
    {
        $result = $this->parseGivenData($data, $statusCode, $headers);


        return response()->json(
            $result['content'], $result['statusCode'], $result['headers']
        );
    }

    protected function respondError($message, int $statusCode = 400, Exception $exception = null): JsonResponse
    {
        return $this->apiResponse(
            [
                'message' => $message ?? 'There was an internal error, Pls try again later',
                'exception' => $exception
            ], $statusCode
        );
    }

    protected function respondWithEmptyData($apiCode)
    {
        return response(null, $apiCode);
    }
}