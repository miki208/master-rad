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
            return ResponseHelper::GenerateSimpleTextResponse('User with this email address already exists.',Response::HTTP_BAD_REQUEST);

        // pass additional information about user to Runner Management Service
        $response = HttpHelper::request('post', 'RunnerManagementService', '/runners', [], $input);
        if($response == null)
            return ResponseHelper::GenerateInternalServiceUnavailableErrorResponse();

        // check if this operation was successful, and if it isn't try to return a meaningful error
        if($response->status() != Response::HTTP_CREATED || !isset($response['runner']['id']))
            return ResponseHelper::GenerateErrorResponseFromAnotherResponse($response, 'Internal service error');

        // if everything is ok, make a user
        $input['password'] = Hash::make($input['password']);
        $input['id'] = $response['runner']['id'];
        $user = User::create($input);

        return response()->json([
            'user' => $user,
            'message' => 'User created successfully.',
            'actions' => [
                'get' => "/user/me",
                'patch' => "/user/me"
            ]
        ], $response->getStatusCode(), [], JSON_UNESCAPED_SLASHES);
    }

    public function get_user(Request $request, $id)
    {
        $id = $this->preprocess_userid_if_needed($id);

        return $this->check_user_and_pass_to_runner_management_service($request, 'get', $id, "/runner/$id");
    }

    public function update_user(Request $request, $id)
    {
        $id = $this->preprocess_userid_if_needed($id);

        return $this->check_user_and_pass_to_runner_management_service($request, 'patch', $id, "/runner/$id");
    }

    public function get_linked_services(Request $request, $id)
    {
        $id = $this->preprocess_userid_if_needed($id);

        return $this->check_user_and_pass_to_runner_management_service($request, 'get', $id, "/runner/$id/linked_services");
    }

    public function get_external_service_authorization_params(Request $request, $id)
    {
        $id = $this->preprocess_userid_if_needed($id);

        return $this->check_user_and_pass_to_runner_management_service($request, 'get', $id, "/runner/$id/external_service_authorization_params");
    }

    public function revoke_authorization_to_external_service(Request $request, $id, $service_name)
    {
        $id = $this->preprocess_userid_if_needed($id);

        return $this->check_user_and_pass_to_runner_management_service($request, 'delete', $id, "/runner/$id/external_service/$service_name");
    }

    private function preprocess_userid_if_needed($id)
    {
        // a special case when the user want to initiate an operation by using 'me' instead of a real user id in the route
        if($id == 'me')
            return Auth::user()->id;

        return $id;
    }

    private function check_user_and_pass_to_runner_management_service(Request $request, $method, $id, $route)
    {
        // first check if user exists
        if(User::where('id', $id)->count() != 1)
            return ResponseHelper::GenerateSimpleTextResponse("User doesn't exist.", Response::HTTP_NOT_FOUND);

        /*
         * Pass the request to runner management service.
         * We're going to send both $id and authenticated user id since a user can initiate some operation on behalf of another user.
         */
        $headers = ['X-User' => Auth::user()->id];
        $response = HttpHelper::request($method, 'RunnerManagementService', $route, $headers, $request->all());

        // handle errors if there are any and return a meaningful error if possible
        if($response == null)
            return ResponseHelper::GenerateInternalServiceUnavailableErrorResponse();

        if($response->status() != Response::HTTP_OK)
            return ResponseHelper::GenerateErrorResponseFromAnotherResponse($response, 'Internal service error');

        // everything is ok
        return response()->json($response->json(), $response->getStatusCode(), [], JSON_UNESCAPED_SLASHES);
    }
}
