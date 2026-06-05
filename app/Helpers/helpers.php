<?php

if (! function_exists('api_success')) {
    function api_success($data = null, $message = 'Opération réussie', $code = 200)
    {
        $payload = [
            'success' => true,
            'error' => false,
            'message' => $message,
        ];

        if (! is_null($data)) {
            $payload['data'] = $data;
        }

        return response()->json($payload, $code);
    }
}

if (! function_exists('api_error')) {
    function api_error($message = 'Une erreur est survenue', $errors = null, $code = 400)
    {
        $payload = [
            'success' => false,
            'error' => true,
            'message' => $message,
        ];

        if (! is_null($errors)) {
            $payload['errors'] = $errors;
        }

        return response()->json($payload, $code);
    }
}
