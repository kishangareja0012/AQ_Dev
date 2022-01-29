<?php
namespace App\Http\Controllers;

use App\Blog;
use App\Category;
use App\Http\Requests;
use App\Mail\ContactUs;
use App\MSG91;
use App\Notification;
use App\Quotes;
use App\Tag;
use App\User;
use App\Vendor;
use App\VendorQuote;
use App\Words;
use Auth;
use DB;
use File;
use Hash;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Session;
use Mail;

class UserController extends Controller
{

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = 'customer/quote-requests';

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

    private $API_KEY = '265055AdgWc9mN8W0r5c766da0';
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
        $sConfig = DB::table('settings')->first();
        $send_quote_location = $sConfig->quote_location;
        $sCustInfoVisible = $sConfig->customer_info_visible;
        $sms_send = $sConfig->sms_send;
        $this->sUserEmail = $request->email;
        $this->sUserMobile = $request->mobile;

        //validate data
        $this->validate($request, [
            'quote_title' => 'required',
            'mobile' => 'required', //required|numeric|unique:users',
        ]);

        $input = $request->all();
        if ($input['request_type'] == 'Number') {
            //Verify OTP
            $cust_otp = $input['cust_otp1'] . $input['cust_otp2'] . $input['cust_otp3'] . $input['cust_otp4'];
            $response = $this->verifyUserOtp($input['mobile'], $cust_otp);
            if (isset($response['error']) && ($response['error'] == 1)) {
                return redirect('/')->withErrors([$response['message']]);
            }
            //exit('after otp verified');
        }

        $categoryData = $this->submitFindCategory($input['quote_title']);

        if (isset($categoryData['status']) && $categoryData['status'] == 'error') {
            return redirect('/')->withErrors([$categoryData['message']]);
        }

        if ($input['request_type'] == 'Email') {
            $sResult = $user = User::where('email', $input['mobile'])->first();
        } else {
            $sResult = $user = User::where('mobile', $input['mobile'])->first();
        }

        $category = implode(",", $categoryData['data']['categories']);
        //$categories = $categoryData['data']['categories'];
        //echo "<pre>"; print_r($categories); exit;

        if (!isset($user['mobile']) && $input['request_type'] == 'Number') {
            //create user
            $user = new User();
            $user->name = isset($input['name']) ? $input['name'] : $input['mobile'];
            $user->password = Hash::make('123456'); //$input['password'];
            $user->email = isset($input['email']) ? $input['email'] : $input['mobile'];
            $user->mobile = $input['mobile'];
            $user->login_method = "Number";
            $user->isVerified = 1;
            $userdata = $user;
            $sResult = $user->save();
        }

        // dd($user);
        if (!isset($user['email']) && $input['request_type'] == 'Email') {
            $user = new User();
            $user->name = '';
            $user->password = Hash::make('123456'); //$input['password'];
            $user->email = isset($input['mobile']) ? $input['mobile'] : $input['mobile'];
            $user->mobile = '';
            $user->login_method = "Email";
            $user->isVerified = 1;
            $userdata = $user;
            $sResult = $user->save();
        }

