<?php

namespace App\Helpers;

use Illuminate\Http\Response;

class ResponseHelper
{
    public static function GenerateErrorResponseFromAnotherResponse($from_response, $base_error_msg)
    {
        $errors_array = [];

        if($from_response->status() == Response::HTTP_BAD_REQUEST)
        {
            if(isset($from_response['message']))
                $base_error_msg = $base_error_msg . ': ' . $from_response['message'];

            if(isset($from_response['errors']))
                $errors_array = $from_response['errors'];
        }

        $error_response['message'] = $base_error_msg;

        if(count($errors_array) > 0)
            $error_response['errors'] = $errors_array;

        return response()->json($error_response, $from_response->status(), [], JSON_UNESCAPED_SLASHES);
    }

    public static function GenerateSimpleTextResponse($msg, $status)
    {
        return response()->json(['message' => $msg], $status, [], JSON_UNESCAPED_SLASHES);
    }

    public static function GenerateInternalServiceUnavailableErrorResponse()
    {
        return self::GenerateSimpleTextResponse('Internal service is currently unavailable. Please try again later.', Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    public static function GenerateValidatorErrorMessage($error_array)
    {
        return response()->json(['message' => 'Validation errors', 'errors' => $error_array],Response::HTTP_BAD_REQUEST, [],JSON_UNESCAPED_SLASHES);
    }
}
