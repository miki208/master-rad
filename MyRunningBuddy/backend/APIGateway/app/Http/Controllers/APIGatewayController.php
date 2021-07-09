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

class APIGatewayController extends Controller
{
    private $userChecked; // used to indicate whether the existence of the user id is already checked for the ongoing request

    function __construct()
    {
        $this->userChecked = false;
    }

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
        if($response->getStatusCode() != Response::HTTP_CREATED || !isset($response['runner']['id']))
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

        $response = $this->check_user_and_pass_to_another_service($request, 'get', $id, "/runner/$id", 'RunnerManagementService');

        if($response->status() !== Response::HTTP_OK)
            return $response;

        // we'll extend response and append additional data about this user
        $response = json_decode($response->content(), true);

        // append linked services info if available
        $linkedServicesResponse = $this->check_user_and_pass_to_another_service($request, 'get', $id, "/runner/$id/linked_services", 'RunnerManagementService');
        if($linkedServicesResponse->status() === Response::HTTP_OK)
            $response['linked_services'] = json_decode($linkedServicesResponse->content(), true);

        // append stats if available
        $statsResponse = $this->check_user_and_pass_to_another_service($request, 'get', $id, "/matcher/stats/{$id}", 'MatchingEngineService');
        if($statsResponse->status() === Response::HTTP_OK)
            $response['stats'] = json_decode($statsResponse->content(), true);

        return response()->json($response, Response::HTTP_OK, [], JSON_UNESCAPED_SLASHES);
    }

    public function update_user(Request $request, $id)
    {
        $id = $this->preprocess_userid_if_needed($id);

        return $this->check_user_and_pass_to_another_service($request, 'patch', $id, "/runner/$id", 'RunnerManagementService');
    }

    public function get_linked_services(Request $request, $id)
    {
        $id = $this->preprocess_userid_if_needed($id);

        return $this->check_user_and_pass_to_another_service($request, 'get', $id, "/runner/$id/linked_services", 'RunnerManagementService');
    }

    public function get_external_service_authorization_params(Request $request, $id)
    {
        $id = $this->preprocess_userid_if_needed($id);

        return $this->check_user_and_pass_to_another_service($request, 'get', $id, "/runner/$id/external_service_authorization_params", 'RunnerManagementService');
    }

    public function revoke_authorization_to_external_service(Request $request, $id, $service_name)
    {
        $id = $this->preprocess_userid_if_needed($id);

        if($service_name === 'all') // avoid using 'all' as a service name; it has a special meaning - it's revoking authorization for all external accounts
            return ResponseHelper::GenerateSimpleTextResponse("External service doesn't exist.", Response::HTTP_NOT_FOUND);

        return $this->check_user_and_pass_to_another_service($request, 'delete', $id, "/runner/$id/external_service/$service_name", 'RunnerManagementService');
    }

    public function get_runner_stats(Request $request, $id)
    {
        $id = $this->preprocess_userid_if_needed($id);

        return $this->check_user_and_pass_to_another_service($request, 'get', $id, "/matcher/stats/$id", 'MatchingEngineService');
    }

    public function set_runner_stats(Request $request, $id)
    {
        $id = $this->preprocess_userid_if_needed($id);

        // if user wants to use custom stats used for matching, authorization for all external accounts will be revoked
        $response = $this->check_user_and_pass_to_another_service($request, 'delete', $id, "/runner/$id/external_service/all", 'RunnerManagementService');
        if($response->status() != Response::HTTP_OK)
            return ResponseHelper::GenerateErrorResponseFromAnotherResponse($response, 'Internal service error');

        return $this->check_user_and_pass_to_another_service($request, 'post', $id, "/matcher/stats/$id", 'MatchingEngineService');
    }

    public function get_next_match(Request $request, $id)
    {
        $id = $this->preprocess_userid_if_needed($id);

        $response = $this->check_user_and_pass_to_another_service($request, 'get', $id, "/matcher/next_match/$id", 'MatchingEngineService');
        if($response->status() !== Response::HTTP_OK)
            return $response;

        // we'll extend response and append additional data about the matched user
        $response = json_decode($response->content(), true);
        $matched_id = $response['suggested_runner']['runner_id'];

        $userInfoResponse = $this->check_user_and_pass_to_another_service($request, 'get', $matched_id, "/runner/$matched_id", 'RunnerManagementService');
        if($userInfoResponse->status() === Response::HTTP_OK)
            $response['suggested_runner']['info'] = json_decode($userInfoResponse->content(), true);

        return response()->json($response, Response::HTTP_OK, [], JSON_UNESCAPED_SLASHES);
    }

    public function match_action(Request $request, $runner_id, $suggested_runner)
    {
        $runner_id = $this->preprocess_userid_if_needed($runner_id);

        return $this->check_user_and_pass_to_another_service($request, 'post', $runner_id, "/matcher/match/$runner_id/$suggested_runner", 'MatchingEngineService');
    }

    public function get_all_matches(Request $request, $id)
    {
        $id = $this->preprocess_userid_if_needed($id);

        return $this->check_user_and_pass_to_another_service($request, 'get', $id, "/matcher/$id/matches", 'MatchingEngineService');
    }

    public function add_message(Request $request, $id, $user_id2)
    {
        $id = $this->preprocess_userid_if_needed($id);

        return $this->check_user_and_pass_to_another_service($request, 'post', $id, "/messages/$id/$user_id2", 'MessagingService');
    }

    public function get_conversations(Request $request, $id)
    {
        $id = $this->preprocess_userid_if_needed($id);

        return $this->check_user_and_pass_to_another_service($request, 'get', $id, "/messages/$id", 'MessagingService');
    }

    public function get_conversation(Request $request, $id, $user_id2)
    {
        $id = $this->preprocess_userid_if_needed($id);

        return $this->check_user_and_pass_to_another_service($request, 'get', $id, "/messages/$id/$user_id2", 'MessagingService');
    }

    private function preprocess_userid_if_needed($id)
    {
        // a special case when the user want to initiate an operation by using 'me' instead of a real user id in the route
        if($id == 'me')
            return Auth::user()->id;

        return $id;
    }

    private function check_user_and_pass_to_another_service(Request $request, $method, $id, $route, $service_name)
    {
        // first check if user exists
        if(!$this->userChecked and User::where('id', $id)->count() != 1)
            return ResponseHelper::GenerateSimpleTextResponse("User doesn't exist.", Response::HTTP_NOT_FOUND);
        else
            $this->userChecked = true;

        /*
         * Pass the request to another service.
         * We're going to send both $id and authenticated user id since a user can initiate some operation on behalf of another user.
         */
        $headers = ['X-User' => Auth::user()->id];
        $response = HttpHelper::request($method, $service_name, $route, $headers, $request->all());

        // handle errors if there are any and return a meaningful error if possible
        if($response == null)
            return ResponseHelper::GenerateInternalServiceUnavailableErrorResponse();

        if($response->getStatusCode() != Response::HTTP_OK && $response->getStatusCode() != Response::HTTP_CREATED)
            return ResponseHelper::GenerateErrorResponseFromAnotherResponse($response, 'Internal service error');

        // everything is ok
        return response()->json($response->json(), $response->getStatusCode(), [], JSON_UNESCAPED_SLASHES);
    }
}
