<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Helpers\ServiceSpecificHelper;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Validator;

class MessagingController extends Controller
{
    public function create_conversation(Request $request)
    {
        $input = $request->only(['runner_id1', 'runner_id2']);

        $validator = Validator::make($input, [
            'runner_id1' => 'required|integer',
            'runner_id2' => 'required|integer'
        ]);

        if($validator->fails())
            return ResponseHelper::GenerateValidatorErrorMessage($validator->errors());

        if(Conversation::conversationExists($input['runner_id1'], $input['runner_id2']))
            return ResponseHelper::GenerateSimpleTextResponse('Conversation already exists.', Response::HTTP_BAD_REQUEST);

        $conversation = Conversation::create($input);

        return response()->json($conversation, Response::HTTP_CREATED, [], JSON_UNESCAPED_SLASHES);
    }

    public function get_conversations(Request $request, $runner_id)
    {
        if(!ServiceSpecificHelper::check_if_authorized($request, $runner_id))
            return ResponseHelper::GenerateSimpleTextResponse('Unauthorized.', Response::HTTP_UNAUTHORIZED);

        $page = $request->get('page', 1);
        $num_of_results_per_page = $request->get('num_of_results_per_page', 10);

        if($num_of_results_per_page > 50)
            $num_of_results_per_page = 50;

        $conversations = Conversation::getConversations($runner_id, $page, $num_of_results_per_page);

        return response()->json(['conversations' => $conversations], Response::HTTP_OK, [], JSON_UNESCAPED_SLASHES);
    }

    public function get_conversation(Request $request, $runner_id1, $runner_id2)
    {
        if(!ServiceSpecificHelper::check_if_authorized($request, $runner_id1))
            return ResponseHelper::GenerateSimpleTextResponse('Unauthorized.', Response::HTTP_UNAUTHORIZED);

        $page = $request->get('page', 1);
        $num_of_results_per_page = $request->get('num_of_results_per_page', 10);
        $from_id = $request->get('from_id', 0);

        if($num_of_results_per_page > 50)
            $num_of_results_per_page = 50;

        $conversation = Conversation::getConversation($runner_id1, $runner_id2);
        if($conversation == null)
            return ResponseHelper::GenerateSimpleTextResponse('Conversation does not exist.', Response::HTTP_BAD_REQUEST);

        $messages = Message::getMessages($conversation->id, $page, $num_of_results_per_page, $from_id);

        return response()->json(['messages' => $messages], Response::HTTP_OK, [], JSON_UNESCAPED_SLASHES);
    }

    public function add_message(Request $request, $runner_id1, $runner_id2)
    {
        if(!ServiceSpecificHelper::check_if_authorized($request, $runner_id1))
            return ResponseHelper::GenerateSimpleTextResponse('Unauthorized.', Response::HTTP_UNAUTHORIZED);

        $input = $request->only(['message']);

        $validator = Validator::make($input, [
            'message' => 'required|string|max:1024'
        ]);

        if($validator->fails())
            return ResponseHelper::GenerateValidatorErrorMessage($validator->errors());

        $conversation = Conversation::getConversation($runner_id1, $runner_id2);
        if($conversation == null)
            return ResponseHelper::GenerateSimpleTextResponse('Conversation does not exist.', Response::HTTP_BAD_REQUEST);

        $message = new Message();

        $message->conversation_id = $conversation->id;
        $message->runner_id = $runner_id1;
        $message->message = $input['message'];

        $message->save();

        $conversation->touch();

        return ResponseHelper::GenerateSimpleTextResponse('Message created.', Response::HTTP_CREATED);
    }
}
