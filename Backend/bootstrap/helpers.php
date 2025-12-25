<?php

if (!function_exists('process')) {
    function process($success, $WrongCode = 422)
    {
        return response()->json(['success' => $success], $success ? 200 : $WrongCode);
    }
}

if (!function_exists('success_response')) {
    function success_response($data = null, ?string $message = null, int $statusCode = 200): \Illuminate\Http\JsonResponse
    {
        $response = ['success' => true];

        if ($message !== null) {
            $response['message'] = $message;
        }

        if ($data !== null) {
            $response['data'] = $data;
        }

        return response()->json($response, $statusCode);
    }
}

if (!function_exists('error_response')) {
    function error_response(string $message, int $statusCode = 400, ?array $errors = null): \Illuminate\Http\JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $statusCode);
    }
}
