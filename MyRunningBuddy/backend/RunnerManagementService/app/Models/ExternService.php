<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExternService extends Model
{
    protected $table = 'ExternServices';

    protected $fillable = [
        'service_name', 'human_friendly_name'
    ];

    protected $hidden = [
    ];
}
