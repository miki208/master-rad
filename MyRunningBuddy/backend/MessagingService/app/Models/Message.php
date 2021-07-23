<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Message extends Model
{
    use HasFactory;

    protected $table = 'messages';

    protected $fillable = ['conversation_id', 'runner_id', 'message'];

    protected $hidden = [];

    public static function getMessages($conversationId, $page, $num_of_results_per_page, $messages_newer_than, $messages_older_than)
    {
        return Message::where('updated_at', '>', $messages_newer_than)
            ->where('updated_at', '<', $messages_older_than)
            ->where('conversation_id', $conversationId)
            ->orderBy('updated_at', 'desc')
            ->skip(($page - 1) * $num_of_results_per_page)
            ->take($num_of_results_per_page)
            ->get();
    }
}
