<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Quotes extends Model
{
   protected $fillable = [
        'user_id', 'location', 'category', 'item', 'item_description', 'item_sample','status', 'is_privacy'
    ];

    public function user()
    {
        return $this->belongsTo('App\User','user_id');
    }
}
