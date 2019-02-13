<?php

namespace App\Vk;

use Illuminate\Database\Eloquent\Model;

class AdComment extends Model
{
    public $incrementing = false;

    protected $fillable = ['id', 'comment'];
}
