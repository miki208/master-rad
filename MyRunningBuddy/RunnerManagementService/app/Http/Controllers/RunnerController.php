<?php

namespace App\Http\Controllers;

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
            'name' => 'required|string|max:256',
            'surname' => 'sometimes|string|max:256',
            'aboutme' => 'sometimes|string|max:256',
            'preferences' => 'sometimes|string|max:256'
        ]);

        if($validator->fails())
            return response()->json(['message' => 'Validation errors', 'errors' => $validator->errors()],
                Response::HTTP_BAD_REQUEST, [], JSON_UNESCAPED_SLASHES);

        $runner = Runner::create($input);

        return response()->json(['runner' => $runner, 'reference' => "/runner/$runner->id", 'message' => 'Runner created successfully'],
            Response::HTTP_CREATED, [], JSON_UNESCAPED_SLASHES);
    }

    public function get_runner(Request $request, $id)
    {
        $runner = Runner::where('id', $id)->first();

        if($runner == null)
        {
            return response()->json(['message' => "Runner doesn't exist"], Response::HTTP_NOT_FOUND, [], JSON_UNESCAPED_SLASHES);
        }

        return $runner;
    }
}