        if ($sResult) {
            $sUserId = $user->id;
            Auth::loginUsingId($user->id);
            Session::put('USERID', $user->id);
            Session::push('user_data', $user);
            $categories = $categoryData['data']['categories'];
            if (count($categories) == 0) {
                $sCategory = "Miscellaneous";
                $aResult = Category::where('category_name', $sCategory)->first();
                //echo "CatID:". $aResult->id;die();
                $category = $aResult->id;
            } else {
                $category = implode(",", $categoryData['data']['categories']);
            }
            //echo "Category".$category;die();
            // file upload
            $filename = "";
            if ($request->hasFile('quote_myfile')) {
                $image = $request->quote_myfile;
                $path = public_path('../public/assets/images/quotes/');
                $filename = time() . '.' . $image->getClientOriginalExtension();
                $image->move($path, $filename);
            }

            $categories = $categoryData['data']['categories'];

            $catsquery = "";
            foreach ($categories as $num => $category) {
                if ($num + 1 == count($categories)) {
                    $catsquery .= "FIND_IN_SET('" . $category . "', category) ";
                } else {
                    $catsquery .= "FIND_IN_SET('" . $category . "', category) or ";
                }
            }
            //Case 2 : If user selected category is not available in System Category finder array
            if ($request->category != '' && $request->category != '0' && $catsquery == '') {
                $catsquery .= " FIND_IN_SET('" . $request->category . "', category) ";
            }
            // dd($catsquery);

            $quote_location = explode(',', $input['quote_location']);
            $location1 = isset($quote_location[0]) ? trim($quote_location[0]) : '';
            $location2 = isset($quote_location[1]) ? trim($quote_location[1]) : '';
            $quote_location = isset($quote_location[1]) ? $quote_location[1] : $input['quote_location'];

            if ($catsquery) {
                if ($send_quote_location == 'city') {
                    $query = "SELECT * FROM vendors WHERE (" . $catsquery . ") AND company_city LIKE '%" . $quote_location . "%'";
                } else if ($send_quote_location == 'location') {
                    $query = "SELECT * FROM vendors WHERE (" . $catsquery . ") AND company_address REGEXP '(^|[[:space:]])" . $location1 . "([[:space:]]|$)'";
                } else {
                    $query = "SELECT * FROM vendors WHERE (" . $catsquery . ") AND (company_city LIKE '%" . $location1 . "%' OR  company_address LIKE '%" . $location1 . "%')";
                }
            } else {
                if ($send_quote_location == 'city') {
                    $query = "SELECT * FROM vendors WHERE company_city LIKE '%" . $quote_location . "%'";
                } else if ($send_quote_location == 'location') {
                    $query = "SELECT * FROM vendors WHERE company_address REGEXP '(^|[[:space:]])" . $location1 . "([[:space:]]|$)'";
                } else {
                    $query = "SELECT * FROM vendors WHERE (company_city LIKE '%" . $location1 . "%' OR  company_address LIKE '%" . $location1 . "%')";
                }

            }

            $aVendorsInfo = DB::select(DB::raw($query));
            if (count($aVendorsInfo) <= 0) {
                $sCategory = "Miscellaneous";
                $aResult = Category::where('category_name', $sCategory)->first();
                //echo "CatID:". $aResult->id;die();
                $category = $aResult->id;
            }

            //create quote
            $quote = Quotes::create([
                'user_id' => $sUserId,
                'item' => $input['quote_title'],
                'item_description' => $input['quote_description'],
                'location' => $input['quote_location'],
                'category' => $category,
                'is_privacy' => isset($input['privacy']) ? 0 : 1,
                'item_sample' => $filename,
                'status' => 'Quote Raised',
            ]);

            if (isset($categoryData) && $categoryData['status'] == 'success' && ($request->category == '' || $request->category == '0') && (!isset($categoryData['data']['categories']) || (count($categoryData['data']['categories']) == 0))) {
                // return redirect('/')->with('flash_message', 'We got your quote request. Our experts will work with vendors to get you best price.');
                Session::put('vendor_not_found', 'yes');
                return redirect()->intended(url('customer/quote-sent-vendors/' . $quote->id))->with('flash_message', 'Your quote has been successfully posted.');
            }

            if ($quote) {

                //Send Email Notification to customer
                $data = array('name' => $user->name, 'email' => $user->email);

                $sQuoteId = $quote->id;
                if (!empty($quote_photo)) {
                    $path = asset('public/assets/images/quotes/' . $quote_photo);
                } else {
                    // $path = asset('public/assets/images/default.jpeg');
                    $path = '';
                }
                $json_quote = json_encode([
                    'user_id' => $sUserId,
                    'quote_id' => $sQuoteId,
                    'item' => isset($input['quote_title']) ? $input['quote_title'] : '',
                    'item_description' => isset($input['quote_description']) ? $input['quote_description'] : '',
                    'location' => isset($input['quote_location']) ? $input['quote_location'] : '',
                    'item_sample' => $path,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

                //Send SMS Notification to customer
                $sQuoteId = $quote->id;
                $sUserType = "Customer";
                $sConfig = DB::table('settings')->first();
                $send_quote_location = $sConfig->quote_location;
                $sCustInfoVisible = $sConfig->customer_info_visible;
                $sms_send = $sConfig->sms_send;
                $this->sUserEmail = $request->email;
                $this->sUserMobile = $request->mobile;

                $MSG91 = new MSG91();
                $Notification = new Notification();

                $categories = $categoryData['data']['categories'];
                $categories = array_values($categories);
                //print_r($categories); exit;

                $catsquery = "";
                foreach ($categories as $num => $category) {
                    if ($num + 1 == count($categories)) {
                        $catsquery .= "FIND_IN_SET('" . $category . "', category) ";
                    } else {
                        $catsquery .= "FIND_IN_SET('" . $category . "', category) or ";
                    }
                }

                //Case 2 : If user selected category is not available in System Category finder array
                if ($request->category != '' && $request->category != '0' && $catsquery == '') {
                    $catsquery .= " FIND_IN_SET('" . $request->category . "', category) ";
                }
                // dd($catsquery);

                $quote_location = $circle_location = explode(',', $input['quote_location']);
                $location1 = isset($quote_location[0]) ? trim($quote_location[0]) : '';
                $location2 = isset($quote_location[1]) ? trim($quote_location[1]) : '';
                $quote_location = isset($quote_location[1]) ? $quote_location[1] : $input['quote_location'];
                if ($catsquery) {
                    if ($send_quote_location == 'city') {
                        $query = "SELECT * FROM vendors WHERE (" . $catsquery . ") AND company_city LIKE '%" . $quote_location . "%'";
                    } else if ($send_quote_location == 'location') {
                        $query = "SELECT * FROM vendors WHERE (" . $catsquery . ") AND company_address REGEXP '(^|[[:space:]])" . $location1 . "([[:space:]]|$)'";
                    } else {

                        /*circle code*/
                        if (count($circle_location) >= 2) {
                            $location = DB::table('locations')->where('location', 'like', $location1)->where('city', 'like', '%' . $location2 . '%')->first();
                        } else {
                            $location = DB::table('locations')->where('location', 'like', $location1)->first();
                        }

                        if ($location == null) {
                            $query = "SELECT * FROM vendors WHERE (" . $catsquery . ") AND company_address LIKE '%" . $quote_location . "%'";
                        } else {

                            $latitude = $location->latitude;
                            $longitude = $location->longitude;
                            $location2 = $location->city;

                            if ($latitude == '' || $longitude == '') {
                                $query = "SELECT * FROM vendors WHERE (" . $catsquery . ") AND company_address LIKE '%" . $quote_location . "%'";
                            } else {
                                $locations_query = "SELECT location as location FROM locations l WHERE ACOS((SIN($latitude / 57.29577951) * SIN(l.latitude / 57.29577951)) + (COS($latitude / 57.29577951) * COS(l.latitude / 57.29577951) * COS(l.longitude / 57.29577951 - $longitude / 57.29577951)))*6371 < 8";
                                $result = DB::select(DB::raw($locations_query));
                                $where_clause = '';
                                $numItems = count($result);
                                $i = 0;
                                foreach ($result as $key => $value) {
                                    if (++$i === $numItems) {
                                        $where_clause .= 'company_address like "%' . $value->location . '%"';
                                    } else {
                                        $where_clause .= 'company_address like "%' . $value->location . '%" OR ';
                                    }
                                }
                                $query = "SELECT * FROM vendors WHERE (" . $catsquery . ") AND (" . $where_clause . ") AND company_city LIKE '%" . $location2 . "%'";
                            }
                        }
                        /*end circle code*/
                    }
                } else {
                    if ($send_quote_location == 'city') {
                        $query = "SELECT * FROM vendors WHERE company_city LIKE '%" . $quote_location . "%'";
                    } else if ($send_quote_location == 'location') {
                        $query = "SELECT * FROM vendors WHERE company_address REGEXP '(^|[[:space:]])" . $location1 . "([[:space:]]|$)'";
                    } else {

                        /*circle code*/
                        if (count($circle_location) >= 2) {
                            $location = DB::table('locations')->where('location', 'like', $location1)->where('city', 'like', '%' . $location2 . '%')->first();
                        } else {
                            $location = DB::table('locations')->where('location', 'like', $location1)->first();
                        }

                        if ($location == null) {
                            $query = "SELECT * FROM vendors WHERE (" . $catsquery . ") AND company_address LIKE '%" . $quote_location . "%'";
                        } else {

                            $latitude = $location->latitude;
                            $longitude = $location->longitude;
                            $location2 = $location->city;

                            if ($latitude == '' || $longitude == '') {
                                $query = "SELECT * FROM vendors WHERE (" . $catsquery . ") AND company_address LIKE '%" . $quote_location . "%'";
                            } else {
                                $locations_query = "SELECT location as location FROM locations l WHERE ACOS((SIN($latitude / 57.29577951) * SIN(l.latitude / 57.29577951)) + (COS($latitude / 57.29577951) * COS(l.latitude / 57.29577951) * COS(l.longitude / 57.29577951 - $longitude / 57.29577951)))*6371 < 8";
                                $result = DB::select(DB::raw($locations_query));
                                $where_clause = '';
                                $numItems = count($result);
                                $i = 0;
                                foreach ($result as $key => $value) {
                                    if (++$i === $numItems) {
                                        $where_clause .= 'company_address like "%' . $value->location . '%"';
                                    } else {
                                        $where_clause .= 'company_address like "%' . $value->location . '%" OR ';
                                    }
                                }
                                $query = "SELECT * FROM vendors WHERE (" . $catsquery . ") AND (" . $where_clause . ") AND company_city LIKE '%" . $location2 . "%'";
                            }
                        }
                        /*end circle code*/
                    }
                }
                // dd($query);
                $aVendorsInfo = DB::select(DB::raw($query));
                $vendors_sms_array = array();
                foreach ($aVendorsInfo as $vendor) {
                    $sUserType = "Vendor";
                    //$sStatus = "Quote Raised";
                    $sStatus = "New";
                    $vendor_quote_insert = VendorQuote::create(['user_id' => $sUserId, 'quote_id' => $sQuoteId, 'vendor_id' => $vendor->id, 'status' => $sStatus]);
                    //SMS code
                    $rand = rand(11111, 99999);
                    $app_link = 'https://anyquote.in/';
                    $short_url = array(
                        "full_url" => url('vendor/send-quote/' . $vendor_quote_insert->id . '/' . $vendor->id),
                        "short_code" => $rand,
                    );
                    $url = DB::table('short_url')->insert($short_url);
                    $url_id = DB::getPdo()->lastInsertId();
                    $message_body = "Hello " . $vendor->name . ",\n" . $user['name'] . " has requested quotation for " . strtoupper($input['quote_title']) . ".\n Quote Request ID: " . $sQuoteId . "\nPlease provide your quote here " . url('provide_price/' . $url_id) . " or download our app \n " . $app_link . ".\nYou can contact us +91 9885344485 for further queries.";
                    if ($vendor->device_token != '') {
                        $message_body = 'Price requested for ' . $input['quote_title'] . ' by AnyQuote customer in ' . $input['quote_location'] . '. Provide your price now.';
                        // $Notification->sendPushNotification('Anyquote Vendor', $message_body, 'vendor', $vendor->device_token, '', $vendor->device_type);
                        $Notification->sendPushNotification('Anyquote Vendor', $message_body, $vendor->device_token, '', $vendor->device_type, $json_quote);
                    }
                    $mobile_no = strlen($vendor->mobile) == 10 ? '91' . $vendor->mobile : $vendor->mobile;
                    $vendors_sms_array[] = array(
                        "mobiles" => $mobile_no,
                        "vendorName" => $vendor->name,
                        "userName" => "AnyQuote Customer",
                        "quoteTitle" => strtoupper($input['quote_title']),
                        "responseLink" => url('provide_price/' . $url_id),
                    );
                    // $msg91Response = $MSG91->sendQuoteSMS($this->sUserEmail, $this->sUserMobile, $vendor->email, $vendor->mobile, $sConfig, $sUserType,$message_body);
                    //end SMS code
                }
                if ($sms_send == "Yes") {
                    $msg91Response = $MSG91->sendQuoteSMS(json_encode($vendors_sms_array));
                }

                // Set Auth Details
                //Auth::login($user);
                Auth::loginUsingId($user->id);
                Session::put('USERID', $user->id);
                Session::push('user_data', $user);

                if (count($aVendorsInfo) <= 0) {
                    Session::put('vendor_not_found', 'yes');
                    return redirect()->intended(url('customer/quote-sent-vendors/' . $sQuoteId))->with('flash_message', 'Your quote has been successfully posted.');
                } else {
                    Session::forget('vendor_not_found');
                }
                // return redirect()->intended(url('customer/quote-sent-vendors/'.$sQuoteId))->with('flash_message', 'Your quote has been successfully posted.');
            }
            return redirect()->intended(url('customer/quote-sent-vendors/' . $sQuoteId))->with('flash_message', 'Your quote has been successfully posted.');
        }
        return redirect()->intended(url('/'))->with('flash_message', 'Your quote has been successfully posted. Login to check your quotes and responses.');
    }

    public function postQuote(Request $request)
    {
        ini_set('memory_limit', '-1');
        if (Auth::guest()) {
            return redirect('/')->withErrors(["Invalid"]);
        }
        $input = $request->all();
        $sConfig = DB::table('settings')->first();
        $send_quote_location = $sConfig->quote_location;
        $sCustInfoVisible = $sConfig->customer_info_visible;
        $sms_send = $sConfig->sms_send;
        $this->sUserEmail = $request->email;
        $this->sUserMobile = $request->mobile;

        //validate data
        $this->validate($request, [
            'quote_title' => 'required',
        ]);

        $user = Auth::user();
        $sUserId = $user->id;

        if (isset($request->raised_by) && $request->raised_by == 'admin') {
            $user = User::where('email', $request->mobile)->orWhere('mobile', $request->mobile)->first();
            $sUserId = $user['id'];
            if (!$user) {
                return redirect()->intended(url('admin/raised_quote'))->with('flash_error_message', 'Customer not found.');
            }
        }

        $categoryData = $this->submitFindCategory($input['quote_title']);
        if (isset($categoryData['status']) && $categoryData['status'] == 'error') {
            return redirect('/')->withErrors([$categoryData['message']]);
        }
        // file upload
        $filename = "";
        if ($request->hasFile('quote_myfile')) {
            $image = $request->quote_myfile;
            $path = public_path('../public/assets/images/quotes/');
            $filename = time() . '.' . $image->getClientOriginalExtension();
            $image->move($path, $filename);
        }
        $category = implode(",", $categoryData['data']['categories']);

        $categories = $categoryData['data']['categories'];
        $categories = array_values($categories);
        $catsquery = "";
        foreach ($categories as $num => $category) {
            if ($num + 1 == count($categories)) {
                $catsquery .= "FIND_IN_SET('" . $category . "', category) ";
            } else {
                $catsquery .= "FIND_IN_SET('" . $category . "', category) or ";
            }
        }
        // dd($catsquery);
        //Case 2 : If user selected category is not available in System Category finder array
        if ($request->category != '' && $request->category != '0' && $catsquery == '') {
            $catsquery .= " FIND_IN_SET('" . $request->category . "', category) ";
        }
        $quote_location = $circle_location = explode(',', $input['quote_location']);
        $location1 = isset($quote_location[0]) ? trim($quote_location[0]) : '';
        $location2 = isset($quote_location[1]) ? trim($quote_location[1]) : '';
        $quote_location = isset($quote_location[1]) ? $quote_location[1] : $input['quote_location'];
        // dd($quote_location);
        if ($catsquery) {
            if ($send_quote_location == 'city') {
                $query = "SELECT * FROM vendors WHERE (" . $catsquery . ") AND company_city LIKE '%" . $quote_location . "%'";
            } else if ($send_quote_location == 'location') {
                $query = "SELECT * FROM vendors WHERE (" . $catsquery . ") AND company_address REGEXP '(^|[[:space:]])" . $location1 . "([[:space:]]|$)'";
            } else {
                $query = "SELECT * FROM vendors WHERE (" . $catsquery . ") AND (company_city LIKE '%" . $location1 . "%' OR  company_address LIKE '%" . $location1 . "%')";
            }
        } else {
            if ($send_quote_location == 'city') {
                $query = "SELECT * FROM vendors WHERE company_city LIKE '%" . $quote_location . "%'";
            } else if ($send_quote_location == 'location') {
                $query = "SELECT * FROM vendors WHERE company_address REGEXP '(^|[[:space:]])" . $location1 . "([[:space:]]|$)'";
            } else {
                $query = "SELECT * FROM vendors WHERE (company_city LIKE '%" . $location1 . "%' OR  company_address LIKE '%" . $location1 . "%')";
            }
        }
        $aVendorsInfo = DB::select(DB::raw($query));
        // dd($aVendorsInfo);
        if (count($aVendorsInfo) <= 0 || $catsquery == '') {
            $sCategory = "Miscellaneous";
            $aResult = Category::where('category_name', $sCategory)->first();
            $category = $aResult->id;
        }

        //create quote
        $quote = Quotes::create([
            'user_id' => $sUserId,
            'item' => $input['quote_title'],
            'item_description' => $input['quote_description'],
            'location' => $input['quote_location'],
            'category' => $category,
            'is_privacy' => isset($input['privacy']) ? 0 : 1,
            'item_sample' => $filename,
            'status' => 'Quote Raised',
        ]);

        if (isset($categoryData) && $categoryData['status'] == 'success' && ($request->category == '' || $request->category == '0') && (!isset($categoryData['data']['categories']) || (count($categoryData['data']['categories']) == 0))) {
            // return redirect('/')->with('flash_message', 'We got your quote request. Our experts will work with vendors to get you best price.');
            Session::put('vendor_not_found', 'yes');
            return redirect()->intended(url('customer/quote-sent-vendors/' . $quote->id))->with('flash_message', 'Your quote has been successfully posted.');
        }
        if ($quote) {
            $this->email = $user->email;

            //Send SMS Notification to customer
            $sQuoteId = $quote->id;
            if (!empty($quote_photo)) {
                $path = asset('public/assets/images/quotes/' . $quote_photo);
            } else {
                $path = '';
                // $path = asset('public/assets/images/default.jpeg');
            }
            $json_quote = json_encode([
                'user_id' => $sUserId,
                'quote_id' => $sQuoteId,
                'item' => isset($input['quote_title']) ? $input['quote_title'] : '',
                'item_description' => isset($input['quote_description']) ? $input['quote_description'] : '',
                'location' => isset($input['quote_location']) ? $input['quote_location'] : '',
                'item_sample' => $path,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            $sUserType = "Customer";
            // $this->sUserEmail = $user->email;
            // $this->sUserMobile = $user->mobile;
            $MSG91 = new MSG91();
            $Notification = new Notification();

            /* $categories = $categoryData['data']['categories'];
            $categories = array_values($categories); */
            $catsquery = "";
            foreach ($categories as $num => $category) {
                if ($num + 1 == count($categories)) {
                    $catsquery .= "FIND_IN_SET('" . $category . "', category) ";
                } else {
                    $catsquery .= "FIND_IN_SET('" . $category . "', category) or ";
                }
            }

            //Case 2 : If user selected category is not available in System Category finder array
            if ($request->category != '' && $request->category != '0' && $catsquery == '') {
                $catsquery .= " FIND_IN_SET('" . $request->category . "', category) ";
            }

            /*  $quote_location = $circle_location = explode(',', $input['quote_location']);
            $location1 = isset($quote_location[0]) ? trim($quote_location[0]) : '';
            $location2 = isset($quote_location[1]) ? trim($quote_location[1]) : '';
            $quote_location = isset($quote_location[1]) ? $quote_location[1] : $input['quote_location']; */
            if ($catsquery) {
                if ($send_quote_location == 'city') {
                    $query = "SELECT * FROM vendors WHERE (" . $catsquery . ") AND company_city LIKE '%" . $quote_location . "%'";
                } else if ($send_quote_location == 'location') {
                    $query = "SELECT * FROM vendors WHERE (" . $catsquery . ") AND company_address REGEXP '(^|[[:space:]])" . $location1 . "([[:space:]]|$)'";
                } else {

                    /*circle code*/
                    if (count($circle_location) >= 2) {
                        $location = DB::table('locations')->where('location', 'like', $location1)->where('city', 'like', '%' . $location2 . '%')->first();
                    } else {
                        $location = DB::table('locations')->where('location', 'like', $location1)->first();
                    }

                    if ($location == null) {
                        $query = "SELECT * FROM vendors WHERE (" . $catsquery . ") AND company_address LIKE '%" . $quote_location . "%'";
                    } else {

                        $latitude = $location->latitude;
                        $longitude = $location->longitude;
                        $location2 = $location->city;
                        if ($latitude == '' || $longitude == '') {
                            $query = "SELECT * FROM vendors WHERE (" . $catsquery . ") AND company_address LIKE '%" . $quote_location . "%'";
                        } else {

                            /*$where_clause = "SELECT CONCAT('company_address is null ',GROUP_CONCAT(circle SEPARATOR' ')) AS where_clause FROM locations l WHERE ACOS((SIN(17.5197 / 57.29577951) * SIN(l.latitude / 57.29577951)) + (COS(17.5197 / 57.29577951) * COS(l.latitude / 57.29577951) * COS(l.longitude / 57.29577951 - 78.3779 / 57.29577951)))*6371 < 8";
                            $locations_result = DB::select(DB::raw($where_clause));*/
                            $locations_query = "SELECT location as location FROM locations l WHERE ACOS((SIN($latitude / 57.29577951) * SIN(l.latitude / 57.29577951)) + (COS($latitude / 57.29577951) * COS(l.latitude / 57.29577951) * COS(l.longitude / 57.29577951 - $longitude / 57.29577951)))*6371 < 8";
                            $result = DB::select(DB::raw($locations_query));
                            $where_clause = '';
                            $numItems = count($result);
                            $i = 0;
                            foreach ($result as $key => $value) {
                                if (++$i === $numItems) {
                                    $where_clause .= 'company_address like "%' . $value->location . '%"';
                                } else {
                                    $where_clause .= 'company_address like "%' . $value->location . '%" OR ';
                                }
                            }
                            $query = "SELECT * FROM vendors WHERE (" . $catsquery . ") AND (" . $where_clause . ") AND company_city LIKE '%" . $location2 . "%'";
                        }
                    }
                    /*end circle code*/
                }
            } else {
                if ($send_quote_location == 'city') {
                    $query = "SELECT * FROM vendors WHERE company_city LIKE '%" . $quote_location . "%'";
                } else if ($send_quote_location == 'location') {
                    $query = "SELECT * FROM vendors WHERE company_address REGEXP '(^|[[:space:]])" . $location1 . "([[:space:]]|$)'";
                } else {

                    /*circle code*/
                    if (count($circle_location) >= 2) {
                        $location = DB::table('locations')->where('location', 'like', $location1)->where('city', 'like', '%' . $location2 . '%')->first();
                    } else {
                        $location = DB::table('locations')->where('location', 'like', $location1)->first();
                    }

                    if ($location == null) {
                        $query = "SELECT * FROM vendors WHERE (" . $catsquery . ") AND company_address LIKE '%" . $quote_location . "%'";
                    } else {
                        $latitude = $location->latitude;
                        $longitude = $location->longitude;
                        $location2 = $location->city;
                        if ($latitude == '' || $longitude == '') {
                            $query = "SELECT * FROM vendors WHERE (" . $catsquery . ") AND company_address LIKE '%" . $quote_location . "%'";
                        } else {
                            /*$where_clause = "SELECT CONCAT('company_address is null ',GROUP_CONCAT(circle SEPARATOR' ')) AS where_clause FROM locations l WHERE ACOS((SIN(17.5197 / 57.29577951) * SIN(l.latitude / 57.29577951)) + (COS(17.5197 / 57.29577951) * COS(l.latitude / 57.29577951) * COS(l.longitude / 57.29577951 - 78.3779 / 57.29577951)))*6371 < 8";
                            $locations_result = DB::select(DB::raw($where_clause));*/
                            $locations_query = "SELECT location as location FROM locations l WHERE ACOS((SIN($latitude / 57.29577951) * SIN(l.latitude / 57.29577951)) + (COS($latitude / 57.29577951) * COS(l.latitude / 57.29577951) * COS(l.longitude / 57.29577951 - $longitude / 57.29577951)))*6371 < 8";
                            $result = DB::select(DB::raw($locations_query));
                            $where_clause = '';
                            $numItems = count($result);
                            $i = 0;
                            foreach ($result as $key => $value) {
                                if (++$i === $numItems) {
                                    $where_clause .= 'company_address like "%' . $value->location . '%"';
                                } else {
                                    $where_clause .= 'company_address like "%' . $value->location . '%" OR ';
                                }
                            }
                            $query = "SELECT * FROM vendors WHERE (" . $catsquery . ") AND (" . $where_clause . ") AND company_city LIKE '%" . $location2 . "%'";
                        }
                    }
                    /*end circle code*/
                }
            }
            $aVendorsInfo = DB::select(DB::raw($query));

            $vendors_sms_array = array();
            foreach ($aVendorsInfo as $vendor) {
                $sUserType = "Vendor";
                //$sStatus = "Quote Raised";
                $sStatus = "New";
                $vendor_quote_insert = VendorQuote::create(['user_id' => $sUserId, 'quote_id' => $sQuoteId, 'vendor_id' => $vendor->id, 'status' => $sStatus]);
                // vendor message
                $rand = rand(12345, 99999);
                $app_link = 'https://anyquote.in/';
                $short_url = array(
                    "full_url" => url('vendor/send-quote/' . $vendor_quote_insert->id . '/' . $vendor->id),
                    "short_code" => $rand,
                );
                $url = DB::table('short_url')->insert($short_url);
                $url_id = DB::getPdo()->lastInsertId();
                $message_body = "Hello " . $vendor->name . ",\n" . $user['name'] . " has requested quotation for " . strtoupper($input['quote_title']) . ".\n\n Quote Request ID: " . $sQuoteId . "\n\nPlease provide your quote here " . url('provide_price/' . $url_id) . " or download our app \n " . $app_link . ".\n\nYou can contact us at 9160065454 for further queries.";
                if ($vendor->device_token != '') {
                    // send push to vendors
                    $message_body = 'Price requested for ' . $input['quote_title'] . ' by AnyQuote customer in ' . $input['quote_location'] . '. Provide your price now.';
                    $Notification->sendPushNotification('Anyquote Vendor', $message_body, $vendor->device_token, '', $vendor->device_type, $json_quote);
                }
                $mobile_no = strlen($vendor->mobile) == 10 ? '91' . $vendor->mobile : $vendor->mobile;
                $vendors_sms_array[] = array(
                    "mobiles" => $mobile_no,
                    "vendorName" => $vendor->name,
                    "userName" => "AnyQuote Customer",
                    "quoteTitle" => strtoupper($input['quote_title']),
                    "responseLink" => url('provide_price/' . $url_id),
                );
                // $msg91Response = $MSG91->sendQuoteSMS($this->sUserEmail, $this->sUserMobile, $vendor->email, $vendor->mobile, $sConfig, $sUserType,$message_body);
            }
            if ($sms_send == "Yes") {
                $msg91Response = $MSG91->sendQuoteSMS(json_encode($vendors_sms_array));
            }
            if (isset($request->raised_by) && $request->raised_by == 'admin') {
                return redirect()->intended(url('admin/raised_quote'))->with('flash_message', 'Your quote has been successfully posted.');
            }
            if (count($aVendorsInfo) <= 0) {
                Session::put('vendor_not_found', 'yes');
                return redirect()->intended(url('customer/quote-sent-vendors/' . $sQuoteId))->with('flash_message', 'Your quote has been successfully posted.');
            } else {
                Session::forget('vendor_not_found');
            }
        }
        return redirect()->intended(url('customer/quote-sent-vendors/' . $sQuoteId))->with('flash_message', 'Your quote has been successfully posted.');
    }

    /**
     * Find category for quote.
     */
    public function submitFindCategory($quote_item)
    {
        // Remove spaces from sentence
        $sentence = trim($quote_item);

        // Break sentence into words
        $sentence_words = explode(" ", $sentence);

        // Check for any empty words
        $sentence_words_better = array();
        foreach ($sentence_words as $word) {
            $word = strtolower(trim($word));
            if (!empty($word)) {
                $sentence_words_better[$word] = $word;
            }
        }

        // Check words fall under neutral(0), banned words(-1)
        // $checkwords = Words::whereIn('word',$sentence_words_better)->get();
        $checkwords = Words::where('word', 'LIKE', '%' . $sentence . '%')->get();
        // dd($checkwords);
        //print_r($checkwords);die();
        if (count($checkwords) > 0) {
            foreach ($checkwords as $checkword) {
                $key = strtolower($checkword->word);

                // If negative words found, redirect with message.
                if ($checkword->type == -1) {
                    //return redirect('/')->withErrors(['Oops! We found some blocked words']);
                    return array('status' => 'error', 'message' => 'We found some thing inappropriate. Our team will look in to the this. Thank you');
                }

                // Ignore neutral word. Remove from array.
                unset($sentence_words_better[$key]);
            }
        }

        // Words after removing neutral(0), banned words(-1)
        //echo "<h1>Entered Data</h1><pre>"; echo $sentence."<br>";
        //echo "<h1>Final Words</h1><pre>"; print_r($sentence_words_better);

        $category_wise_tags = array();
        $categories = array();
        // Check final words with category keywords

        if (count($sentence_words_better) > 0) {
            $final_words = array_values($sentence_words_better);
            $checktags = Tag::join('categories', 'tags.category_id', '=', 'categories.id')->whereIn('tag_name', $final_words)->select('tags.*', 'categories.category_name')->get();
            if (count($checktags) > 0) {
                foreach ($checktags as $checktag) {
                    $category = $checktag->category_name;
                    $categories[] = $checktag->category_id;
                    $category_wise_tags[$category][] = array('id' => $checktag->id, 'tag_name' => $checktag->tag_name, 'category_id' => $checktag->category_id, 'category_name' => $checktag->category_name);
                }
            }
        }
        //count values from array
        // dd($categories);
        $array_keys = array_count_values($categories);
        //get key of maximum values from array
        $maxs = array();
        if (count($array_keys)) {
            $maxs = array_keys($array_keys, max($array_keys));
        }

        // dd($maxs);
        // dd($category_wise_tags);

        if (count($categories) > 0) {
            $categories = array_unique($categories);
        }
        return array('status' => 'success', 'data' => array('categories' => $maxs));
        // return array('status'=>'success','data'=>array('categories'=>$categories));
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
    public function sendEmailOtp(Request $request)
    {

        $aData = Input::all();
        $data = array(
            'otp' => rand(1111, 9999),
            'email' => $aData['cust_email'],
        );

        Mail::send('emails.send_otp', $data, function ($message) use ($data) {
            $message->to($data['email'])->from($data['email'], '')->subject("AnyQuote Login OTP");
            $message->from('login@anyquote.in', 'From AnyQuote');
            // $message->from('info@interiorquotes.com','From AnyQuote');
        });

        $response['error'] = 0;
        $response['message'] = 'OTP sent to Mobile/Email.';
        $response['OTP'] = md5($data['otp']);
        $response['loggedIn'] = 1;
        echo json_encode($response);
    }

    /**
     * Sending the OTP.
     *
     * @return Response
     */
    public function sendOtp(Request $request)
    {
        $aData = Input::all();
        /* testing detail verify */
        if ($aData['custMobile'] == "8989582895" || $aData['custMobile'] == "918989582895" || $aData['custMobile'] == "9885344485") {
            $user = User::where('mobile', '918989582895')->orWhere('mobile', '9885344485')->first();
            $response['error'] = 0;
            $response['message'] = "OTP sent to Mobile/Email...!";
            $response['loggedIn'] = 1;
            return json_encode($response);
        }
        /* end testing detail verify */
        if ($aData['post_key'] == "Email") {
            // Mail::to($data['contact_email'])->send(new ContactUs($contacts));
            $data = array(
                'otp' => rand(1111, 9999),
                'email' => $aData['custMobile'],
            );

            Mail::send('emails.send_otp', $data, function ($message) use ($data) {
                $message->to($data['email'])->from($data['email'], '')->subject("AnyQuote Login OTP");
                $message->from('login@anyquote.in', 'From AnyQuote');
                // $message->from('info@interiorquotes.com','From AnyQuote');
            });

            $cntUserInfo = User::where('email', $request->custMobile)->count();
            if ($cntUserInfo <= 0) {
                $user = new User();
                $user->name = $request->custMobile;
                $user->password = Hash::make('123456'); //$input['password'];
                $user->email = $request->custMobile;
                $user->mobile = '';
                $user->login_method = 'Email';
                $userdata = $user;
                $sResult = $user->save();
            }

            $response['error'] = 0;
            $response['message'] = 'OTP sent to Mobile/Email.';
            $response['OTP'] = md5($data['otp']);
            $response['loggedIn'] = 1;
            echo json_encode($response);

        } else {

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
                $otp = rand(100000, 999999);
                $MSG91 = new MSG91();
                if (isset($aData['profile_type']) && $aData['profile_type'] == "customer_profile_otp") {
                    $msg91Response = $MSG91->sendOTP($otp, $sMobileNumber);
                } else {
                    $user = new User();
                    $user->name = $request->custMobile;
                    $user->password = Hash::make('123456'); //$input['password'];
                    $user->email = $request->custMobile;
                    $user->mobile = $request->custMobile;
                    $user->login_method = 'Number';
                    $userdata = $user;
                    $sResult = $user->save();
                    $msg91Response = $MSG91->sendOTP($otp, $sMobileNumber);
                }
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
            }
        }
    }

    /**
     * Function to verify OTP.
     *
     * @return Response
     */
    public function verifyOtp(Request $request)
    {
        $aData = $request->all();
        /* testing detail verify */
        if ($request->cust_otp1 == '' || $request->cust_otp2 == '' || $request->cust_otp3 == '' || $request->cust_otp4 == '') {
            return json_encode(array('success' => 0, 'message' => 'Please enter OTP'));
        }
        $customer_otp = $request->cust_otp1 . $request->cust_otp2 . $request->cust_otp3 . $request->cust_otp4;
        if (($request->customer_mobile == "8989582895" && $customer_otp == '1234') || ($request->customer_mobile == "918989582895" && $customer_otp == '1234') || ($request->customer_mobile == "9885344485" && $customer_otp == '1234')) {
            $user = User::where('mobile', '918989582895')->orWhere('mobile', '9885344485')->first();
            Auth::loginUsingId($user->id);
            Session::put('USERID', $user->id);
            Session::push('user_data', $user);
            $response['error'] = 0;
            $response['message'] = "Login Successful..!";
            $response['loggedIn'] = 1;
            return json_encode(array('success' => 1, 'message' => 'Login Successful..!'));
        }
        /* end testing detail verify */
        if ($aData['login_request_type'] == "Email") {

            if ($request->cust_otp1 == '' || $request->cust_otp2 == '' || $request->cust_otp3 == '' || $request->cust_otp4 == '') {
                return json_encode(array('success' => 0, 'message' => 'Please enter OTP'));
            }

            if ($request->customer_mobile == '') {
                return json_encode(array('success' => 0, 'message' => 'Email or Mobile is required..!'));
            }

            $sOTP = $request->cust_otp1 . '' . $request->cust_otp2 . '' . $request->cust_otp3 . '' . $request->cust_otp4;
            $enc_otp = md5($sOTP);
            if ($enc_otp == $request->cust_sent_login_otp) {
                // Updating user's status "isVerified" as 1.
                $bUpdateUser = User::where('email', $request->customer_mobile)->update(['isVerified' => 1]);
                // Get user record
                $user = DB::table('users')->where('email', $request->customer_mobile)->first();
                Auth::loginUsingId($user->id);
                Session::put('USERID', $user->id);
                Session::push('user_data', $user);
                return json_encode(array('success' => 1, 'message' => 'Login Successful..!'));
                // return redirect('customer/quote-requests')->with('flash_message', 'Login Successful.');
            } else {
                return json_encode(array('success' => 0, 'message' => 'OTP is not verified...!'));
                // return redirect('/')->with('flash_error_message', 'OTP is not verified.');
            }
        } else {

            if ($request->cust_otp1 == '' || $request->cust_otp2 == '' || $request->cust_otp3 == '' || $request->cust_otp4 == '') {
                return json_encode(array('success' => 0, 'message' => 'Please enter OTP'));
            }

            if ($request->customer_mobile == '') {
                return json_encode(array('success' => 0, 'message' => 'Email or Mobile is required..!'));
            }

            $sMobileNumber = "91" . $request->customer_mobile;
            $sOTP = $request->cust_otp1 . '' . $request->cust_otp2 . '' . $request->cust_otp3 . '' . $request->cust_otp4;
            $response = array();
            $MSG91 = new MSG91();
            $msg91Response = $MSG91->verifyOTP($sOTP, $sMobileNumber);

            $msg91Response = (array) json_decode($msg91Response, true);
            if ($msg91Response["message"] == "otp_verified" || $msg91Response["message"] == "already_verified") {
                $bUpdateUser = User::where('mobile', $request->customer_mobile)->update(['isVerified' => 1]);
                $response['error'] = 0;
                $response['isVerified'] = 1;
                $response['loggedIn'] = 1;
                $response['message'] = "Your Number is Verified.";
                // Get user record
                $user = DB::table('users')->where('mobile', $request->customer_mobile)->first();

                if (!isset($user->name)) {
                    //return redirect('/')->withErrors(['Invalid Login Credentials']);
                    $response['error'] = 1;
                    $response['isVerified'] = 0;
                    $response['loggedIn'] = 0;
                    $response['message'] = "Your Number is Verified.";
                    return json_encode(array('success' => 1, 'message' => 'Login Successful..!'));
                } else {

                    Auth::loginUsingId($user->id);
                    Session::put('USERID', $user->id);
                    Session::push('user_data', $user);
                    return json_encode(array('success' => 1, 'message' => 'Login Successful..!'));

                }
            } else {
                $response['error'] = 1;
                $response['isVerified'] = 0;
                $response['loggedIn'] = 0;
                $response['message'] = "OTP does not match.";
                return json_encode(array('success' => 0, 'message' => 'Error! OTP does not match..!'));
                // return redirect('/')->withErrors(['Error! OTP does not match.']);
            }
        }
    }

    /**
     * Function to verify Login OTP.
     *
     * @return Response
     */
    public function verifyLoginOtp(Request $request)
    {
        $aData = Input::all();
        $sMobileNumber = "91" . $request->customer_mobile;
        $sOTP = $request->cust_otp;
        //dd($sOTP);
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
            echo json_encode($response);
        } else {
            $response['error'] = 1;
            $response['isVerified'] = 0;
            $response['loggedIn'] = 0;
            $response['message'] = "OTP does not match.";
            echo json_encode($response);
            //return redirect('/')->with('error-login', 'Error! OTP does not match.');
        }
    }

    /**
     * Function to verify User OTP.
     *
     * @return Response
     */
    public function verifyUserOtp($customer_mobile, $customer_otp)
    {
        $aData = Input::all();
        $sMobileNumber = "91" . $customer_mobile;
        $sOTP = $customer_otp;
        $response = array();
        $MSG91 = new MSG91();
        //$msg91Response = $MSG91->verifyOTP($sOTP, $sMobileNumber);

        $msg91Response["message"] = "otp_verified";
        //echo "<pre>"; print_r($msg91Response); exit;

        //$msg91Response = (array) json_decode($msg91Response,true);
        //print_r($msg91Response); exit;

        $response = array('error' => 0, 'message' => '');
        $response['error'] = 0;

        if ($msg91Response["message"] != "otp_verified" && $msg91Response["message"] != "already_verified") {
            $response['error'] = 1;
            $response['message'] = "Error! OTP does not match.";
        }
        return $response;
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
                ->select('vendor_quotes.id as vendor_quote_id', 'vendor_quotes.created_at as response_created_at', 'vendors.*', 'quotes.item', 'quotes.item_description', 'quotes.created_at as quote_created_at')
                ->where('vendor_quotes.user_id', '=', $userdata->id)
                ->where('vendor_quotes.isResponded', '=', 1)
                ->orderBy('vendor_quotes.id', 'DESC')
                ->limit(10)
                ->get();

            return view("user/dashboard", ['quoterequests' => $quoterequests, 'quotes_count' => $quotes_count, 'quotes_sentresponses_count' => $quotes_sentresponses_count, 'quotes_responded_count' => $quotes_responded_count]);
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

        $quotes = DB::table('quotes')->leftjoin("categories", "quotes.category", "=", "categories.id")
            ->select("quotes.*", "categories.category_name")
            ->where('quotes.user_id', '=', $userdata->id)
            ->get();

        if (count($quotes) > 0) {
            foreach ($quotes as $key => $quote) {
                $cntquoteresponses = DB::table('vendor_quotes')->select("count('vendor_quotes.id')")->where('vendor_quotes.quote_id', '=', $quote->id)->where('vendor_quotes.isResponded', '=', 1)->groupBy("vendor_quotes.quote_id")->count();
                $quotes[$key]->count_click = $cntquoteresponses;
            }
        }
        return view("user/quote-requests", compact('quotes'));
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
            ->select('vendor_quotes.id as vendor_quote_id', 'vendor_quotes.created_at as response_created_at', 'vendor_quotes.quote_response', 'vendors.*')
            ->where('user_id', '=', $userdata->id)
            ->where('quote_id', '=', $quote_id)
            ->where('isResponded', '=', 1)
            ->orderBy('vendor_quotes.id', 'DESC')
            ->get();

        return view("user/quote-responses", ['quoteresponses' => $quoteresponses, 'quotedata' => $quotedata]);
    }

    public function createquote()
    {
        $categories = Category::pluck('category_name as name', 'id');
        return view('user/create-quote-request', compact('categories'));
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
            $msg91Response = $MSG91->sendSMS($userdata->email, $userdata->mobile, $vendor->email, $vendor->mobile, $sConfig, $sUserType);
        }

        return redirect('/quote-requests')->with('flash_message', 'Quote Created Successfully.');
    }

    public function editquote($quote_id)
    {
        $quote = Quotes::find($quote_id);
        $categories = Category::pluck('category_name as name', 'id');

        return view('user/edit-quote-request', compact('quote', 'categories'));
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
        $quote->save();

        return redirect('/edit-quote-request/' . $quote_id)->with('flash_message', 'Quote Details Updated Successfully.');
    }

    public function view_sent_quote($vendor_quote_id, Request $request)
    {
        $vendorquote = VendorQuote::join('vendors', 'vendors.id', '=', 'vendor_quotes.vendor_id')->join('quotes', 'quotes.id', '=', 'vendor_quotes.quote_id')->select('vendors.*', 'vendor_quotes.*', 'quotes.*', 'vendor_quotes.id as vendor_quote_id', 'vendor_quotes.created_at as vendorquote_created_at', 'quotes.created_at as quote_created_at')->where('vendor_quotes.id', $vendor_quote_id)->first();
        return view('user/view-send-quote-response', compact('vendorquote'));
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
            return view("user/profile", compact('userdata'));
        }
    }

