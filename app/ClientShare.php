<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClientShare extends Model
{
    //
    use SoftDeletes;
    protected $guarded = [];
}
