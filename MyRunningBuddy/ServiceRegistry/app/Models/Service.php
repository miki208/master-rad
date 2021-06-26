<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    protected $fillable = [
        'service_name', 'location', 'port'
    ];

    protected $hidden = [
    ];
}
