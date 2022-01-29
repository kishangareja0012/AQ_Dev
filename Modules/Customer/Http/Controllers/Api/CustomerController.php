<?php
namespace Modules\Customer\Http\Controllers\Api;

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

        /*  print_r($match);
        Array
        (
        [0] => user@email.com
        [1] => user
        [2] => @email.com
        [3] => email.com
        )
         */

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
                $response['message'] = 'Your OTP is created.';
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
    public function quoteRequests(Request $request)
    {
        $userid = $request->userid;
        $keyword = $request->keyword;
        $page_no = ($request->page_no != 0) ? $request->page_no : "1";
        $no_of_record = $page_no * 10;
        $offset = $no_of_record - 10;

        $quotequery = DB::table('quotes')->leftjoin("categories", "quotes.category", "=", "categories.id")
            ->select("quotes.*", DB::raw("DATE_FORMAT(quotes.created_at, '%d %M, %Y') as createdDate"), "categories.category_name")
            ->where('quotes.user_id', '=', $userid);

        if ($keyword != '') {
            $quotequery->where(function ($query) use ($keyword) {
                $query->where('item', 'LIKE', '%' . $keyword . '%');
            });
        }
        $quotes_count = $quotequery->count();
        $quotes = $quotequery->orderBy('quotes.id', 'desc')->offset($offset)->limit(10)->get();
        /*foreach ($quotes as $key => $value) {
        $quotes[$key] = $value;
        $quotes[$key]->item_sample_url = asset('public/assets/images/default.jpeg');
        if ($value->item_sample!='') {
        $quotes[$key]->item_sample_url = asset('public/assets/images/quotes/'.$value->item_sample);
        }
        }*/

        if (count($quotes) > 0) {
            foreach ($quotes as $key => $quote) {
                $quotes[$key] = $quote;
                $result = $this->getCountAndMinMaxValue($quote->id);
                $quotes[$key]->count_sent = isset($result[0]->total_count) ? $result[0]->total_count : 0;
                $quotes[$key]->count_responded = isset($result[0]->responded_count) ? $result[0]->responded_count : 0;
                $quotes[$key]->min_price = isset($result[0]->min_price) ? $result[0]->min_price : 0;
                $quotes[$key]->max_price = isset($result[0]->max_price) ? $result[0]->max_price : 0;
                $quotes[$key]->item_sample_url = asset('public/assets/images/default.jpeg');
                if ($quote->item_sample != '') {
                    $quotes[$key]->item_sample_url = asset('public/assets/images/quotes/' . $quote->item_sample);
                }
            }
        }
        $response['error'] = 0;
        $response['quotes'] = $quotes;
        $response['total_record_count'] = $quotes_count;
        echo json_encode($response);
    }

    public function getCountAndMinMaxValue($quote_id)
    {
        return DB::select("SELECT t1.quote_id,MIN(price) AS min_price,MAX(price) AS max_price,
                            (SELECT COUNT(*) FROM vendor_quotes t2 WHERE t2.quote_id = $quote_id) total_count,
                            (SELECT COUNT(*) FROM vendor_quotes t3 WHERE t3.quote_id = $quote_id AND t3.isResponded = 1) responded_count
                        FROM vendor_quotes t1
                        WHERE t1.quote_id = $quote_id
                        GROUP BY t1.quote_id");
    }

    /**
     * Display the Quote Responses.
     *
     * @return Response
     */
    public function quoteSendVendors_old(Request $request)
    {
        //$userdata = Auth::user();
        $userid = $request->userid;
        $quote_id = $request->quote_id;
        $page_no = ($request->page_no != 0) ? $request->page_no : "1";
        $no_of_record = $page_no * 10;
        $offset = $no_of_record - 10;
        $quotedata = Quotes::where('id', '=', $quote_id)->first();

        $settings = DB::table('settings')->where('id', 1)->first();
        $phone_number_visible = $settings->phone_number_visible;
        $address_visible = $settings->address_visible;
        $email_visible = $settings->email_visible;
        $hide_all = $settings->hide_all;

        /* $location = Input::get('location');
        $vendor = Input::get('vendor');
        $sortby = Input::get('sortby');*/
        $location = '';
        $vendor = '';
        $sortby = '';

        $quotequery = VendorQuote::leftjoin('vendors', 'vendors.id', '=', 'vendor_quotes.vendor_id')
            ->select('vendor_quotes.id as vendor_quote_id', 'vendor_quotes.isResponded', 'vendor_quotes.created_at as response_created_at', 'vendor_quotes.expiry_date', 'vendor_quotes.price', 'vendor_quotes.photo', 'vendor_quotes.document', 'vendors.*')
            ->where('user_id', '=', $userid)
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

        if ($hide_all == 'Yes') {
            $quotequery->where(function ($query) use ($vendor) {
                $query->where('vendor_quotes.isResponded', 1);
                $query->orWhere('vendors.register_by_self', 1);
            });
        }

        if ($sortby == '') {
            $quotequery->orderBy('vendor_quotes.price', 'DESC');
        } else {
            if ($sortby == 'pricehigh2low') {
                $quotequery->orderBy('vendor_quotes.price', 'DESC');
            } else if ($sortby == 'pricelow2high') {
                $quotequery->orderBy('vendor_quotes.price', 'ASC');
            } else if ($sortby == 'newfirst') {
                $quotequery->orderBy('vendor_quotes.id', 'DESC');
            } else if ($sortby == 'discounthigh') {
                $quotequery->orderBy('vendor_quotes.discount', 'DESC');
            }
        }

        $record_count = $quotequery->count();
        $quoteresponses = $quotequery->offset($offset)->limit(10)->get();

        $vendors_count_sent = DB::table('vendor_quotes')->select("count('vendor_quotes.id')")->where('vendor_quotes.quote_id', '=', $quote_id)->groupBy("vendor_quotes.quote_id")->count();
        $vendors_count_responded = DB::table('vendor_quotes')->select("count('vendor_quotes.id')")->where('vendor_quotes.quote_id', '=', $quote_id)->where('vendor_quotes.isResponded', '=', 1)->groupBy("vendor_quotes.quote_id")->count();

        foreach ($quoteresponses as $key => $value) {
            $quoteresponses[$key] = $value;
            $quoteresponses[$key]['photo_url'] = $quoteresponses[$key]['document_url'] = asset('public/assets/images/default.jpeg');
            if ($value['photo'] != '') {
                $quoteresponses[$key]['photo_url'] = asset('public/assets/images/quote-responses/' . $value['photo']);
            }
            if ($value['document']) {
                $quoteresponses[$key]['document_url'] = asset('public/assets/documents/quote-responses/' . $value['document']);
            }
        }
        $response['error'] = 0;
        $response['quote_id'] = $quote_id;
        $response['quoteresponses'] = $quoteresponses;
        $response['quotedata'] = $quotedata;
        $response['vendors_count_sent'] = $vendors_count_sent;
        $response['vendors_count_responded'] = $vendors_count_responded;
        $response['total_record_count'] = $record_count;
        echo json_encode($response);
    }

    public function quoteSendVendors(Request $request)
    {
        // dd($request->all());
        //$userdata = Auth::user();
        $userid = $request->userid;
        $quote_id = $request->quote_id;
        $page_no = ($request->page_no != 0) ? $request->page_no : "1";
        $no_of_record = $page_no * 10;
        $offset = $no_of_record - 10;
        $quotedata = Quotes::where('id', '=', $quote_id)->first();

        $settings = DB::table('settings')->where('id', 1)->first();
        $phone_number_visible = $settings->phone_number_visible;
        $address_visible = $settings->address_visible;
        $email_visible = $settings->email_visible;
        $hide_all = $settings->hide_all;

        /* $location = Input::get('location');
        $vendor = Input::get('vendor');
        $sortby = Input::get('sortby');*/
        $location = '';
        $vendor = '';
        $sortby = '';

        if ($hide_all == "Yes") {
            $search_query = "SELECT `vendor_quotes`.`id` AS `vendor_quote_id`, `vendor_quotes`.`isResponded`, `vendor_quotes`.`created_at` AS `response_created_at`,
            `vendor_quotes`.`expiry_date`, `vendor_quotes`.`price`,`vendor_quotes`.`photo`,`vendor_quotes`.`document`, `vendors`.*
            FROM `vendor_quotes`
            LEFT JOIN `vendors` ON `vendors`.`id` = `vendor_quotes`.`vendor_id`
            WHERE `quote_id` = '$quote_id' AND (`isResponded` != 0 OR `vendor_quotes`.`vendor_id`
            IN(SELECT id FROM vendors WHERE `register_by_self`!=0 AND (`name` LIKE '%$vendor%' OR `company_city` LIKE '%$location%' OR `company_address` LIKE '%$location%'))) LIMIT $offset,10";
        } else {
            $search_query = "SELECT `vendor_quotes`.`id` AS `vendor_quote_id`, `vendor_quotes`.`isResponded`, `vendor_quotes`.`created_at` AS `response_created_at`,
            `vendor_quotes`.`expiry_date`, `vendor_quotes`.`price`,`vendor_quotes`.`photo`,`vendor_quotes`.`document`, `vendors`.*
            FROM `vendor_quotes`
            LEFT JOIN `vendors` ON `vendors`.`id` = `vendor_quotes`.`vendor_id`
            WHERE `quote_id` = '$quote_id' AND (`vendor_quotes`.`vendor_id` IN(SELECT id FROM vendors WHERE (`name` LIKE '%$vendor%' OR `company_city` LIKE '%$location%' OR `company_address` LIKE '%$location%'))) LIMIT $offset,10";
        }
        // dd($search_query);
        $quotequery = DB::select(DB::raw($search_query));
        // dd($quotequery->toSql());
        $record_count = count($quotequery);

        $vendors_count_sent = DB::table('vendor_quotes')->select("count('vendor_quotes.id')")->where('vendor_quotes.quote_id', '=', $quote_id)->groupBy("vendor_quotes.quote_id")->count();
        $vendors_count_responded = DB::table('vendor_quotes')->select("count('vendor_quotes.id')")->where('vendor_quotes.quote_id', '=', $quote_id)->where('vendor_quotes.isResponded', '=', 1)->groupBy("vendor_quotes.quote_id")->count();
        $quote_result = $quotequery;

        if ($hide_all != "Yes") {
            $quote_result = array();
            foreach ($quotequery as $key => $value) {
                $quote_result[$key] = $value;
                $quote_result[$key]->photo_url = $quote_result[$key]->document_url = asset('public/assets/images/default.jpeg');
                if ($value->photo != '') {
                    $quote_result[$key]->photo_url = asset('public/assets/images/quote-responses/' . $value->photo);
                }
                if ($value->document) {
                    $quote_result[$key]->document_url = asset('public/assets/documents/quote-responses/' . $value->document);
                }

                if (($address_visible == 'registered' && $value->register_by_self) || ($address_visible == 'non_registered' && $value->register_by_self == 0) || ($address_visible == 'all')) {
                    $quote_result[$key]->company_address = $value->company_address;
                } else {
                    $quote_result[$key]->company_address = '';
                }

                if (($email_visible == 'registered' && $value->register_by_self) || ($email_visible == 'non_registered' && $value->register_by_self == 0) || ($email_visible == 'all')) {
                    $quote_result[$key]->company_email = $value->company_email;
                    $quote_result[$key]->email = $value->email;
                } else {
                    $quote_result[$key]->company_email = '';
                    $quote_result[$key]->email = '';
                }

                if (($phone_number_visible == 'registered' && $value->register_by_self) || ($phone_number_visible == 'non_registered' && $value->register_by_self == 0) || ($phone_number_visible == 'all')) {
                    $quote_result[$key]->mobile = $value->mobile;
                    $quote_result[$key]->company_phone = $value->company_phone;
                } else {
                    $quote_result[$key]->mobile = '';
                    $quote_result[$key]->company_phone = '';
                }
            }
        }
        $response['error'] = 0;
        $response['quote_id'] = $quote_id;
        $response['address_visible'] = $address_visible;
        $response['email_visible'] = $email_visible;
        $response['phone_number_visible'] = $phone_number_visible;
        $response['hide_all'] = $hide_all;
        $response['vendors_count_sent'] = $vendors_count_sent >= 200 ? 'more than 200' : $vendors_count_sent;
        $response['vendors_count_responded'] = $vendors_count_responded;
        $response['total_record_count'] = $record_count;
        $response['quoteresponses'] = $quote_result;
        $response['quotedata'] = $quotedata;
        echo json_encode($response);
    }

    /**
     * Display the Quote Responses.
     *
     * @return Response
     */
    public function quoteResponses(Request $request)
    {
        //$userdata = Auth::user();
        $quote_id = $request->quote_id;
        $user_id = $request->user_id;
        $quotedata = Quotes::where('id', '=', $quote_id)->first();

        $quoteresponses = VendorQuote::leftjoin('vendors', 'vendors.id', '=', 'vendor_quotes.vendor_id')
            ->select('vendor_quotes.id as vendor_quote_id', 'vendor_quotes.created_at as response_created_at', 'vendor_quotes.expiry_date', 'vendor_quotes.isResponded', 'vendor_quotes.price', 'vendors.*')
            ->where('user_id', '=', $user_id)
            ->where('quote_id', '=', $quote_id)
            ->where('isResponded', '=', 1)
            ->orderBy('vendor_quotes.id', 'DESC')
            ->get();

        $vendors_count_sent = DB::table('vendor_quotes')->select("count('vendor_quotes.id')")->where('vendor_quotes.quote_id', '=', $quote_id)->groupBy("vendor_quotes.quote_id")->count();
        $vendors_count_responded = DB::table('vendor_quotes')->select("count('vendor_quotes.id')")->where('vendor_quotes.quote_id', '=', $quote_id)->where('vendor_quotes.isResponded', '=', 1)->groupBy("vendor_quotes.quote_id")->count();

        $response['error'] = 0;
        $response['quote_id'] = $quote_id;
        $response['quoteresponses'] = $quoteresponses;
        $response['quotedata'] = $quotedata;
        $response['vendors_count_sent'] = $vendors_count_sent;
        $response['vendors_count_responded'] = $vendors_count_responded;
        echo json_encode($response);
        //return view("customer::quote-responses",['quote_id'=>$quote_id,'quoteresponses'=>$quoteresponses,'quotedata'=>$quotedata,'vendors_count_sent'=>$vendors_count_sent,'vendors_count_responded'=>$vendors_count_responded]);
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

    public function view_sent_quote1($vendor_quote_id, Request $request)
    {
        $vendorquote = VendorQuote::join('vendors', 'vendors.id', '=', 'vendor_quotes.vendor_id')
            ->join('quotes', 'quotes.id', '=', 'vendor_quotes.quote_id')
            ->select('vendors.*', 'vendor_quotes.*', 'quotes.*', 'vendors.is_privacy as vendor_is_privacy', 'vendor_quotes.id as vendor_quote_id', 'vendor_quotes.created_at as vendorquote_created_at', 'quotes.created_at as quote_created_at')
            ->where('vendor_quotes.id', $vendor_quote_id)
            ->first();
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
    public function view_sent_quote(Request $request)
    {
        $vendor_quote_id = $request->quote_id;
        $vendorquote = VendorQuote::join('vendors', 'vendors.id', '=', 'vendor_quotes.vendor_id')->join('quotes', 'quotes.id', '=', 'vendor_quotes.quote_id')->select('vendors.*', 'vendor_quotes.*', 'quotes.*', 'vendors.is_privacy as vendor_is_privacy', 'vendor_quotes.id as vendor_quote_id', 'vendor_quotes.created_at as vendorquote_created_at', 'quotes.created_at as quote_created_at')->where('vendor_quotes.id', $vendor_quote_id)->first();
        $quote_min_price = DB::table('vendor_quotes')->where('vendor_quotes.id', '=', $vendor_quote_id)->groupBy("vendor_quotes.quote_id")->min('vendor_quotes.price');
        $quote_max_price = DB::table('vendor_quotes')->where('vendor_quotes.id', '=', $vendor_quote_id)->groupBy("vendor_quotes.quote_id")->max('vendor_quotes.price');

        $vendorquote_products = array();
        $vendor_quote_products_count = VendorQuoteProducts::where('vendor_quote_id', $vendor_quote_id)->count();
        if ($vendor_quote_products_count > 0) {
            $vendorquote_products = VendorQuoteProducts::where('vendor_quote_id', $vendor_quote_id)->get();
        }
        $vendorquote['photo_url'] = $vendorquote['document_url'] = asset('public/assets/images/default.jpeg');
        if ($vendorquote['photo'] != '') {
            $vendorquote['photo_url'] = asset('public/assets/images/quote-responses/' . $vendorquote['photo']);
        }
        if ($vendorquote['document'] != '') {
            $vendorquote['document_url'] = asset('public/assets/images/quote-responses/' . $vendorquote['document']);
        }

        foreach ($vendorquote_products as $key => $value) {
            $vendorquote_products[$key] = $value;
            $vendorquote_products[$key]['product_file_url'] = asset('public/assets/images/default.jpeg');
            if ($value['product_file'] != '') {
                $vendorquote_products[$key]['product_file_url'] = asset('public/assets/images/quote-responses/' . $value['product_file']);
            }
        }

        $response['error'] = 0;
        $response['vendorquote'] = $vendorquote;
        $response['vendorquote_products'] = $vendorquote_products;
        $response['vendor_quote_products_count'] = $vendor_quote_products_count;
        $response['quote_min_price'] = $quote_min_price;
        $response['quote_max_price'] = $quote_max_price;
        echo json_encode($response);
        //dd(compact('vendorquote','vendorquote_products','vendor_quote_products_count','quote_min_price','quote_max_price'));
        //return view('customer::view-send-quote-response', compact('vendorquote','vendorquote_products','vendor_quote_products_count','quote_min_price','quote_max_price'));
    }

    public function customer_profile(Request $request)
    {
        $cntUserInfo = array();
        if (is_numeric($request->search_value)) {
            $cntUserInfo = User::where('mobile', $request->search_value)->get();
        } else {
            $cntUserInfo = User::where('email', $request->search_value)->get();
        }

        $response['error'] = 1;
        $response['message'] = "Profile Not Found..!";
        $response['customer_data'] = $cntUserInfo;
        if (count($cntUserInfo)) {
            $response['error'] = 0;
            $response['message'] = "Profile Found..!";
            $response['customer_data'] = $cntUserInfo;
        }
        echo json_encode($response);
    }
}