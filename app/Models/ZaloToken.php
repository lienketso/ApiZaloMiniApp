<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ZaloToken extends Model
{
    protected $fillable = [
        'access_token',
        'refresh_token',
        'expires_in',
        'last_refreshed_at',
    ];
}
