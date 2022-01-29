<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Quote extends Model
{
   protected $fillable = [
        'furniture_type', 'material_type', 'color', 'minPrice', 'maxPrice', 'additionalDetails', 'photo', 'userId', 'cat_type', 'status'
    ];

    public function QuoteTracking()
    {
        return $this->hasOne('App\QuoteTracking','quote_id');
    }
    public function VendorQuote()
    {
        return $this->hasOne('App\VendorQuote','quote_id');
    }
}
