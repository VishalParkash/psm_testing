<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class Profile extends Model
{
    //
    use SoftDeletes;
    protected $dates = ['validity'];
    protected $guarded = [];
}
