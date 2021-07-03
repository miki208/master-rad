<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use Illuminate\Http\Response;
use Illuminate\Http\Request;
use Log;

class ActivityImporterController extends Controller
{
    public function import_activities(Request $request)
    {
        $input = json_decode($request->getContent(), true);

        Log::info(json_encode($input));

        return ResponseHelper::GenerateSimpleTextResponse('Activities imported successfully.', Response::HTTP_OK);
    }
}
