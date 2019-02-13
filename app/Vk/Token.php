<?php

namespace App\Vk;

use Illuminate\Database\Eloquent\Model;

class Token extends Model
{
    public $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'photo_url',
        'token',
        'expires_in',
    ];

    public function scopeExpired($query)
    {
        $query->whereRaw('TIMESTAMPADD(SECOND, expires_in, created_at) <= CURRENT_TIMESTAMP() AND expires_in != 0');
    }

    public function scopeActive($query)
    {
        $query->whereRaw('expires_in = 0 OR TIMESTAMPADD(SECOND, expires_in, created_at) > CURRENT_TIMESTAMP()');
    }
}
