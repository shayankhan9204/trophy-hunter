<?php

namespace App\Helpers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class APIResponse
{
    /**
     * Get User Current Role
     *
     * @return string
     */
    /**
     * API Success Response Helper Method
     *
     * @param string $msg
     * @param array $data
     * @param int $status
     * @return JsonResponse
     */
    public static function success(string $msg = '', array $data = [], int $status = 200): JsonResponse
    {
        return response()->json([
            'message' => $msg,
            'data' => $data,
            'success' => true,
        ], $status);
    }

    /**
     * API Error Response Helper Method
     *
     * @param int $status
     * @param string $msg
     * @param array $data
     * @return JsonResponse
     */
    public static function error(string $msg, array $data = [], int $status = 200): JsonResponse
    {
        return response()->json([
            'message' => $msg,
            'data' => $data,
            'success' => false,
        ], $status);
    }

    /**
     * API Exception Error Response Helper Method
     *
     * @param int $status
     * @param string $msg
     * @param array $data
     * @return JsonResponse
     */
    // public static function exception(string $msg = "Something went wrong. Please try again.", array $data = [], int $status = 500): JsonResponse
    public static function exception(string $msg, array $data = [], int $status = 200): JsonResponse
    {
        return response()->json([
            'message' => $msg,
            'data' => $data,
            'success' => false,
        ], $status);
    }
}
