<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Runner extends Model
{
    protected $fillable = [
        'name', 'surname', 'aboutme', 'preferences'
    ];

    protected $hidden = [
        'password', 'updated_at', 'created_at'
    ];
}
