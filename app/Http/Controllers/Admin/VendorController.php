<?php

namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;

use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use App\User;
use App\Vendor;
use App\Quotes;
use Auth;
use App\VendorQuote;

//Importing laravel-permission models
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

//Enables us to output flash messaging
use Session;

class VendorController extends Controller
{
    public function __construct() {
        //$this->middleware(['auth', 'isAdmin']); //isAdmin middleware lets only users with a //specific permission permission to access these resources
    }
	/**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $vendors = Vendor::all();
        return view('admin.vendors.vendor')->with('vendors', $vendors);
    }

    public function add_vendor(){
        return view('admin.vendors.create_vendor');
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'mobile' => 'required|numeric|min:10|unique:vendors',
            'password' => 'required|string|min:6',
        ]);

        $user = User::create($request->only('email', 'name', 'password'));
        $role_r = Role::findOrFail('5');//Assigning Role to User
        $user->assignRole($role_r);

        if($user){
            $data['user_id'] = $user->id;
            $data['username']= $request->name;
            $data['email']= $request->email;
            $data['mobile']= $request->mobile;
            $data['password']= Hash::make($request->password);
            $data['state']= $request->state;
            $data['city']= $request->city;
            $data['pin']= $request->pin;
            $data['address']= $request->address;
            $data['company_name']= $request->company_name;
            $data['company_email']= $request->company_email;
            $data['company_number']= $request->company_number;
            $data['website']= $request->website;
            $data['company_state']= $request->company_state;
            $data['company_city']= $request->company_city;
            $data['company_pin']= $request->company_pin;
            $data['company_address']= $request->company_address;
            $data['furniture_type']= $request->furniture_type;
            $data['material_type']= $request->material_type;
            $data['cat_type']= $request->cat_type;
        }
        $vendor = Vendor::create($data); //Retrieving only the email and password data

        return redirect()->route('admin.vendors')
            ->with('flash_message', 'Vendor added successfully..!');

    }


    //Vendor Quotes tracking page
    public function vendor_quotes_tracking()
    {
        $tracking_vendor_quotes = VendorQuote::with(['Vendor','Customer','Quote'])->get();
        return view('admin.vendors.vendors-tracking', compact('tracking_vendor_quotes'));
    }

    //edit and get quote function
    public function editquote($quote_id)
    {
        //echo "quote id :".$quote_id;die();
        $quote = Quotes::findOrFail($quote_id);
        return view('admin/vendors/edit-quote', compact('quote'));
    }

}