    /**
     * Function to log out User
     * @return Response
     */
    public function checkApp()
    {
        // echo $status = File::deleteDirectory(public_path('../Modules'));  exit;
        return redirect('/');
    }

    /**
     * Function to log out User
     * @return Response
     */
    public function logout()
    {
        Auth::logout();
        Session::flush(); // removes all session data
        return redirect('/');
    }

    public function search_location(Request $request)
    {
        // ORDER BY `pincode` DESC
        $data = $request->all();
        $area = $data['query'];
        $area = $area['term'];
        $result = \DB::table('locations')->where('area', 'LIKE', "%{$area}%")->where('active', 'Y')->orderBy('pincode', 'DESC')->get();
        $countryResult = array();
        foreach ($result as $value) {
            $countryResult[] = $value->area . ' ,' . $value->city;
        }
        echo json_encode($countryResult);
    }

    public function search_cetegory(Request $request)
    {
        $data = [];
        if ($request->has('q')) {
            $search = $request->q;
            $data = Category::where('category_name', 'LIKE', "%$search%")->where('status', 'Active')->orderBy('category_name', 'ASC')->get();
        } else {
            $data = Category::where('status', 'Active')->orderBy('category_name', 'ASC')->get();
        }
        return response()->json($data);
    }

    public function search_city(Request $request)
    {
        $data = [];
        if ($request->has('q')) {
            $search = $request->q;
            $data = \DB::table('locations')->select('city')->where('city', 'LIKE', "{$search}%")->groupBy('city')->get();
        } else {
            $data = \DB::table('locations')->select('city')->groupBy('city')->get();
        }
        return response()->json($data);
    }

