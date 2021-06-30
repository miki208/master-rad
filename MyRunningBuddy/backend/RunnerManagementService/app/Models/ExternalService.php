<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExternalService extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = 'ExternalServices';

    protected $fillable = [
        'service_name', 'human_friendly_name'
    ];

    protected $hidden = [
    ];
}
