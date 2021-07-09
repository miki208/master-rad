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

    public static function getMessages($conversationId, $page, $num_of_results_per_page, $from_id)
    {
        return Message::where('conversation_id', $conversationId)
            ->where('id', '>', $from_id)
            ->orderBy('id', 'desc')
            ->skip(($page - 1) * $num_of_results_per_page)
            ->take($num_of_results_per_page)
            ->get();
    }
}
