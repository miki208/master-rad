<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExternalAccount extends Model
{
    const CONFIRMATION_ID_AUTHORIZED = 0;
    const CONFIRMATION_ID_REVOKED = 1;

    protected $table = 'ExternalAccounts';

    protected $fillable = [

    ];

    protected $hidden = [

    ];
}