    public function get_contact()
    {
        $categories = Category::all();
        $locations = \DB::table('locations')->select('city')->groupBy('city')->get();
        return view('contact_us', compact("categories", "locations"));
    }

    public function submit_contact(Request $request)
    {
        $data = $request->all();
        //validate data
        $this->validate($request, [
            'contact_name' => 'required',
            'contact_email' => 'required',
            'contact_mobile' => 'required',
            'contact_message' => 'required',
        ]);

        $contacts = array(
            'name' => $data['contact_name'],
            'email' => $data['contact_email'],
            'mobile' => $data['contact_mobile'],
            'message' => $data['contact_message'],
            'created_at' => now(),
            'updated_at' => now(),
        );
        if (DB::table('contacts')->insert($contacts)) {
            Mail::to($data['contact_email'])->send(new ContactUs($contacts));
            return redirect($request->current_url)->with('flash_message', 'Contact Submit Successfully.');
        }
        return redirect($request->current_url)->with('flash_message', 'Something went wrong please try again.');
    }

    public function best_prices(Request $request)
    {
        $data = $request->all();
        $quote_text = isset($data['post_category']) ? $data['post_category'] : '';
        $categories = DB::select('select * from categories where status="Active" ORDER BY category_name ASC');
        $locations = DB::table('locations')->select('city')->groupBy('city')->get();
        $banner = DB::select('select * from city_banner where status="1" ');
        return view('best_prices', compact("categories", "locations", "banner", "quote_text"));
    }

