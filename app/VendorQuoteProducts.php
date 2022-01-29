<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class VendorQuoteProducts extends Model
{

   protected $table = 'vendor_quotes_products';

   protected $fillable = [
       'quote_id','vendor_quote_id', 'product_name', 'product_description','product_price','product_discount','product_expirydate','product_file'
		//'furniture_type', 'material_type', 'color', 'price', 'additionalDetails', 'photo', 'cat_type','quote_id','vendor_id', 'customer_id', 'status','availability'
    ];

    

}
