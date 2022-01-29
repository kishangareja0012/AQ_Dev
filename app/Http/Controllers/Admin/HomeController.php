<?php

namespace App\Http\Controllers\Admin;

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
        $settings = DB::table('settings')->get();

        return view('admin.settings', compact('settings'));
    }

    public function store(Request $request)
    {
        $setting = DB::table('settings')->insert([
            'quote_responses_customer' => $request->quote_responses_customer,
            'customer_info_visible' => $request->customer_info_visible,

        ]);
        if ($setting) {
            //Display a successful message upon save
            return redirect()->route('admin.settings')
                ->with('flash_message', 'Setting created successfully..!');
        }

    }
}