<?php

namespace App\Http\Controllers;

use App\Helpers\HttpHelper;
use App\Helpers\ResponseHelper;
use App\Models\ExternalAccount;
use App\Models\ExternService;
use App\Models\Runner;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Validator;

class RunnerController extends Controller
{
    public function register(Request $request)
    {
        $input = $request->all();

        // data validation
        $validator = Validator::make($input, [
            'name' => 'required|string|max:30',
            'surname' => 'sometimes|string|max:30',
            'aboutme' => 'sometimes|string|max:256',
            'preferences' => 'sometimes|string|max:256',
            'location' => 'sometimes|string|max:64'
        ]);

        if($validator->fails())
            return ResponseHelper::GenerateValidatorErrorMessage($validator->errors());

        // create a runner
        $runner = Runner::create($input);

        return response()->json(['runner' => $runner, 'reference' => "/runner/$runner->id", 'message' => 'Runner created successfully'],
            Response::HTTP_CREATED, [], JSON_UNESCAPED_SLASHES);
    }

    public function get_runner(Request $request, $id)
    {
        $runner = Runner::where('id', $id)->first();

        if($runner == null)
            return ResponseHelper::GenerateSimpleTextResponse("Runner doesn't exist", Response::HTTP_NOT_FOUND);

        return response()->json($runner, Response::HTTP_OK, [], JSON_UNESCAPED_SLASHES);
    }

    public function update_runner(Request $request, $id)
    {
        if(!$this->check_if_authorized($request, $id))
            return ResponseHelper::GenerateSimpleTextResponse('Unauthorized.', Response::HTTP_UNAUTHORIZED);

        $runner = Runner::where('id', $id)->first();

        if($runner == null)
            return ResponseHelper::GenerateSimpleTextResponse("Runner doesn't exist", Response::HTTP_NOT_FOUND);

        // data validation
        $input = $request->all();

        $validator = Validator::make($input, [
            'name' => 'sometimes|string|max:256',
            'surname' => 'sometimes|string|max:256',
            'aboutme' => 'sometimes|string|max:256',
            'preferences' => 'sometimes|string|max:256',
            'location' => 'sometimes|string|max:64'
        ]);

        if($validator->fails())
            return ResponseHelper::GenerateValidatorErrorMessage($validator->errors());

        // update a runner
        $runner->fill($input);
        $runner->save();

        return response()->json($runner, Response::HTTP_OK, [], JSON_UNESCAPED_SLASHES);
    }

    public function get_linked_services(Request $request, $id)
    {
        if(!$this->check_if_authorized($request, $id))
            return ResponseHelper::GenerateSimpleTextResponse('Unauthorized.', Response::HTTP_UNAUTHORIZED);

        if(Runner::where('id', $id)->count() != 1)
            return ResponseHelper::GenerateSimpleTextResponse("Runner doesn't exist", Response::HTTP_NOT_FOUND);

        // check for every external service if the user has linked account
        $result = [];
        $externServices = ExternService::all();
        foreach($externServices as $service)
        {
            // account is linked if access_token is populated
            $linked = ExternalAccount::where('runner_id', $id)
                    ->where('service_name', $service->service_name)
                    ->where('access_token', '!=', '')->count() == 1;

            array_push($result, ['service' => $service, 'linked' => $linked]);
        }

        return response()->json($result, Response::HTTP_OK, [], JSON_UNESCAPED_SLASHES);
    }

    public function get_authorization_params(Request $request, $id)
    {
        if(!$this->check_if_authorized($request, $id))
            return ResponseHelper::GenerateSimpleTextResponse('Unauthorized.', Response::HTTP_UNAUTHORIZED);

        if(Runner::where('id', $id)->count() != 1)
            return ResponseHelper::GenerateSimpleTextResponse("Runner doesn't exist", Response::HTTP_NOT_FOUND);

        // data validation
        $input = $request->all();

        $validator = Validator::make($input, [
            'service_name' => 'required|string|max:64',
        ]);

        if($validator->fails())
            return ResponseHelper::GenerateValidatorErrorMessage($validator->errors());

        // check if the service exists
        if(ExternService::where('service_name', $input['service_name'])->count() != 1)
            return ResponseHelper::GenerateSimpleTextResponse("Extern service doesn't exist", Response::HTTP_NOT_FOUND);

        // contact external service gateway for authorization params
        $response = HttpHelper::request('get', $input['service_name'], "/authorization_params", [], []);
        if($response == null)
            return ResponseHelper::GenerateInternalServiceUnavailableErrorResponse();

        // check if this operation was successful, and if it isn't try to return a meaningful error
        if($response->status() != Response::HTTP_OK)
            return ResponseHelper::GenerateErrorResponseFromAnotherResponse($response);

        $responseJson = $response->json();
        if(isset($responseJson['authorization_url']))
        {
            $authorization_url = $responseJson['authorization_url'];

            $externalAccount = ExternalAccount::where('service_name', $input['service_name'])->where('runner_id', $id)->first();

            if($externalAccount == null)
            {
                $externalAccount = new ExternalAccount();

                $externalAccount->service_name = $input['service_name'];
                $externalAccount->runner_id = $id;
            }

            $externalAccount->confirmation_id = $externalAccount->runner_id * 10000000000 + rand(1000000000, 9999999999);

            $externalAccount->save();

            $authorization_url = $authorization_url . "/" . $externalAccount->confirmation_id;

            return response()->json(['authorization_url' => $authorization_url], Response::HTTP_OK, [], JSON_UNESCAPED_SLASHES);
        }
        else
            return ResponseHelper::GenerateSimpleTextResponse('Authorization URL missing', Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    private function check_if_authorized(Request $request, $id)
    {
        $authenticated_user = $request->header('X-User');

        if($authenticated_user == null or $authenticated_user != $id)
            return false;

        return true;
    }
}
