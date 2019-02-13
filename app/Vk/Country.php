<?php

namespace App\Vk;

use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'id',
        'name',
    ];
}
