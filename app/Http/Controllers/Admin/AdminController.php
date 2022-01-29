<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;


use Illuminate\Http\Request;
use App\User;
use App\Quotes;
use App\VendorQuote;
use App\Vendor;

class AdminController extends Controller
{
	use AuthenticatesUsers;

	protected $redirectTo = 'admin/dashboard';

	public function __construct() {
        $this->middleware(['auth', 'isAdmin']); //isAdmin middleware lets only users with a //specific permission permission to access these resources
    }
	public function index()
	{
	    //echo "admin controller";die();
		$users_count = User::count();
		$vendors_count = Vendor::count();
		$quotes_requests_count = Quotes::count();
		$quotes_responses_count = VendorQuote::where('isResponded',1)->count();
		
		return view('admin.dashboard',compact('users_count','vendors_count','quotes_requests_count','quotes_responses_count'));
	}
	public function mail()
	{
		return view('mail');
	}
	
	/**
		* Display the the Profile page.
     *
     * @return Response
     */
	public function profile(){
		return view("admin/profile");
    }

}
