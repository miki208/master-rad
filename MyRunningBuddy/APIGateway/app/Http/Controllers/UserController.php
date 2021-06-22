<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\HttpClientException;
use Illuminate\Http\Response;
use Validator;

class UserController extends Controller
{
    public function register(Request $request)
    {
        $input = $request->all();

        // data validation
        $validator = Validator::make($input, [
            'email' => 'required|email',
            'password' => 'required|min:6|regex:/^[A-Za-z0-9!$#%_]+$/'
        ]);

        if($validator->fails())
            return response()->json(['message' => 'Validation errors', 'errors' => $validator->errors()],
                Response::HTTP_BAD_REQUEST, [], JSON_UNESCAPED_SLASHES);

        // check whether a user with this email already exists
        $numberOfExistingUsers = User::where('email', $input['email'])->count();

        if($numberOfExistingUsers > 0)
        {
            return response()->json(['message' => 'User with this email address already exists'],
                Response::HTTP_BAD_REQUEST, [], JSON_UNESCAPED_SLASHES);
        }

        // pass additional information about user to Runner Management Service
        try {
            $response = Http::retry(config('httpclient.retries'))
                ->timeout(config('httpclient.timeout'))
                ->post(config('services.RunnerManagementServiceUrl') . '/runner', $input);
        } catch (HttpClientException $e)
        {
            return response()->json(['message' => 'Internal service is currently unavailable. Please try again later.'],
                Response::HTTP_INTERNAL_SERVER_ERROR, [], JSON_UNESCAPED_SLASHES);
        }

        // check if this operation was successful, and if it isn't try to return a meaningful error
        if($response->status() != Response::HTTP_CREATED)
        {
            $message = 'Internal service error';
            $errors_array = [];

            if($response->status() == Response::HTTP_BAD_REQUEST)
            {
                if(isset($response['message']))
                    $message = $message . ': ' . $response['message'];

                if(isset($response['errors']))
                    $errors_array = $response['errors'];
            }

            $error_response = [$message];
            if(count($errors_array) > 0)
                array_push($error_response, $errors_array);

            return response()->json($error_response, $response->status(), [], JSON_UNESCAPED_SLASHES);
        }

        // if everything is ok, make a user
        $input['password'] = Hash::make($input['password']);
        $user = User::create($input);

        return response()->json(['user' => $user, 'reference' => "/user/$user->id", 'message' => 'User created successfully'],
            Response::HTTP_CREATED, [], JSON_UNESCAPED_SLASHES);
    }
}
