<?php

namespace App\Vk;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'id',
        'name',
        'parent_id'
    ];
}
