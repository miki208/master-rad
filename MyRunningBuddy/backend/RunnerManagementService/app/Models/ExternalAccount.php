<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ExternalAccount extends Model
{
    use HasFactory;

    const CONFIRMATION_ID_AUTHORIZED = 0;
    const CONFIRMATION_ID_REVOKED = 1;

    protected $table = 'ExternalAccounts';

    protected $fillable = [

    ];

    protected $hidden = [

    ];
}
