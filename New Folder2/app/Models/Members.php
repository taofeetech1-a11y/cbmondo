<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Members extends Model
{

    protected $guarded = [];

    function pollingUnit(){
        return $this->belongsTo(PollingUnit::class, 'polling_unit');
    }


}