    public function vendor_short_url($url_id)
    {
        $user = DB::table('short_url')->where('id', $url_id)->first();
        return redirect($user->full_url);
    }

    public function vendor_login($quote_id, $vendor_id, $token)
    {
        $quote_id = $quote_id;
        $quote_info = VendorQuote::where('id', $quote_id)->first();
        $user_info = User::where('id', $quote_info['user_id'])->first();
        $vendor_id = $vendor_id;
        $token = $token;
        $categories = Category::all();
        $locations = \DB::table('locations')->select('city')->groupBy('city')->get();
        $vendor_info = Vendor::where('id', $vendor_id)->first();
        $sMobileNumber = $vendor_info['mobile'];
        $register_by_self = $vendor_info['register_by_self'];
        return view('vendor_login', compact("categories", "locations", "sMobileNumber", "quote_id", "vendor_id", "register_by_self", "token", "user_info", "vendor_info"));
    }

    public function vendor_verify_otp(Request $request)
    {
        // dd($request->all());
        $vendor_info = Vendor::where('id', $request->vendor_id)->first();
        if ($vendor_info['register_by_self']) {
            $vendor_otp = $request->cust_otp1 . $request->cust_otp2 . $request->cust_otp3 . $request->cust_otp4;
            if ($request->otp_token == md5($vendor_otp)) {
                Session::put('VENDORID', $request->vendor_id);
                return redirect(url('vendor/send-quote/' . $request->quote_id));
            }
            return redirect(url('vendor_login/' . $request->quote_id . '/' . $request->vendor_id . '/' . $request->otp_token))->with('flash_error_message', 'OTP is not verified.');
        } else {
            return redirect(url('vendor_login/' . $request->quote_id . '/' . $request->vendor_id . '/' . $request->otp_token))->with('flash_error_message', 'Please Register.');
        }
    }
    public function get_blogs()
    {
        $categories = Category::where('category_name', '!=', 'Miscellaneous')->orderBy('category_name', 'ASC')->get();
        $locations = [];
        $blogs = Blog::select('blogs.*', 'categories.category_name')
            ->leftjoin("categories", "blogs.category_id", "=", "categories.id")->get();
        //dd($blogs);
        return view('blogs', compact("blogs", "categories", "locations"));
    }
    public function get_blogs_details(Request $request)
    {
        $categories = $locations = [];
        $blogs = Blog::select('blogs.*', 'categories.category_name')
            ->leftjoin("categories", "blogs.category_id", "=", "categories.id")->where('blogs.id', $request->id)->first();
        return view('blogs_details', compact("blogs", "categories", "locations"));
    }
    public function get_categorywise_blog(Request $request)
    {
        if ($request->category_id != 0) {
            $cat_info = Category::find($request->category_id);
            $this->data['category_name'] = $cat_info->category_name;
            $this->data['blogs'] = $blogs = Blog::select('blogs.*', 'categories.category_name')
                ->leftjoin("categories", "blogs.category_id", "=", "categories.id")->where('blogs.category_id', $request->category_id)->get();
        } else {
            $this->data['category_name'] = 'All';
            $this->data['blogs'] = $blogs = Blog::select('blogs.*', 'categories.category_name')
                ->leftjoin("categories", "blogs.category_id", "=", "categories.id")->get();
        }
        if (!empty($blogs)) {
            $returnHTML = view('c_blogs_details')->with($this->data)->render();
            //dd($returnHTML);
            return response()->json(array('success' => true, 'category_blog' => $returnHTML));
        }
    }

