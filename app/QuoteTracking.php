<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Model;

class QuoteTracking extends Model
{
	use HasRoles;
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array

     */
	protected $table = 'quote_tracking';

    protected $fillable = [
        'vendor_id', 'quote_id', 'customer_id', 'status',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    public function Vendor()
	{
		return $this->belongsTo('App\Vendor','vendor_id');
    }
    public function Customer()
	{
		return $this->belongsTo('App\User','customer_id');
    }
    public function Quote()
	{
		return $this->belongsTo('App\Quote','quote_id');
	}

}
