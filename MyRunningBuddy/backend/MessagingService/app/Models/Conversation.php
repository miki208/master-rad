<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\DB;


class Conversation extends Model
{
    use HasFactory;

    protected $table = 'conversations';

    protected $fillable = ['runner_id1', 'runner_id2'];

    protected $hidden = [];

    public static function getConversation($runner_id1, $runner_id2)
    {
        $conversation = Conversation::where('runner_id1', $runner_id1)
            ->where('runner_id2', $runner_id2)
            ->orWhere(function($query) use ($runner_id1, $runner_id2) {
                $query->where('runner_id1', $runner_id2)
                    ->where('runner_id2', $runner_id1);
            })->first();

        return $conversation;
    }

    public static function conversationExists($runner_id1, $runner_id2)
    {
        return self::getConversation($runner_id1, $runner_id2) != null;
    }

    public static function getConversations($runner_id1, $page, $num_of_results_per_page)
    {
        return Conversation::where('runner_id1', $runner_id1)
            ->orWhere('runner_id2', $runner_id1)
            ->orderBy('updated_at', 'desc')
            ->skip(($page - 1) * $num_of_results_per_page)
            ->take($num_of_results_per_page)
            ->get();
    }
}