    public function userNotification(Request $request)
    {
        $count_responded = 100;
        $min_price = 100;
        $max_price = 500;
        $device_token = "c-MgwdspTGC-ccWlAND984:APA91bF4gTr_NqPo1MI0seRCFIXgePSQnSd46_xIj3o-pLW2wtMnd2BUZeDp_PcjrU-2FkhpEtlLSNA_xayPweO2rwu_g5VHjPXOI-vKAo-CMZQQFCu5OVq1MGkO9BNWYaWiyjV-eEuB";
        echo $this->sendPushNotification_old("Title1", "Dear user you got total " . $count_responded . " respond from vendor and minimu price is " . $min_price . " and maximum price is " . $max_price . ".", $device_token, '', '1');
        die;
        /* $message_array['custom_data']['message'] = "Dear user you got total " . $count_responded . " respond from vendor and minimu price is " . $min_price . " and maximum price is " . $max_price . ".";
        $message_array['custom_data']['notification_type'] = 1;
        $params = array("multiple"=>0,'device_type'=>'0','register_id'=>"d50ccScATNWmCN5fVM8qsV:APA91bHlrNbwEl9RmA60NiuXY8n4-lfwFzZmEahlQt-ZUtGkwKc5r_8GWdmGDLsklAMode0wp0w6St49x4y5_3L1RoZSYGEOtrKiKCjLoanpazL6QYL-TgN0JlkGBtvtn0DO1kEqRSju");
        $result = $this->sendPushNotification($params,$message_array);
        dd($result);
        die; */
        $current_time = date('Y-m-d H:i:s', strtotime(date('Y-m-d H:i:s')));
        $cheking_time = date('Y-m-d H:i:s', strtotime('-4 hour', strtotime(date('Y-m-d H:i:s'))));
        $responded_quote = VendorQuote::leftJoin('quotes', 'vendor_quotes.quote_id', '=', 'quotes.id')
            ->leftJoin('users', 'vendor_quotes.user_id', '=', 'users.id')
            ->select('vendor_quotes.*', 'quotes.item', 'users.name', 'users.email', 'users.mobile', 'users.device_token')
            ->where('vendor_quotes.status', 'Responded')
            ->where('vendor_quotes.isResponded', 1)
            ->where('vendor_quotes.responded_at', '>=', $cheking_time)
            ->where('vendor_quotes.responded_at', '<=', $current_time)
            ->get();
        $quotes = array();
        if (count($responded_quote) > 0) {
            foreach ($responded_quote as $key => $quote_value) {

                $quotes[$key] = $quote_value;
                $quotes[$key]->count_responded = $count_responded = DB::table('vendor_quotes')->select("count('vendor_quotes.id')")->where('vendor_quotes.quote_id', '=', $quote_value->quote_id)->where('vendor_quotes.isResponded', '=', 1)->groupBy("vendor_quotes.quote_id")->count();
                $quotes[$key]->min_price = $min_price = DB::table('vendor_quotes')->where('vendor_quotes.quote_id', '=', $quote_value->quote_id)->groupBy("vendor_quotes.quote_id")->min('vendor_quotes.price');
                $quotes[$key]->max_price = $max_price = DB::table('vendor_quotes')->where('vendor_quotes.quote_id', '=', $quote_value->quote_id)->groupBy("vendor_quotes.quote_id")->max('vendor_quotes.price');
                if ($quote_value->device_token != '' && $quote_value->is_notification_sent != '1') {
                    VendorQuote::where('id', $quote_value->id)->update(['is_notification_sent' => 1]);
                    $this->sendPushNotification("AnyQuote", "Dear user you got total " . $count_responded . " respond from vendor and minimu price is " . $min_price . " and maximum price is " . $max_price . ".", $quote_value->device_token, '');
                }
            }
        }
        return $quotes;
    }

