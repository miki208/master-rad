<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Helpers\ResponseHelper;
use Illuminate\Http\Response;
use Illuminate\Http\Request;
use Validator;

class ServiceController extends Controller
{
    public function AddService(Request $request)
    {
        $input = $request->all();

        $validator = Validator::make($input, [
            'service_name' => 'required|string|max:64',
            'location' => 'required|string|max:256',
            'port' => 'required|integer',
        ]);

        if($validator->fails())
            return ResponseHelper::GenerateValidatorErrorMessage($validator->errors());

        $service = Service::create($input);

        return response()->json(['service' => $service, 'reference' => "/service/{$service->service_name}"], Response::HTTP_CREATED, [], JSON_UNESCAPED_SLASHES);
    }

    public function GetService(Request $request, $service_name)
    {
        if(Service::where('service_name', $service_name)->count() == 0)
            return ResponseHelper::GenerateSimpleTextResponse("This service doesn't exist", Response::HTTP_NOT_FOUND);

        $service = Service::where('service_name', $service_name)->get();

        return response()->json($service, Response::HTTP_OK, [], JSON_UNESCAPED_SLASHES);
    }

    public function GetAllServices(Request $request)
    {
        return Service::all();
    }

    public function DeleteService(Request $request, $service_id)
    {
        $service = Service::where('id', $service_id)->first();

        if($service == null)
            return ResponseHelper::GenerateSimpleTextResponse("This service doesn't exist", Response::HTTP_NOT_FOUND);

        $service->delete();

        return ResponseHelper::GenerateSimpleTextResponse('Service deleted successfully', Response::HTTP_OK);
    }

    public function UpdateService(Request $request, $service_id)
    {
        $input = $request->all();

        $validator = Validator::make($input, [
            'location' => 'sometimes|string|max:256',
            'port' => 'sometimes|integer',
        ]);

        if($validator->fails())
            return ResponseHelper::GenerateValidatorErrorMessage($validator->errors());

        $service = Service::where('id', $service_id)->first();

        if($service == null)
            return ResponseHelper::GenerateSimpleTextResponse("This service doesn't exist", Response::HTTP_NOT_FOUND);

        if(isset($input['location']))
            $service->location = $input['location'];

        if(isset($input['port']))
            $service->port = $input['port'];

        $service->save();

        return $service;
    }
}
