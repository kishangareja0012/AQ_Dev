<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Spatie\Permission\Traits\HasRoles;

class Customer extends Authenticatable
{
	use HasRoles;
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array

     */
    protected $table = 'customers';

    protected $fillable = [
        'user_id','username','is_privacy','email','mobile','password','status','api_key','address','city','gender','agree','otp', 'device_token'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
	 public function user()
	{
		return $this->belongsTo('App\User','user_id');
	}
    public function VendorQuote()
    {
        return $this->hasOne('App\VendorQuote','customer_id');
    }
}