    public function sendPushNotification($data, $message_data)
    {
        $device_type = @$data['device_type'];
        $register_id = $data['register_id'];
        /*$token       = @$data['device_token'];
        $badge       = 0;*/
        //    print_r($message_data);die;
        if ($register_id != "") {

            /*$fields['notification'] = array
            (
            'body'=> $message_data['message'],
            'title'=> $message_data['title'],
            'icon' => 'myicon',
            'sound'=> 'mySound'
            );*/

            $fields['notification'] = array
                (
                'title' => "Anyquote",
                'body' => $message_data['custom_data']['message'],
                'sound' => 'mySound',
                //'badge' => $message_data['custom_data']['count']
            );

            $message_data['custom_data']['message'] = $message_data['custom_data']['message'];
            //print_r($message_data['custom_data']['message']);die;
            if (!empty($message_data['custom_data'])) {

                $fields['data'] = $message_data['custom_data'];
            }
            //$fields['data'] = $message_data;

            if ($data['multiple'] == 1) {
                $fields['registration_ids'] = $register_id;
            } else {
                $fields['to'] = $register_id;
            }
            //$fields['count'] = $message_data['custom_data']['count'];
            //$fields['priority'] = $priority;
            //    print_r($fields);die;
            $headers = array
                (
                'Authorization: key=' . GOOGLE_API_KEY,
                'Content-Type: application/json',
            );
            //print_r($fields);die;
            #Send Reponse To FireBase Server
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
            $result = curl_exec($ch);
            curl_close($ch);
            return $result;

        }
    }

