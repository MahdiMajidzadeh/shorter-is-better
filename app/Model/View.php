<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class View extends Model
{
    public  function  user()
    {
        return $this->belongsTo('App\Model\Link');
    }
}
