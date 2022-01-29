<?php

namespace Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use DB;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //$this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        //$settings = DB::table('settings')->get();

        return view('home');
    }
    public function extract_zip()
    {
        return view('extract');
    }

    public function settings()
    {
        $setting = DB::table('settings')->first();
        return view('admin.settings', compact('setting'));
    }

    public function store(Request $request)
    {
        $setting = DB::table('settings')->where('id', 1)->update([
            'quote_responses_customer' => $request->quote_responses_customer,
            'customer_info_visible' => $request->customer_info_visible,
            'phone_number_visible' => $request->phone_number_visible,
            'address_visible' => $request->address_visible,
            'email_visible' => $request->email_visible,
            'quote_location' => $request->quote_location,
            'hide_all' => $request->hide_all,
            'sms_send' => $request->sms_send,
            'vendor_request_to_users' => $request->vendor_request_to_users,
        ]);
        return redirect()->route('admin.settings')->with('flash_message', 'Setting updated successfully..!');
    }
}