    public function sendPushNotification_old($title, $description, $token, $image = '', $device_type = 0)
    {
        $fcmUrl = 'https://fcm.googleapis.com/fcm/send';
        $notification = [
            'title' => $title,
            'body' => $description,
            "description" => $description,
            "image" => "",
            'sound' => true,
        ];

        $extraNotificationData = ["message" => $notification, "moredata" => 'dd'];
        if ($device_type) {
            // Send IOS Notification
            // Send IOS Notification
            $fields['notification'] = array
                (
                'title' => "Anyquote",
                'body' => $description,
                'sound' => 'mySound',
            );
            $fields['to'] = $token;
        } else {
            // Send ANDROID Notification
            $fields = [
                //'registration_ids' => $tokenList, //multple token array
                'to' => $token, //single token
                'notification' => $notification,
                'data' => $extraNotificationData,
            ];
        }

        $headers = [
            'Authorization: key=' . GOOGLE_API_CUSTOMER_KEY,
            'Content-Type: application/json',
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $fcmUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }

    public function cronUserEmail()
    {
        $current_time = date('Y-m-d H:i:s', strtotime(date('Y-m-d H:i:s')));
        $cheking_time = date('Y-m-d H:i:s', strtotime('-4 hour', strtotime(date('Y-m-d H:i:s'))));
        $result = DB::table('vendor_quotes as v')
            ->leftJoin('users as u', 'v.user_id', '=', 'u.id')
            ->leftJoin('quotes as q', 'v.quote_id', '=', 'q.id')
            ->select('v.*', 'q.item', 'u.email')
            ->where('u.login_method', 'Email')
            ->where('v.isResponded', 1)
            ->where('v.responded_at', '>=', $cheking_time)
            ->where('v.responded_at', '<=', $current_time)
            ->groupBy('v.quote_id')
            ->get();
        foreach ($result as $key => $value) {
            $count_responded = DB::table('vendor_quotes')->select("count('vendor_quotes.id')")->where('vendor_quotes.quote_id', '=', $value->quote_id)->where('vendor_quotes.isResponded', '=', 1)->groupBy("vendor_quotes.quote_id")->count();
            $min_price = DB::table('vendor_quotes')->where('vendor_quotes.quote_id', '=', $value->quote_id)->groupBy("vendor_quotes.quote_id")->min('vendor_quotes.price');
            $max_price = DB::table('vendor_quotes')->where('vendor_quotes.quote_id', '=', $value->quote_id)->groupBy("vendor_quotes.quote_id")->max('vendor_quotes.price');
            $data_array = array(
                'email' => $value->email,
                'item' => $value->item,
                'count_responded' => $count_responded,
                'min_price' => $min_price,
                'max_price' => $max_price,
            );
            Mail::send('emails.quote_responded', $data_array, function ($message) use ($data_array) {
                $message->to($data_array['email'])->from('support@anyquote.in', 'From AnyQuote')->subject("AnyQuote Responded Quote");
            });
        }
    }
}