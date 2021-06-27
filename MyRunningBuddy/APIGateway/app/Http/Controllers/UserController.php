<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Helpers\HttpHelper;
use App\Helpers\ResponseHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Response;
use Validator;
use Auth;

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
            return ResponseHelper::GenerateValidatorErrorMessage($validator->errors());

        // check whether a user with this email already exists
        if(User::where('email', $input['email'])->count() > 0)
            return ResponseHelper::GenerateSimpleTextResponse('User with this email address already exists',Response::HTTP_BAD_REQUEST);

        // pass additional information about user to Runner Management Service
        $response = HttpHelper::request('post', 'RunnerManagementService', '/runner', [], $input);
        if($response == null)
            return ResponseHelper::GenerateInternalServiceUnavailableErrorResponse();

        // check if this operation was successful, and if it isn't try to return a meaningful error
        if($response->status() != Response::HTTP_CREATED || !isset($response['runner']['id']))
            return ResponseHelper::GenerateErrorResponseFromAnotherResponse($response, 'Internal service error');

        // if everything is ok, make a user
        $input['password'] = Hash::make($input['password']);
        $input['id'] = $response['runner']['id'];
        $user = User::create($input);

        return response()->json(['user' => $user, 'reference' => "/user/$user->id", 'message' => 'User created successfully'],
            Response::HTTP_CREATED, [], JSON_UNESCAPED_SLASHES);
    }

    public function get_user(Request $request, $id)
    {
        if(User::where('id', $id)->count() != 1)
            return ResponseHelper::GenerateSimpleTextResponse("User doesn't exist.", Response::HTTP_NOT_FOUND);

        $headers = ['X-User' => Auth::user()->id];
        $response = HttpHelper::request('get', 'RunnerManagementService', "/runner/$id", $headers, []);
        if($response == null)
            return ResponseHelper::GenerateInternalServiceUnavailableErrorResponse();

        return response()->json($response->json(), Response::HTTP_OK, [], JSON_UNESCAPED_SLASHES);
    }
}
