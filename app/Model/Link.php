<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Link extends Model
{
    use SoftDeletes;

    public function views()
    {
        return $this->hasMany('App\Model\View');
    }

    public  function  user()
    {
        return $this->belongsTo('App\Model\User');
    }
}
