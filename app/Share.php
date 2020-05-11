<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Share extends Model
{
    //
    use SoftDeletes;
    protected $dates = ['validity'];
    protected $guarded = [];
}
