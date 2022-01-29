<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Model;

class Vendor extends Model
{
	use HasRoles;
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array

     */
    protected $table = 'vendors';
    protected $hidden = array('password');

    protected $fillable = [
        'name', 'email', 'mobile', 'password', 'company_name', 'company_phone', 'contact_person','website', 'company_email', 'company_number', 'company_address','company_city', 'company_state', 'company_pin', 'is_privacy','status',  'category','register_by_self','device_token','isVerified'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
	public function QuoteTracking()
	{
		return $this->hasOne('App\QuoteTracking','vendor_id');
    }

    public function VendorQuote()
	{
		return $this->hasOne('App\VendorQuote','vendor_id');
	}
    // public function messages()
    // {
    //     return $this->hasMany(Message::class);
    // }

}
