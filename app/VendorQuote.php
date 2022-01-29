<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class VendorQuote extends Model
{

   protected $table = 'vendor_quotes';

   protected $fillable = [
        'user_id', 'vendor_id', 'quote_id', 'expiry_date', 'status','price','discount','photo','document','additional_details','isResponded','responded_at'
		//'furniture_type', 'material_type', 'color', 'price', 'additionalDetails', 'photo', 'cat_type','quote_id','vendor_id', 'customer_id', 'status','availability'
    ];

    public function Vendor()
	{
		return $this->belongsTo('App\Vendor','vendor_id');
    }

    public function Customer()
	{
		return $this->belongsTo('App\Customer','customer_id');
    }

    public function Quote()
	{
		return $this->belongsTo('App\Quote','quote_id');
	}

}
