<?php
namespace Modules\Customer\Http\Controllers;

use App\Category;
use App\Http\Controllers\Controller;
use App\Http\Requests;
use App\MSG91;
use App\Quotes;
use App\User;
use App\Vendor;
use App\VendorQuote;
use App\VendorQuoteProducts;
use Auth;
use DB;
use Hash;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Session;
use Mail;

class CustomerController extends Controller
{

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/customer/quote-requests';

    /**
     * User Email.
     *
     * @var string
     */
    protected $sUserEmail = '';

    /**
     * User Mobile.
     *
     * @var string
     */
    protected $sUserMobile = '';

    /**
     * Vendor Email.
     *
     * @var string
     */
    protected $sVendorEmail = '';

/**
 * Vendor Mobile.
 *
 * @var string
 */
    protected $sVendorMobile = '';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //$this->middleware('guest')->except('logout');
        $this->user = new User;
    }

    /**
     * Function for Login.
     *
     * @return Response
     */
    public function userLogin(Request $request)
    {
        $password = $request->input('password');
        $email = $request->input('email');

        if (Auth::attempt(['email' => $email, 'password' => $password])) {

            echo json_encode(array(
                'loggedIn' => true,
            ));
        } else {
            echo json_encode(array(
                'loggedIn' => false,
                'error' => "Wrong Email password Combinatoin.",
            ));
        }
    }

    /**
     * Function for Register.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function userRegister(Request $request)
    {

        //validate data
        $this->validate($request, [
            'email' => 'required|email|unique:users',
            'mobile' => 'required|numeric|unique:users',
            'location' => 'required',
            'category' => 'required',
            'item' => 'required',
            'item_description' => 'required',
            'item_sample' => 'required',
        ]);

        preg_match('/(\S+)(@(\S+))/', $request->email, $match);

        $sSubMail = $match[1]; // output: `user`
        $input = $request->all();
        $input['name'] = $sSubMail;
        $input['password'] = Hash::make($sSubMail);
        $sLocation = $input['location'];

        if ($request->hasFile('item_sample')) {

            // file upload
            $image = $request->item_sample;
            $path = public_path('../public/assets/images/quotes/');
            $filename = time() . '.' . $image->getClientOriginalExtension();
            $image->move($path, $filename);

            //create user
            $user = new User();
            $user->name = $input['name'];
            $user->password = $input['password'];
            $user->email = $input['email'];
            $user->mobile = $input['mobile'];
            $userdata = $user;
            $sResult = $user->save();

            if ($sResult) {
                $sUserId = $user->id;

                //create quote
                $quote = Quotes::create([
                    'user_id' => $sUserId,
                    'location' => $sLocation,
                    'category' => $input['category'],
                    'item' => $input['item'],
                    'item_description' => $input['item_description'],
                    'item_sample' => $filename,
                    'status' => 'Quote Raised',
                ]);
                if ($quote) {
                    //Send Email Notification to customer
                    $data = array('name' => $user->name, 'email' => $user->email);
                    Mail::send('emails.customer-welcome', $data, function ($message) {
                        $message->subject('Interior Quotes - Account Created');
                        $message->from('kbshaik@aveitsolutions.com', 'Interior Quotes');
                        $message->to('praveenkolla4@gmail.com');
                    });

                    //Send SMS Notification to customer
                    $sQuoteId = $quote->id;
                    $sUserType = "Customer";
                    $sConfig = DB::table('settings')->select('customer_info_visible')->first();
                    $sCustInfoVisible = $sConfig->customer_info_visible;
                    $this->sUserEmail = $request->email;
                    $this->sUserMobile = $request->email;
                    $MSG91 = new MSG91();
                    $msg91Response = $MSG91->sendSMS($this->sUserEmail, $this->sUserMobile, $this->sVendorEmail = '', $this->sVendorMobile = '', $sConfig, $sUserType);

                    //Send SMS Notification to all matched vendors
                    $category = (int) $input['category'];
                    $aVendorInfo = DB::select(DB::raw('SELECT * FROM vendors WHERE FIND_IN_SET("' . $category . '", category)'));

                    foreach ($aVendorInfo as $vendor) {
                        $sUserType = "Vendor";
                        $sStatus = "New";
                        VendorQuote::create(['user_id' => $sUserId, 'quote_id' => $sQuoteId, 'vendor_id' => $vendor->id, 'status' => $sStatus]);
                        $msg91Response = $MSG91->sendSMS($this->sUserEmail, $this->sUserMobile, $vendor->email, $vendor->mobile, $sConfig, $sUserType);
                    }
                }
            }
            return redirect()->intended(url('/'))->with('flash_message', 'Your account has been successfully created. Login to check your quotes and responses.');

        }
    }

    /**
     * Show the application dataAjax.
     *
     * @return \Illuminate\Http\Response
     */
    public function dataAjax(Request $request)
    {
        $data = [];

        if ($request->has('q')) {
            $search = $request->q;
            $data = DB::table("categories")
                ->select("id", "category_name")
                ->where('category_name', 'LIKE', "%$search%")
                ->get();
        }

        return response()->json($data);
    }

    /**
     * Sending the OTP.
     *
     * @return Response
     */
    public function sendOtp(Request $request)
    {

        $aData = Input::all();
        $sMobileNumber = "91" . $request->custMobile;
        $response = array();

        $cntUserInfo = User::where('mobile', $request->custMobile)->count();

        if ($cntUserInfo > 0) {
            $aUserInfo = User::where('mobile', $request->custMobile)->first();
            $otp = rand(100000, 999999);
            $MSG91 = new MSG91();

            $msg91Response = $MSG91->sendOTP($otp, $sMobileNumber);

            if ($msg91Response['error']) {
                $response['error'] = 1;
                $response['message'] = $msg91Response['message'];
                $response['loggedIn'] = 1;
            } else {
                $response['error'] = 0;
                $response['message'] = 'OTP sent to Mobile/Email.';
                $response['OTP'] = $otp;
                $response['loggedIn'] = 1;
            }
            echo json_encode($response);
        } else {
            $response['error'] = 1;
            $response['message'] = "Sorry!!!. Please register and try to login";
            $response['loggedIn'] = 0;
            echo json_encode($response);
        }
    }

    /**
     * Function to verify OTP.
     *
     * @return Response
     */
    public function verifyOtp(Request $request)
    {
        $aData = Input::all();
        $sMobileNumber = "91" . $request->customer_mobile;
        $sOTP = $request->cust_otp;
        $response = array();
        $MSG91 = new MSG91();
        $msg91Response = $MSG91->verifyOTP($sOTP, $sMobileNumber);
        //$msg91Response["message"] = "otp_verified";
        //echo "<pre>"; print_r($msg91Response); exit;

        $msg91Response = (array) json_decode($msg91Response, true);
        //print_r($msg91Response); exit;
        if ($msg91Response["message"] == "otp_verified" || $msg91Response["message"] == "already_verified") {
            // Updating user's status "isVerified" as 1.
            $bUpdateUser = User::where('mobile', $request->customer_mobile)->update(['isVerified' => 1]);

            $response['error'] = 0;
            $response['isVerified'] = 1;
            $response['loggedIn'] = 1;
            $response['message'] = "Your Number is Verified.";

            // Get user record
            $user = DB::table('users')->where('mobile', $request->customer_mobile)->first();

            // Set Auth Details
            //Auth::login($user);
            Auth::loginUsingId($user->id);
            Session::put('USERID', $user->id);
            // if successful, then redirect to their intended location
            // Redirect home page

            // if successful, then redirect to their intended location
            return redirect()->intended(url('customer/quote-requests'));

        } else {
            $response['error'] = 1;
            $response['isVerified'] = 0;
            $response['loggedIn'] = 0;
            $response['message'] = "OTP does not match.";
            //echo json_encode($response);
            return redirect('/')->with('error-login', 'Error! OTP does not match.');
        }
    }

    /**
     * Display the the Home page.
     *
     * @return Response
     */
    public function dashboard()
    {
        if (Auth::check()) {
            $user = Auth::user();

            //Getting User information.
            $users = User::where('id', $user->id)->first();
            $userdata = Auth::user();

            $quotes_count = Quotes::where('user_id', '=', $userdata->id)->count();
            $quotes_sentresponses_count = VendorQuote::where('user_id', '=', $userdata->id)->count();
            $quotes_responded_count = VendorQuote::where('user_id', '=', $userdata->id)->where('isResponded', '=', 1)->count();

            $quoterequests = VendorQuote::leftjoin('vendors', 'vendors.id', '=', 'vendor_quotes.vendor_id')
                ->leftjoin('quotes', 'quotes.id', '=', 'vendor_quotes.quote_id')
                ->select('vendor_quotes.id as vendor_quote_id', 'vendor_quotes.created_at as response_created_at', 'vendors.*', 'quotes.item', 'quotes.item_sample', 'quotes.item_description', 'quotes.created_at as quote_created_at')
                ->where('vendor_quotes.user_id', '=', $userdata->id)
                ->where('vendor_quotes.isResponded', '=', 1)
                ->orderBy('vendor_quotes.id', 'DESC')
                ->limit(10)
                ->get();

            return view("customer::dashboard", ['quoterequests' => $quoterequests, 'quotes_count' => $quotes_count, 'quotes_sentresponses_count' => $quotes_sentresponses_count, 'quotes_responded_count' => $quotes_responded_count]);
        } else {
            return redirect('/');
        }
    }

    /**
     * Display the Quote Requests
     *
     * @return Response
     */
    public function quoteRequests()
    {
        $userdata = Auth::user();
        if ($userdata === null) {
            return redirect('/');
        }
        $keyword = Input::get('keyword');

        $quotequery = DB::table('quotes')->leftjoin("categories", "quotes.category", "=", "categories.id")
            ->select("quotes.*", "categories.category_name")
            ->where('quotes.user_id', '=', $userdata->id);

        if ($keyword != '') {
            $quotequery->where(function ($query) use ($keyword) {
                $query->where('item', 'LIKE', '%' . $keyword . '%');
                $query->orWhere('item_description', 'LIKE', '%' . $keyword . '%');
                $query->orWhere('location', 'LIKE', '%' . $keyword . '%');
            });
        }

        $quotes = $quotequery->orderBy('quotes.id', 'desc')->simplePaginate(15);
        // $quotes = $quotequery->orderBy('quotes.id','desc')->get();

        if (count($quotes) > 0) {
            foreach ($quotes as $key => $quote) {
                $quotes[$key]->count_sent = DB::table('vendor_quotes')->select("count('vendor_quotes.id')")->where('vendor_quotes.quote_id', '=', $quote->id)->groupBy("vendor_quotes.quote_id")->count();
                $quotes[$key]->count_responded = DB::table('vendor_quotes')->select("count('vendor_quotes.id')")->where('vendor_quotes.quote_id', '=', $quote->id)->where('vendor_quotes.isResponded', '=', 1)->groupBy("vendor_quotes.quote_id")->count();
                $quotes[$key]->min_price = DB::table('vendor_quotes')->where('vendor_quotes.quote_id', '=', $quote->id)->groupBy("vendor_quotes.quote_id")->min('vendor_quotes.price');
                $quotes[$key]->max_price = DB::table('vendor_quotes')->where('vendor_quotes.quote_id', '=', $quote->id)->groupBy("vendor_quotes.quote_id")->max('vendor_quotes.price');
            }
        }

        if (count($quotes) > 0) {
            return view("customer::quote-requests", compact('quotes'));
        } else {
            if ($keyword != '') {
                return view("customer::quote-requests", compact('quotes'));
            } else {
                return view("customer::no-quote");
            }
        }
    }

    public function ajax_quote_load(Request $request)
    {
        $keyword = $request->keyword;
        $page_no = $request->page ? $request->page : '1';
        $no_of_record = $page_no * 15;
        $offset = $no_of_record - 15;
        $userdata = Auth::user();
        $quotequery = DB::table('quotes')->leftjoin("categories", "quotes.category", "=", "categories.id")
            ->select("quotes.*", "categories.category_name")
            ->where('quotes.user_id', '=', $userdata->id);

        if ($keyword != '') {
            $quotequery->where(function ($query) use ($keyword) {
                $query->where('item', 'LIKE', '%' . $keyword . '%');
                $query->orWhere('item_description', 'LIKE', '%' . $keyword . '%');
                $query->orWhere('location', 'LIKE', '%' . $keyword . '%');
            });
        }

        // $quotes = $quotequery->orderBy('quotes.id','desc')->get();
        $quotes = $quotequery->orderBy('quotes.id', 'desc')->offset($offset)->limit(15)->simplePaginate(15);
        if (count($quotes) > 0) {
            foreach ($quotes as $key => $quote) {
                $quotes[$key]->count_sent = DB::table('vendor_quotes')->select("count('vendor_quotes.id')")->where('vendor_quotes.quote_id', '=', $quote->id)->groupBy("vendor_quotes.quote_id")->count();
                $quotes[$key]->count_responded = DB::table('vendor_quotes')->select("count('vendor_quotes.id')")->where('vendor_quotes.quote_id', '=', $quote->id)->where('vendor_quotes.isResponded', '=', 1)->groupBy("vendor_quotes.quote_id")->count();
                $quotes[$key]->min_price = DB::table('vendor_quotes')->where('vendor_quotes.quote_id', '=', $quote->id)->groupBy("vendor_quotes.quote_id")->min('vendor_quotes.price');
                $quotes[$key]->max_price = DB::table('vendor_quotes')->where('vendor_quotes.quote_id', '=', $quote->id)->groupBy("vendor_quotes.quote_id")->max('vendor_quotes.price');
            }
        }

        $this->data['quotes'] = $quotes;
        $returnHTML = view('customer::ajax_quote_load')->with($this->data)->render();
        return response()->json(array('success' => true, 'popup_html' => $returnHTML));
    }

    /**
     * Display the Quote Responses.
     *
     * @return Response
     */
    public function quoteSendVendors($quote_id)
    {
        $settings = DB::table('settings')->where('id',1)->first();
        $phone_number_visible = $settings->phone_number_visible;
        $address_visible = $settings->address_visible;
        $email_visible = $settings->email_visible;
        $hide_all = $settings->hide_all;
        $userdata = Auth::user();
        $quotedata = Quotes::where('id', '=', $quote_id)->first();

        $location = Input::get('location');
        $vendor = Input::get('vendor');
        $sortby = Input::get('sortby');

        $quotequery = VendorQuote::leftjoin('vendors', 'vendors.id', '=', 'vendor_quotes.vendor_id')
            ->select('vendor_quotes.id as vendor_quote_id', 'vendor_quotes.isResponded', 'vendor_quotes.created_at as response_created_at', 'vendor_quotes.expiry_date', 'vendor_quotes.price', 'vendors.*')
            ->where('user_id', '=', $userdata->id)
            ->where('quote_id', '=', $quote_id);

        if ($location != '') {
            $quotequery->where(function ($query) use ($location) {
                $query->where('company_address', 'LIKE', '%' . $location . '%');
                $query->orWhere('company_city', 'LIKE', '%' . $location . '%');
                $query->orWhere('company_state', 'LIKE', '%' . $location . '%');
            });
        }

        if ($vendor != '') {
            $quotequery->where(function ($query) use ($vendor) {
                $query->where('name', 'LIKE', '%' . $vendor . '%');
                $query->orWhere('company_name', 'LIKE', '%' . $vendor . '%');
                //$query->orWhere('company_state', 'LIKE', '%'.$vendor.'%');
            });
        }

        if ($hide_all=='Yes') {
            $quotequery->where(function ($query) use ($vendor) {
                $query->where('vendor_quotes.isResponded', 1);
                $query->orWhere('vendors.register_by_self', 1);
            });
        }

        if ($sortby == '') {
            $quotequery->orderBy('vendors.id', 'DESC');
        } else {
            if ($sortby == 'pricehigh2low') {
                $quotequery->orderBy('vendor_quotes.price', 'DESC')->where('vendor_quotes.price','!=','');
            } else if ($sortby == 'pricelow2high') {
                $quotequery->orderBy('vendor_quotes.price', 'ASC')->where('vendor_quotes.price','!=','');
            } else if ($sortby == 'newfirst') {
                $quotequery->orderBy('vendor_quotes.id', 'DESC')->where('vendor_quotes.price','!=','');
            } else if ($sortby == 'discounthigh') {
                $quotequery->orderBy('vendor_quotes.discount', 'DESC')->where('vendor_quotes.price','!=','');
            }else if($sortby == 'registered'){
                $quotequery->where('vendors.register_by_self','1');
            }
        }

        /* $quoteresponses = $quotequery->toSql();
        dd($quoteresponses); */
            $quoteresponses = $quotequery->limit(15)->simplePaginate(15);

        // dd($quoteresponses);


        $vendors_count_sent = DB::table('vendor_quotes')->select("count('vendor_quotes.id')")->where('vendor_quotes.quote_id', '=', $quote_id)->groupBy("vendor_quotes.quote_id")->count();
        $vendors_count_responded = DB::table('vendor_quotes')->select("count('vendor_quotes.id')")->where('vendor_quotes.quote_id', '=', $quote_id)->where('vendor_quotes.isResponded', '=', 1)->groupBy("vendor_quotes.quote_id")->count();

        if ($location != '' || $vendor != '' || $sortby != '') {
            $vendors_count_sent = $vendors_count_sent;
            if (isset($quoteresponses) && count($quoteresponses) < 15) {
                $vendors_count_sent = count($quoteresponses);
            }
        } else {
            $vendors_count_sent = $vendors_count_sent;
        }

        if ($location != '' || $vendor != '' || $sortby != '') {
            return view("customer::quote-sent-vendors-search", ['quote_id' => $quote_id, 'quoteresponses' => $quoteresponses, 'quotedata' => $quotedata, 'vendors_count_sent' => $vendors_count_sent, 'vendors_count_responded' => $vendors_count_responded,'phone_number_visible'=>$phone_number_visible,'address_visible'=>$address_visible,'email_visible'=>$email_visible,'hide_all'=>$hide_all]);
        } else {
            if (isset($_GET['expert']) && $_GET['expert'] == 1) {
                $expert = $_GET['expert']; // if vendor is not found then redirect to expert talk page
                return view("customer::quote-sent-vendors-search", ['quote_id' => $quote_id, 'quoteresponses' => $quoteresponses, 'quotedata' => $quotedata, 'vendors_count_sent' => $vendors_count_sent, 'vendors_count_responded' => $vendors_count_responded, 'expert' => $expert,'phone_number_visible'=>$phone_number_visible,'address_visible'=>$address_visible,'email_visible'=>$email_visible,'hide_all'=>$hide_all]);
            } else {
                return view("customer::quote-sent-vendors", ['quote_id' => $quote_id, 'quoteresponses' => $quoteresponses, 'quotedata' => $quotedata, 'vendors_count_sent' => $vendors_count_sent, 'vendors_count_responded' => $vendors_count_responded,'phone_number_visible'=>$phone_number_visible,'address_visible'=>$address_visible,'email_visible'=>$email_visible,'hide_all'=>$hide_all]);
            }
        }
    }

    public function ajax_load_sent_quote(Request $request)
    {
        $settings = DB::table('settings')->where('id',1)->first();
        $phone_number_visible = $settings->phone_number_visible;
        $address_visible = $settings->address_visible;
        $email_visible = $settings->email_visible;
        $hide_all = $settings->hide_all;
        $data = $request->all();
        $quote_id = $data['quote_id'];
        $location = $data['location'];
        $vendor = $data['vendor'];
        $sortby = $data['sortby'];
        $page_no = $data['page'] ? $data['page'] : '1';
        $no_of_record = $page_no * 15;
        $offset = $no_of_record - 15;
        $userdata = Auth::user();
        $quotedata = Quotes::where('id', '=', $quote_id)->first();

        $quotequery = VendorQuote::leftjoin('vendors', 'vendors.id', '=', 'vendor_quotes.vendor_id')
            ->select('vendor_quotes.id as vendor_quote_id', 'vendor_quotes.isResponded', 'vendor_quotes.created_at as response_created_at', 'vendor_quotes.expiry_date', 'vendor_quotes.price', 'vendors.*')
            ->where('user_id', '=', $userdata->id)
            ->where('quote_id', '=', $quote_id);


        if ($location != '') {
            $quotequery->where(function ($query) use ($location) {
                $query->where('company_address', 'LIKE', '%' . $location . '%');
                $query->orWhere('company_city', 'LIKE', '%' . $location . '%');
                $query->orWhere('company_state', 'LIKE', '%' . $location . '%');
            });
        }

        if ($vendor != '') {
            $quotequery->where(function ($query) use ($vendor) {
                $query->where('name', 'LIKE', '%' . $vendor . '%');
                $query->orWhere('company_name', 'LIKE', '%' . $vendor . '%');
                //$query->orWhere('company_state', 'LIKE', '%'.$vendor.'%');
            });
        }

        if ($hide_all=='Yes') {
            $quotequery->where(function ($query) use ($vendor) {
                $query->where('vendor_quotes.isResponded', 1);
                $query->orWhere('vendors.register_by_self', 1);
            });
        }

        if ($sortby == '') {
            $quotequery->orderBy('vendors.id', 'DESC');
        } else {
            if ($sortby == 'pricehigh2low') {
                $quotequery->orderBy('vendor_quotes.price', 'DESC')->where('vendor_quotes.price','!=','');
            } else if ($sortby == 'pricelow2high') {
                $quotequery->orderBy('vendor_quotes.price', 'ASC')->where('vendor_quotes.price','!=','');
            } else if ($sortby == 'newfirst') {
                $quotequery->orderBy('vendor_quotes.id', 'DESC')->where('vendor_quotes.price','!=','');
            } else if ($sortby == 'discounthigh') {
                $quotequery->orderBy('vendor_quotes.discount', 'DESC')->where('vendor_quotes.price','!=','');
            }else if($sortby == 'registered'){
                $quotequery->where('vendors.register_by_self', '1');
            }
        }
        // ->orderBy('vendor_quotes.price', 'DESC')->get();
        $quoteresponses = $quotequery->offset($offset)->limit(15)->simplePaginate(15);

        $vendors_count_sent = DB::table('vendor_quotes')->select("count('vendor_quotes.id')")->where('vendor_quotes.quote_id', '=', $quote_id)->groupBy("vendor_quotes.quote_id")->count();
        $vendors_count_responded = DB::table('vendor_quotes')->select("count('vendor_quotes.id')")->where('vendor_quotes.quote_id', '=', $quote_id)->where('vendor_quotes.isResponded', '=', 1)->groupBy("vendor_quotes.quote_id")->count();

        $this->data['quote_id'] = $quote_id;
        $this->data['quoteresponses'] = $quoteresponses;
        $this->data['quotedata'] = $quotedata;
        $this->data['phone_number_visible'] = $phone_number_visible;
        $this->data['address_visible'] = $address_visible;
        $this->data['email_visible'] = $email_visible;
        $this->data['hide_all'] = $hide_all;
        if ($location != '' || $vendor != '' || $sortby != '') {
            $this->data['vendors_count_sent'] = count($quoteresponses);
        } else {
            $this->data['vendors_count_sent'] = $vendors_count_sent;
        }
        $this->data['vendors_count_responded'] = $vendors_count_responded;
        $is_item = 0;
        if (count($quoteresponses)) {
            $is_item = 1;
        }
        $returnHTML = view('customer::ajax_load_sent_quote')->with($this->data)->render();
        return response()->json(array('success' => true, 'popup_html' => $returnHTML, 'is_item' => $is_item));
    }

    /**
     * Display the Quote Responses.
     *
     * @return Response
     */
    public function quoteResponses($quote_id)
    {
        $userdata = Auth::user();
        $quotedata = Quotes::where('id', '=', $quote_id)->first();

        $quoteresponses = VendorQuote::leftjoin('vendors', 'vendors.id', '=', 'vendor_quotes.vendor_id')
            ->select('vendor_quotes.id as vendor_quote_id', 'vendor_quotes.created_at as response_created_at', 'vendor_quotes.expiry_date', 'vendor_quotes.isResponded', 'vendor_quotes.price', 'vendors.*')
            ->where('user_id', '=', $userdata->id)
            ->where('quote_id', '=', $quote_id)
            ->where('isResponded', '=', 1)
            ->orderBy('vendor_quotes.id', 'DESC')
            ->get();

        $vendors_count_sent = DB::table('vendor_quotes')->select("count('vendor_quotes.id')")->where('vendor_quotes.quote_id', '=', $quote_id)->groupBy("vendor_quotes.quote_id")->count();
        $vendors_count_responded = DB::table('vendor_quotes')->select("count('vendor_quotes.id')")->where('vendor_quotes.quote_id', '=', $quote_id)->where('vendor_quotes.isResponded', '=', 1)->groupBy("vendor_quotes.quote_id")->count();

        return view("customer::quote-responses", ['quote_id' => $quote_id, 'quoteresponses' => $quoteresponses, 'quotedata' => $quotedata, 'vendors_count_sent' => $vendors_count_sent, 'vendors_count_responded' => $vendors_count_responded]);
    }

    public function createquote()
    {
        $categories = Category::pluck('category_name as name', 'id');
        return view('customer::create-quote-request', compact('categories'));
    }

    public function submitquote(Request $request)
    {
        $this->validate($request, [
            'location' => 'required',
            'category' => 'required',
            'item' => 'required',
            'item_description' => 'required',
            'item_sample' => 'required',
        ]);

        $userdata = Auth::user();
        $quote = new Quotes();
        //echo "<pre"; print_r($quotedata); exit;

        $filename = "";
        if ($request->hasFile('item_sample')) {
            // file upload
            $image = $request->item_sample;
            $path = public_path('../public/assets/images/quotes/');
            $filename = time() . '.' . $image->getClientOriginalExtension();
            $image->move($path, $filename);
        }

        $quote->user_id = $userdata->id;
        $quote->location = $request->location;
        $quote->category = $request->category;
        $quote->item = $request->item;
        $quote->item_description = $request->item_description;
        $quote->item_sample = $filename;
        $quote->status = "Y";
        $quote->is_privacy = (isset($request->isprivacy)) ? 1 : 0;
        $quote->save();

        $sQuoteId = $quote->id;

        $category = (int) $request->category;
        $aVendorInfo = DB::select(DB::raw('SELECT * FROM vendors WHERE FIND_IN_SET("' . $category . '", category)'));

        $sConfig = DB::table('settings')->select('customer_info_visible')->first();
        $sCustInfoVisible = $sConfig->customer_info_visible;

        $MSG91 = new MSG91();
        foreach ($aVendorInfo as $vendor) {
            $sUserType = "Vendor";
            //$sStatus = "Quote Raised";
            $sStatus = "New";
            VendorQuote::create(['user_id' => $userdata->id, 'quote_id' => $sQuoteId, 'vendor_id' => $vendor->id, 'status' => $sStatus]);
            //$msg91Response = $MSG91->sendSMS($userdata->email, $userdata->mobile, $vendor->email, $vendor->mobile, $sConfig, $sUserType);
        }

        return redirect('customer/quote-requests')->with('flash_message', 'Quote Created Successfully.');
    }

    public function editquote($quote_id)
    {
        $quote = Quotes::find($quote_id);
        $categories = Category::pluck('category_name as name', 'id');

        return view('customer::edit-quote-request', compact('quote', 'categories'));
    }

    public function updatequote($quote_id, Request $request)
    {
        $this->validate($request, [
            'location' => 'required',
            'category' => 'required',
            'item' => 'required',
            'item_description' => 'required',
        ]);

        $quote = Quotes::find($quote_id);
        $filename = "";
        if ($request->hasFile('item_sample')) {
            // file upload
            $image = $request->item_sample;
            $path = public_path('../public/assets/images/quotes/');
            $filename = time() . '.' . $image->getClientOriginalExtension();
            $image->move($path, $filename);
        }

        $quote->location = $request->location;
        $quote->category = $request->category;
        $quote->item = $request->item;
        $quote->item_description = $request->item_description;
        if ($request->hasFile('item_sample')) {
            $quote->item_sample = $filename;
        }
        $quote->is_privacy = (isset($request->isprivacy)) ? 1 : 0;

        $quote->save();

        return redirect('/customer/edit-quote-request/' . $quote_id)->with('flash_message', 'Quote Details Updated Successfully.');
    }

    public function view_sent_quote($vendor_quote_id, Request $request)
    {
        $vendorquote = VendorQuote::join('vendors', 'vendors.id', '=', 'vendor_quotes.vendor_id')->join('quotes', 'quotes.id', '=', 'vendor_quotes.quote_id')->select('vendors.*', 'vendor_quotes.*', 'quotes.*', 'vendors.is_privacy as vendor_is_privacy', 'vendor_quotes.id as vendor_quote_id', 'vendor_quotes.created_at as vendorquote_created_at', 'quotes.created_at as quote_created_at')->where('vendor_quotes.id', $vendor_quote_id)->first();
        $quote_min_price = DB::table('vendor_quotes')->where('vendor_quotes.id', '=', $vendor_quote_id)->groupBy("vendor_quotes.quote_id")->min('vendor_quotes.price');
        $quote_max_price = DB::table('vendor_quotes')->where('vendor_quotes.id', '=', $vendor_quote_id)->groupBy("vendor_quotes.quote_id")->max('vendor_quotes.price');

        $vendorquote_products = array();
        $vendor_quote_products_count = VendorQuoteProducts::where('vendor_quote_id', $vendor_quote_id)->count();
        if ($vendor_quote_products_count > 0) {
            $vendorquote_products = VendorQuoteProducts::where('vendor_quote_id', $vendor_quote_id)->get();
        }
        //dd(compact('vendorquote','vendorquote_products','vendor_quote_products_count','quote_min_price','quote_max_price'));
        return view('customer::view-send-quote-response', compact('vendorquote', 'vendorquote_products', 'vendor_quote_products_count', 'quote_min_price', 'quote_max_price'));
    }

    /**
     * Display the Profile page.
     *
     * @return Response
     */
    public function profile(Request $request)
    {
        if ($request->session()->has('USERID')) {
            $userdata = User::where('id', '=', $request->session()->get('USERID'))
                ->orderBy('id', 'DESC')
                ->first();
            return view("customer::profile", compact('userdata'));
        }
    }

    public function edit_profile(Request $request)
    {
        if (Auth::user() === null) {
            return redirect('/');
        }
        $userdata = array();
        if ($request->session()->has('USERID')) {
            $userdata = User::where('id', '=', $request->session()->get('USERID'))->orderBy('id', 'DESC')->first();
        }
        return view("customer::edit_profile", compact('userdata'));
    }

    public function submit_profile(Request $request)
    {
        $data = $request->all();
        $user_id = $request->session()->get('USERID');
        if ($request->all_verified == 1) {
            $user = User::find($user_id);
            $user->name = $request->name;
            $user->save();
            return redirect('/customer/edit_profile')->with('flash_message', 'Profile Updated Successfully.');
        } else {
            $otp = $data['cust_otp1'] . '' . $data['cust_otp2'] . '' . $data['cust_otp3'] . '' . $data['cust_otp4'];
            if ($request->login_method == "Email") {
                $register_user = User::where('id', '!=', $user_id)->where('mobile', $request->mobile)->get();
                if (count($register_user)) {
                    return redirect('/customer/edit_profile')->with('flash_error_message', 'Mobile already exists.');
                } else {
                    $user = User::find($user_id);
                    $user->name = $request->name;
                    // $user->email = $request->email;
                    $user->mobile = $request->mobile;
                    $user->email_mobile_verified = 1;
                    $user->save();
                }
            } else {
                $register_user = User::where('id', '!=', $user_id)->where('email', $request->email)->get();
                if (count($register_user)) {
                    return redirect('/customer/edit_profile')->with('flash_error_message', 'EmailID already exists.');
                } else {
                    if (md5($otp) == $data['email_sent_otp']) {
                        $user = User::find($user_id);
                        $user->name = $request->name;
                        $user->email = $request->email;
                        // $user->mobile = $request->mobile;
                        $user->email_mobile_verified = 1;
                        $user->save();
                    } else {
                        return redirect('/customer/edit_profile')->with('flash_error_message', 'OTP Not Verified.');
                    }
                }
            }
        }

        return redirect('/customer/edit_profile')->with('flash_message', 'Profile Updated Successfully.');
    }

    /**
     * Function to log out User
     * @return Response
     */
    public function logout()
    {
        Auth::logout();
        return redirect('/');
    }

    public function downloadFile($file)
    {
        $myFile = public_path($file);
        //$headers = ['Content-Type: application/pdf'];
        $newName = $file;
        return response()->download($myFile, $newName);
    }
}