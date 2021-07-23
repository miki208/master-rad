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

        $conversation = Conversation::updateOrCreate($input, []);

        return response()->json($conversation, Response::HTTP_CREATED, [], JSON_UNESCAPED_SLASHES);
    }

    public function get_conversations(Request $request, $runner_id)
    {
        if(!ServiceSpecificHelper::check_if_authorized($request, $runner_id))
            return ResponseHelper::GenerateSimpleTextResponse('Unauthorized.', Response::HTTP_UNAUTHORIZED);

        $page = $request->get('page', 1);
        $num_of_results_per_page = $request->get('num_of_results_per_page', 10);
        $conversations_newer_than = $request->get('conversations_newer_than', '2000-01-01 00:00:00');
        $conversations_older_than = $request->get('conversations_older_than', '9999-12-31 23:59:59');

        if($num_of_results_per_page > 50)
            $num_of_results_per_page = 50;

        $conversations = Conversation::getConversations($runner_id, $page, $num_of_results_per_page, $conversations_newer_than, $conversations_older_than);

        return response()->json(['conversations' => $conversations], Response::HTTP_OK, [], JSON_UNESCAPED_SLASHES);
    }

    public function get_conversation(Request $request, $runner_id1, $runner_id2)
    {
        if(!ServiceSpecificHelper::check_if_authorized($request, $runner_id1))
            return ResponseHelper::GenerateSimpleTextResponse('Unauthorized.', Response::HTTP_UNAUTHORIZED);

        $page = $request->get('page', 1);
        $num_of_results_per_page = $request->get('num_of_results_per_page', 10);
        $messages_newer_than = $request->get('messages_newer_than', '2000-01-01 00:00:00');
        $messages_older_than = $request->get('messages_older_than', '9999-12-31 23:59:59');

        if($num_of_results_per_page > 50)
            $num_of_results_per_page = 50;

        $conversation = Conversation::getConversation($runner_id1, $runner_id2);
        if($conversation == null)
            return ResponseHelper::GenerateSimpleTextResponse('Conversation does not exist.', Response::HTTP_BAD_REQUEST);

        // runner_id1 saw this conversation
        $shouldUpdateLastMessageSeen = $page == 0;
        $somethingChanged = false;
        if($runner_id1 == $conversation->runner_id1)
        {
            if($conversation->runner_id1_seen_conversation == false)
            {
                $conversation->runner_id1_seen_conversation = true;

                $somethingChanged = true;
            }

            if($shouldUpdateLastMessageSeen and $conversation->runner_id1_seen_last_message == false)
            {
                $conversation->runner_id1_seen_last_message = true;

                $somethingChanged = true;
            }
        }
        else
        {
            if($conversation->runner_id2_seen_conversation == false)
            {
                $conversation->runner_id2_seen_conversation = true;

                $somethingChanged = true;
            }

            if($shouldUpdateLastMessageSeen and $conversation->runner_id2_seen_last_message == false)
            {
                $conversation->runner_id2_seen_last_message = true;

                $somethingChanged = true;
            }
        }
        if($somethingChanged)
            $conversation->save();

        // get messages
        $messages = Message::getMessages($conversation->id, $page, $num_of_results_per_page, $messages_newer_than, $messages_older_than);

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

        // create new message
        $message = new Message();

        $message->conversation_id = $conversation->id;
        $message->runner_id = $runner_id1;
        $message->message = $input['message'];

        $message->save();

        // set 'unseen' last message for the runner 2
        if($runner_id2 == $conversation->runner_id1)
            $conversation->runner_id1_seen_last_message = false;
        else
            $conversation->runner_id2_seen_last_message = false;

        $conversation->save();

        // update the time when the last conversation is modified
        $conversation->touch();

        return ResponseHelper::GenerateSimpleTextResponse('Message created.', Response::HTTP_CREATED);
    }
}
