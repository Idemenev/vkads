<?php

namespace App\Vk;

use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'id',
        'name',
        'country_id',
    ];

    public function country()
    {
        $this->belongsTo(App\Vk\Country::class);
    }
}
