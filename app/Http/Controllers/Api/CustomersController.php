<?php
namespace App\Http\Controllers\Api;

use App\Category;
use App\Customer;
use App\Http\Controllers\Controller;
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
use Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Mail;
use Spatie\Permission\Models\Role;

class CustomersController extends Controller
{
    public function __construct()
    {
        // $this->middleware('auth:api');
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function login(Request $request)
    {

        if ($request->type == 'M') {
            $this->validate($request, [
                'mobile' => 'required',
                'password' => 'required',
            ]);
        } elseif ($request->type == 'E') {
            $this->validate($request, [
                'email' => 'required',
                'password' => 'required',
            ]);
        }
        $apikey = base64_encode(str_random(40));
        if ($request->type == 'M') {
            $customer = Customer::where('mobile', $request->mobile)->where('otp', $request->password)->first();
            if (!empty($customer)) {
                Customer::where('mobile', $request->mobile)->update(['api_key' => "$apikey"]);
                return response()->json(array('status' => true, 'api_key' => $apikey, 'message' => 'Login Successful', 'status_code' => 200));
            } else {
                return response()->json(array('status' => false, 'message' => 'Login Failed', 'status_code' => 400));
            }
        } elseif ($request->type == 'E') {
            $user = User::where('email', $request->email)->first();
            if ($user) {
                $customer = Customer::where('email', $request->email)->first();
                if (Hash::check($request->password, $customer->password)) {
                    $apikey = base64_encode(str_random(40));
                    Customer::where('email', $request->email)->update(['api_key' => "$apikey"]);
                    return response()->json(array('status' => true, 'user_id' => $user->id, 'api_key' => $apikey, 'message' => 'Login Successful', 'status_code' => 200));
                } else {
                    return response()->json(array('status' => false, 'message' => 'Login Failed', 'status_code' => 400));
                }
            }
            return response()->json(array('status' => false, 'message' => 'Login Failed', 'status_code' => 400));
        }
    }

    public function register(Request $request)
    {
        $validMsg = $this->validate($request, [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'mobile' => 'required|numeric|min:10|unique:customers',
            'password' => 'required|string|min:6',
        ]);

        $user = User::create($request->only('email', 'name', 'password'));
        $role_r = Role::findOrFail('4'); //Assigning Role to User
        $user->assignRole($role_r);

        if ($user) {
            $customer = Customer::create([
                'user_id' => $user->id,
                'username' => $request->name,
                'email' => $request->email,
                'mobile' => $request->mobile,
                'password' => Hash::make($request->password),
            ]);
            if ($customer) {
                return response()->json(array('status' => true, 'message' => 'Registration Successfully', 'status_code' => 200));
            } else {
                return response()->json(array('status' => false, 'message' => 'Customer Registration failed', 'status_code' => 400));
            }
        }
        return response()->json(array('status' => false, 'message' => 'User Registration failed', 'status_code' => 400));
    }

    public function sendOtp(Request $request)
    {
        $aData = $request->all();
        /* testing detail verify */
        if ($aData['custMobile'] == "8989582895" || $aData['custMobile'] == "918989582895" || $aData['custMobile'] == "9885344485") {
            $user = User::where('mobile', '918989582895')->orWhere('mobile', '8989582895')->orWhere('mobile', '9885344485')->first();
            $response['error'] = 0;
            $response['message'] = "OTP sent to Mobile/Email...!";
            $response['loggedIn'] = 1;
            return json_encode($response);
        }
        /* end testing detail verify */
        if (!is_numeric($aData['custMobile'])) {
            $data = array(
                'otp' => rand(1111, 9999),
                'email' => $aData['custMobile'],
            );
            Mail::send('emails.send_otp', $data, function ($message) use ($data) {
                $message->to($data['email'])->from($data['email'], '')->subject("AnyQuote Login OTP");
                $message->from('login@anyquote.in', 'From AnyQuote');
            });
            $cntUserInfo = User::where('email', $request->custMobile)->first();
            if (empty($cntUserInfo)) {
                $user = new User();
                $user->name = $request->custMobile;
                $user->password = Hash::make('123456'); //$input['password'];
                $user->email = $request->custMobile;
                $user->mobile = '';
                $user->login_method = 'Email';
                $user->device_token = isset($request->device_token) ? $request->device_token : '';
                $userdata = $user;
                $sResult = $user->save();
            }

            $response['error'] = 0;
            $response['message'] = 'OTP sent to Email.';
            $response['OTP'] = $data['otp'];
            $response['loggedIn'] = 1;
            echo json_encode($response);
        } else {
            $sMobileNumber = "91" . $request->custMobile;
            $response = array();

            //echo "HAi".$request->custMobile; exit;

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
                $user = new User();
                $user->name = $request->custMobile;
                $user->password = Hash::make('123456'); //$input['password'];
                $user->email = $request->custMobile;
                $user->mobile = $request->custMobile;
                $user->login_method = 'Number';
                $user->device_token = $request->device_token ? $request->device_token : '';
                $userdata = $user;
                $sResult = $user->save();
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
            }
        }
    }

    public function vendorRegister(Request $request)
    {
        $response['error'] = 1;
        $response['message'] = 'Error in creating account';
        $mobile_no = strlen($request->mobile) == 10 ? '91' . $request->mobile : $request->mobile;
        $exists = DB::table('vendors')
        //->where('register_by_self', '=', '1')
        ->where(function ($query) use ($request , $mobile_no) {
            $query->orWhere('mobile', '=',  $request->mobile)
                  ->orWhere('email', '=', $request->email)
                  ->orWhere('mobile', '=', $mobile_no);
        })
        ->first();
        if ($exists && $exists->register_by_self == 1) {
            $response['error'] = 1;
            $response['message'] = 'Number or Email is already registered..!';
            return json_encode($response);
        }
        if (!empty($request->categories)) {
            $categoriesdata = Category::select('id', 'category_name')->whereIn('category_name', explode(",", $request->categories))->get();
            $categoriesarray = array();

            $categories = array();
            foreach ($categoriesdata as $category) {
                $categories[] = $category->id;
            }
        }

        //Auth::login($user);
        $sPassword = Hash::make($request->mobile);
        if (isset($request->email) && $request->email) {
            preg_match('/(\S+)(@(\S+))/', $request->email, $match);
            $sSubMail = $match[1]; // output: `user`
            $sPassword = Hash::make($sSubMail);
        }

        $MSG91 = new MSG91();
        $mobile = '91' . $request->mobile;
        $result = $MSG91->verifyOTP($request->otp, $mobile);
        $msg91Response = (array) json_decode($result, true);

        // dd($msg91Response);

        if ($msg91Response["message"] == "otp_verified" || $msg91Response["message"] == "already_verified") {
            if ($exists && $exists->register_by_self == 0) {
                // dd($exists);
                $sVendor = Vendor::where('mobile', $request->mobile)->orWhere('mobile', $mobile)->update([
                    'name' => $request->company_name ? $request->company_name : '',
                    'password' => $sPassword ? $sPassword : '',
                    'company_name' => $request->company_name ? $request->company_name : '',
                    'mobile' => $request->mobile ? $request->mobile : '',
                    'company_phone' => $request->mobile ? $request->mobile : '',
                    'email' => $request->email ? $request->email : '',
                    'company_email' => $request->email ? $request->email : '',
                    'contact_person' => $request->contact_person ? $request->contact_person : '',
                    // 'category' => implode(",", $categories),
                    'category' => $request->categories ? $request->categories : '',
                    'company_address' => $request->company_address ? $request->company_address : '',
                    'company_state' => $request->company_address ? $request->company_address : '',
                    'company_city' => $request->company_city ? $request->company_city : '',
                    'company_pin' => $request->pincode ? $request->pincode : '',
                    'website' => $request->website ? $request->website : '',
                    'register_by_self' => 1,
                    'device_token' => $request->device_token ? $request->device_token : '',
                    'device_type' => $request->device_type ? $request->device_type : 0,
                    'isVerified' => 1,
                ]);
            }else{
                $sVendor = Vendor::create([
                    'name' => $request->company_name ? $request->company_name : '',
                    'password' => $sPassword ? $sPassword : '',
                    'company_name' => $request->company_name ? $request->company_name : '',
                    'mobile' => $request->mobile ? $request->mobile : '',
                    'company_phone' => $request->mobile ? $request->mobile : '',
                    'email' => $request->email ? $request->email : '',
                    'company_email' => $request->email ? $request->email : '',
                    'contact_person' => $request->contact_person ? $request->contact_person : '',
                    // 'category' => implode(",", $categories),
                    'category' => $request->categories ? $request->categories : '',
                    'company_address' => $request->company_address ? $request->company_address : '',
                    'company_state' => $request->company_address ? $request->company_address : '',
                    'company_city' => $request->company_city ? $request->company_city : '',
                    'company_pin' => $request->pincode ? $request->pincode : '',
                    'website' => $request->website ? $request->website : '',
                    'register_by_self' => 1,
                    'device_token' => $request->device_token ? $request->device_token : '',
                    'device_type' => $request->device_type ? $request->device_type : 0,
                    'isVerified' => 1,
                ]);
            }

            $response['error'] = 0;
            $response['message'] = 'Register Successful..!';
            $response['vendor_data'] = Vendor::where('mobile', $request->mobile)->first();
        } else {
            $response['error'] = 1;
            $response['message'] = 'OTP is not verified plaese try again..!';
        }
        echo json_encode($response);
    }

    public function getOtp($otp, $senderid, $message, $mobile, $authkey)
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://control.msg91.com/api/sendotp.php?otp=$otp&sender=$senderid&message=$message&mobile=$mobile&authkey=$authkey",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => "",
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);

        if ($err) {
            return $err;
        } else {
            return $response;
        }
    }

    public function verifyOtp(Request $request)
    {
        /* testing detail verify */
        if ($request->cust_otp1 == '' || $request->cust_otp2 == '' || $request->cust_otp3 == '' || $request->cust_otp4 == '') {
            return json_encode(array('success' => 0, 'message' => 'Please enter OTP'));
        }
        $customer_otp = $request->cust_otp1 . $request->cust_otp2 . $request->cust_otp3 . $request->cust_otp4;
        if (($request->customer_mobile == "8989582895" && $customer_otp != '1234') || ($request->customer_mobile == "9885344485" && $customer_otp != '1234')) {
            $response['error'] = 1;
            $response['isVerified'] = 0;
            $response['loggedIn'] = 0;
            $response['message'] = "OTP does not match.";
            return json_encode($response);
        }
        if (($request->customer_mobile == "8989582895" && $customer_otp == '1234') || ($request->customer_mobile == "918989582895" && $customer_otp == '1234') || ($request->customer_mobile == "9885344485" && $customer_otp == '1234')) {
            $user = User::where('mobile', '918989582895')->orWhere('mobile', '8989582895')->orWhere('mobile', '9885344485')->first();
            Auth::loginUsingId($user->id);
            Session::put('USERID', $user->id);
            Session::push('user_data', $user);
            $response['error'] = 0;
            $response['isVerified'] = 1;
            $response['loggedIn'] = 1;
            $response['message'] = "User is Verified.";
            $response['user'] = $user;
            return json_encode($response);
        }
        /* end testing detail verify */
        if (!is_numeric($request->customer_mobile)) {
            $sOTP = $request->cust_otp1 . '' . $request->cust_otp2 . '' . $request->cust_otp3 . '' . $request->cust_otp4;
            // Updating user's status "isVerified" as 1.
            $bUpdateUser = User::where('email', $request->customer_mobile)->update(['isVerified' => 1, 'device_token' => $request->device_token, 'device_type' => $request->device_type]);
            // Get user record
            $user = DB::table('users')->where('email', $request->customer_mobile)->first();
            Auth::loginUsingId($user->id);
            Session::put('USERID', $user->id);
            Session::push('user_data', $user);
            $response['error'] = 0;
            $response['isVerified'] = 1;
            $response['loggedIn'] = 1;
            $response['message'] = "User is Verified.";
            $response['user'] = $user;
            echo json_encode($response);
        } else {
            $sMobileNumber = "91" . $request->customer_mobile;
            $sOTP = $request->cust_otp1 . '' . $request->cust_otp2 . '' . $request->cust_otp3 . '' . $request->cust_otp4;
            $response = array();
            $MSG91 = new MSG91();
            $msg91Response = $MSG91->verifyOTP($sOTP, $sMobileNumber);
            $msg91Response = (array) json_decode($msg91Response, true);
            if ($msg91Response["message"] == "otp_verified" || $msg91Response["message"] == "already_verified") {
                // Updating user's status "isVerified" as 1.
                $bUpdateUser = User::where('mobile', $request->customer_mobile)->update(['isVerified' => 1, 'device_token' => $request->device_token, 'device_type' => $request->device_type]);
                $response['error'] = 0;
                $response['isVerified'] = 1;
                $response['loggedIn'] = 1;
                $response['message'] = "Your Number is Verified.";

                // Get user record
                $user = DB::table('users')->where('mobile', $request->customer_mobile)->first();

                if (!isset($user->name)) {
                    $response['error'] = 1;
                    $response['isVerified'] = 0;
                    $response['loggedIn'] = 0;
                    $response['message'] = "Your Number is Verified.";
                } else {
                    // Set Auth Details
                    //Auth::login($user);
                    Auth::loginUsingId($user->id);
                    Session::put('USERID', $user->id);
                    $response['error'] = 0;
                    $response['isVerified'] = 1;
                    $response['loggedIn'] = 1;
                    $response['message'] = "Your Number is Verified.";
                    $response['redirect'] = "customer/quote-requests";
                    $response['user'] = Auth::user();
                }
                echo json_encode($response);
            } else {
                $response['error'] = 1;
                $response['isVerified'] = 0;
                $response['loggedIn'] = 0;
                $response['message'] = "OTP does not match.";
                echo json_encode($response);
            }
        }
    }

    public function postQuote(Request $request)
    {
        $input = $request->all();
        $sConfig = DB::table('settings')->first();
        $sCustInfoVisible = $sConfig->customer_info_visible;
        $send_quote_location = $sConfig->quote_location;
        $sms_send = $sConfig->sms_send;
        $categoryData = $this->submitFindCategory($input['quote_title']);
        $category = implode(",", $categoryData['data']['categories']);
        $categories = $categoryData['data']['categories'];
        $categories = array_values($categories);
        $sUserId = $request->userId;
        $category = implode(",", $categoryData['data']['categories']);
        $filename = "";
        $quote_photo = '';
        if ($request->quote_myfile) {
            $image = $request->quote_myfile;
            $quote_photo = time() . '.jpeg';
            $path = "public/assets/images/quotes/" . $quote_photo;
            file_put_contents($path, base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $image)));
        }

        $quote = Quotes::create([
            'user_id' => $sUserId,
            'item' => isset($input['quote_title']) ? $input['quote_title'] : '',
            'item_description' => isset($input['quote_description']) ? $input['quote_description'] : '',
            'location' => isset($input['quote_location']) ? $input['quote_location'] : '',
            'category' => ($category != '') ? $category : 1,
            'is_privacy' => isset($input['privacy']) ? 0 : 1,
            'item_sample' => $quote_photo,
            'status' => 'Quote Raised',
        ]);

        if (isset($categoryData) && $categoryData['status'] == 'success' && ($request->category == '' || $request->category == '0') && (!isset($categoryData['data']['categories']) || (count($categoryData['data']['categories']) == 0))) {
            $response['error'] = 0;
            $response['message'] = 'We got your quote request. Our experts will work with vendors to get you best price.';
            $response['quoteId'] = $quote->id;
            return json_encode($response);
        }

        if ($quote) {
            $user = User::find($sUserId);
            $sQuoteId = $quote->id;
            if (!empty($quote_photo)) {
                $path = asset('public/assets/images/quotes/' . $quote_photo);
            } else {
                // $path = asset('public/assets/images/default.jpeg');
                $path = "";
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

            //Send Email Notification to customer
            $data = array('name' => $user->name, 'email' => $user->email);

            //Send SMS Notification to customer

            $sUserType = "Customer";

            $this->sUserEmail = $user->email;
            $this->sUserMobile = $user->mobile;

            $MSG91 = new MSG91();
            $Notification = new Notification();
            //$msg91Response = $MSG91->sendSMS($this->sUserEmail, $this->sUserMobile, $this->sVendorEmail = '', $this->sVendorMobile = '', $sConfig, $sUserType);

            //Send SMS Notification to all matched vendors
            //$category = 4;

            $categories = $categoryData['data']['categories'];
            $categories = array_values($categories);
            // print_r($categories); exit;

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

            $quote_location1 = isset($input['quote_location']) ? $input['quote_location'] : '';
            $quote_location = $circle_location = explode(',', $quote_location1);
            $location1 = isset($quote_location[0]) ? trim($quote_location[0]) : '';
            $location2 = isset($quote_location[1]) ? trim($quote_location[1]) : '';
            $quote_location = isset($quote_location[1]) ? $quote_location[1] : $quote_location1;
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
                $message_body = "Hello " . $vendor->name . ",\n" . $user['name'] . " has requested quotation for " . strtoupper($input['quote_title']) . ".\nPlease provide your quote here " . url('provide_price/' . $url_id) . " or download our app \n " . $app_link . ".\nYou can contact us +91 9885344485 for further queries.";
                if ($vendor->device_token != '') {
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
                // $msg91Response = $MSG91->sendQuoteSMS($this->sUserEmail, $this->sUserMobile, $vendor->email, $vendor->mobile, $sConfig, $sUserType, $message_body);
                //end SMS code
            }
            if ($sms_send == "Yes") {
                $msg91Response = $MSG91->sendQuoteSMS(json_encode($vendors_sms_array));
            }
            if (count($aVendorsInfo) <= 0) {
                $response['error'] = 0;
                $response['message'] = 'We got your request.
                Our experts will work with vendors to get you best price.
                Please stay tuned on this page .';
                $response['quoteId'] = $quote->id;
                echo json_encode($response);
            } else {
                $response['error'] = 0;
                $response['message'] = 'Your quote has been successfully posted.';
                $response['quoteId'] = $quote->id;
                echo json_encode($response);
            }
        }
    }

    /**
     * Date : 25-Nov-2020 Wednesday
     * In this case customer can serach location on address field with city
     */
    public function postQuote_old(Request $request)
    {
        $input = $request->all();
        $categoryData = $this->submitFindCategory($input['quote_title']);
        $category = implode(",", $categoryData['data']['categories']);
        $categories = $categoryData['data']['categories'];
        $categories = array_values($categories);
        $sUserId = $request->userId;
        $category = implode(",", $categoryData['data']['categories']);
        $filename = "";
        $quote_photo = '';
        if ($request->quote_myfile) {
            $image = $request->quote_myfile;
            $quote_photo = time() . '.jpeg';
            $path = "public/assets/images/quotes/" . $quote_photo;
            file_put_contents($path, base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $image)));
        }

        $quote = Quotes::create([
            'user_id' => $sUserId,
            'item' => isset($input['quote_title']) ? $input['quote_title'] : '',
            'item_description' => isset($input['quote_description']) ? $input['quote_description'] : '',
            'location' => isset($input['quote_location']) ? $input['quote_location'] : '',
            'category' => ($category != '') ? $category : 1,
            'is_privacy' => isset($input['privacy']) ? 0 : 1,
            'item_sample' => $quote_photo,
            'status' => 'Quote Raised',
        ]);

        if (isset($categoryData) && $categoryData['status'] == 'success' && (!isset($categoryData['data']['categories']) || (count($categoryData['data']['categories']) == 0))) {
            $response['error'] = 0;
            $response['message'] = 'We got your quote request. Our experts will work with vendors to get you best price.';
            $response['quoteId'] = $quote->id;
            echo json_encode($response);
            die;
            //return redirect('/')->with('flash_message', 'We got your quote request. Our experts will work with vendors to get you best price.');
        }

        if ($quote) {
            $user = User::find($sUserId);

            //Send Email Notification to customer
            $data = array('name' => $user->name, 'email' => $user->email);

            /*
            Mail::send('emails.customer-welcome', $data, function ($message) {
            $message->subject('Interior Quotes - Account Created');
            $message->from('kbshaik@aveitsolutions.com', 'Interior Quotes');
            $message->to('praveenkolla4@gmail.com');
            });
             */

            //Send SMS Notification to customer
            $sQuoteId = $quote->id;
            $sUserType = "Customer";
            $sConfig = DB::table('settings')->select('customer_info_visible')->first();
            $sCustInfoVisible = $sConfig->customer_info_visible;
            $this->sUserEmail = $user->email;
            $this->sUserMobile = $user->mobile;

            $MSG91 = new MSG91();
            //$msg91Response = $MSG91->sendSMS($this->sUserEmail, $this->sUserMobile, $this->sVendorEmail = '', $this->sVendorMobile = '', $sConfig, $sUserType);

            //Send SMS Notification to all matched vendors
            //$category = 4;

            $categories = $categoryData['data']['categories'];
            $categories = array_values($categories);
            // print_r($categories); exit;

            $catsquery = "";
            foreach ($categories as $num => $category) {
                if ($num + 1 == count($categories)) {
                    $catsquery .= "FIND_IN_SET('" . $category . "', category) ";
                } else {
                    $catsquery .= "FIND_IN_SET('" . $category . "', category) or ";
                }
            }

            /* old code
            $quote_location = explode(',', $input['quote_location']);
            if ($catsquery) {
            $query = "SELECT * FROM vendors WHERE (" . $catsquery . ") AND company_city LIKE '%" . $quote_location[1] . "%'";
            } else {
            $query = "SELECT * FROM vendors WHERE company_city LIKE '%" . $quote_location[1] . "%'";
            } */

            $quote_location1 = isset($input['quote_location']) ? $input['quote_location'] : '';
            $quote_location = explode(',', $quote_location1);
            // $quote_location = isset($quote_location[1]) ? $quote_location[1] : $quote_location1;
            if ($catsquery) {
                if (isset($quote_location[0]) && isset($quote_location[1])) {
                    $location1 = trim($quote_location[0]);
                    $location2 = trim($quote_location[1]);
                    $query = "SELECT * FROM vendors WHERE (" . $catsquery . ") AND company_address REGEXP '(^|[[:space:]])" . $location1 . "([[:space:]]|$)' AND company_city LIKE '" . $location2 . "%'";
                } else {
                    $location1 = trim($quote_location[0]);
                    $query = "SELECT * FROM vendors WHERE (" . $catsquery . ") AND company_address REGEXP '(^|[[:space:]])" . $location1 . "([[:space:]]|$)'";
                }
                // old query // 25-nov-2020
                // $query = "SELECT * FROM vendors WHERE (" . $catsquery . ") AND company_city LIKE '%" . $quote_location . "%'";
            } else {
                if (isset($quote_location[0]) && isset($quote_location[1])) {
                    $location1 = trim($quote_location[0]);
                    $location2 = trim($quote_location[1]);
                    $query = "SELECT * FROM vendors WHERE company_address REGEXP '(^|[[:space:]])" . $location1 . "([[:space:]]|$)' AND company_city LIKE '" . $location2 . "%'";
                } else {
                    $location1 = trim($quote_location[0]);
                    $query = "SELECT * FROM vendors WHERE company_address REGEXP '(^|[[:space:]])" . $location1 . "([[:space:]]|$)'";
                }
                // old query // 25-nov-2020
                // $query = "SELECT * FROM vendors WHERE company_city LIKE '%" . $quote_location . "%'";
            }

            // $query = "SELECT * FROM vendors WHERE ".$catsquery;
            $aVendorsInfo = DB::select(DB::raw($query));

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
                $message_body = "Hello " . $vendor->name . ",\n" . $user['name'] . " has requested quotation for " . strtoupper($input['quote_title']) . ".\nPlease provide your quote here " . url('provide_price/' . $url_id) . " or download our app \n " . $app_link . ".\nYou can contact us +91 9885344485 for further queries.";
                // $msg91Response = $MSG91->sendQuoteSMS($this->sUserEmail, $this->sUserMobile, $vendor->email, $vendor->mobile, $sConfig, $sUserType, $message_body);
                //end SMS code
            }

            if (count($aVendorsInfo) <= 0) {
                $response['error'] = 0;
                $response['message'] = 'We got your request.
                Our experts will work with vendors to get you best price.
                Please stay tuned on this page .';
                $response['quoteId'] = $quote->id;
                echo json_encode($response);
            } else {
                $response['error'] = 0;
                $response['message'] = 'Your quote has been successfully posted.';
                $response['quoteId'] = $quote->id;
                echo json_encode($response);
            }
        }
    }

    public function base64_to_jpeg($base64_string, $output_file)
    {
        // open the output file for writing
        $ifp = fopen($output_file, 'wb');

        // split the string on commas
        // $data[ 0 ] == "data:image/png;base64"
        // $data[ 1 ] == <actual base64 string>
        $data = explode(',', $base64_string);

        // we could add validation here with ensuring count( $data ) > 1
        fwrite($ifp, base64_decode($data[1]));

        // clean up the file resource
        fclose($ifp);

        return $output_file;
    }
    public function save_base64_image($base64_image_string, $output_file_without_extension, $path_with_end_slash = "")
    {
        //usage:  if( substr( $img_src, 0, 5 ) === "data:" ) {  $filename=save_base64_image($base64_image_string, $output_file_without_extentnion, getcwd() . "/application/assets/pins/$user_id/"); }
        //
        //data is like:    data:image/png;base64,asdfasdfasdf
        $splited = explode(',', substr($base64_image_string, 5), 2);
        $mime = $splited[0];
        $data = $splited[1];

        $mime_split_without_base64 = explode(';', $mime, 2);
        $mime_split = explode('/', $mime_split_without_base64[0], 2);
        if (count($mime_split) == 2) {
            $extension = $mime_split[1];
            if ($extension == 'jpeg') {
                $extension = 'jpg';
            }
            //if($extension=='javascript')$extension='js';
            //if($extension=='text')$extension='txt';
            $output_file_with_extension = $output_file_without_extension . '.' . $extension;
        }
        file_put_contents($path_with_end_slash . $output_file_with_extension, base64_decode($data));
        return $output_file_with_extension;
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

    public function updateUser(Request $request)
    {
        // $user = User::find($request->userId);
        // $user->name = $request->name;
        // $user->email = $request->email;
        // $user->save();
        // $response['error'] = 0;
        // $response['message'] = 'User has been successfully updated.';
        // $response['quoteId'] = $user;
        // echo json_encode($response);

        $data = $request->all();
        $otp = $data['cust_otp1'] . '' . $data['cust_otp2'] . '' . $data['cust_otp3'] . '' . $data['cust_otp4'];
        $user_id = $data['userId'];
        if ($request->login_method == "Email") {
            $register_user = User::where('id', '!=', $user_id)->where('mobile', $request->mobile)->get();
            if (count($register_user)) {
                $response['error'] = 0;
                $response['message'] = 'Mobile already exists.';
                echo json_encode($response);
                exit;
            } else {
                $user = User::find($user_id);
                $user->name = $request->name;
                // $user->email = $request->email;
                $user->mobile = $request->mobile;
                $user->save();
                $response['error'] = 0;
                $response['message'] = 'User has been successfully updated.';
                $response['userinfo'] = $user;
                echo json_encode($response);
            }
        } else {
            $register_user = User::where('id', '!=', $user_id)->where('email', $request->email)->get();
            if (count($register_user)) {
                $response['error'] = 0;
                $response['message'] = 'EmailID already exists.';
                echo json_encode($response);
                exit;
            } else {
                $user = User::find($user_id);
                $user->name = $request->name;
                $user->email = $request->email;
                $user->save();
                $response['error'] = 0;
                $response['message'] = 'User has been successfully updated.';
                $response['userinfo'] = $user;
                echo json_encode($response);
            }
        }
        // $response['error'] = 0;

        // echo json_encode($response);
    }

    public function getLocation()
    {
        $locations = DB::table('locations')->select('city')->where('active', 'Y')->groupBy('city')->get();
        $response['error'] = 1;
        $response['message'] = 'Location Not Found.';
        $response['locations'] = array();
        if ($locations) {
            $response['error'] = 0;
            $response['message'] = 'Location Found Successfully...!';
            $response['locations'] = $locations;
        }
        echo json_encode($response);
    }

    public function getCategory()
    {
        $categories = DB::select('select * from categories where status="Active" ORDER BY category_name ASC ');
        $response['error'] = 1;
        $response['message'] = 'Category Not Found.';
        $response['category'] = array();
        if ($categories) {
            $response['error'] = 0;
            $response['message'] = 'Category Found Successfully...!';
            $response['category'] = $categories;
        }
        echo json_encode($response);
    }

    public function searchLocation(Request $request)
    {
        $data = [];
        if ($request->has('search_keyword')) {
            $search = $request->search_keyword;
            // $data =\DB::table('locations')->where('area','LIKE',"%$search%")->groupBy('city')->orderBy('pincode','DESC')->get();
            $data = \DB::table('locations')->where('area', 'LIKE', "{$search}%")->where('active', 'Y')->orderBy('pincode', 'DESC')->get();
            if (count($data)) {
                $response['error'] = 0;
                $response['message'] = 'Location Found Successfully...!';
                $response['location'] = $data;
            } else {
                $response['error'] = 1;
                $response['message'] = 'Location Not Found.';
                $response['location'] = array();
            }
            echo json_encode($response);
        } else {
            $response['error'] = 1;
            $response['message'] = 'Location Not Found.';
            $response['location'] = array();
            echo json_encode($response);
        }
    }

    public function searchCity(Request $request)
    {
        $data = [];
        if ($request->has('search_keyword')) {
            $search = $request->search_keyword;
            $data = \DB::table('locations')->select('city')->where('city', 'LIKE', "{$search}%")->groupBy('city')->get();
            if (count($data)) {
                $response['error'] = 0;
                $response['message'] = 'City Found Successfully...!';
                $response['location'] = $data;
            } else {
                $response['error'] = 1;
                $response['message'] = 'City Not Found.';
                $response['location'] = array();
            }
            echo json_encode($response);
        } else {
            $response['error'] = 1;
            $response['message'] = 'City Not Found.';
            $response['location'] = array();
            echo json_encode($response);
        }
    }

    public function searchCategory(Request $request)
    {
        $data = [];
        if ($request->has('search_keyword')) {
            $search = $request->search_keyword;
            $data = \DB::table('categories')->where('category_name', 'LIKE', "%$search%")->where('status', "Active")->get();
            if (count($data)) {
                $response['error'] = 0;
                $response['message'] = 'Category Found Successfully...!';
                $response['category'] = $data;
            } else {
                $response['error'] = 1;
                $response['message'] = 'Category Not Found.';
                $response['category'] = array();
            }
            echo json_encode($response);
        } else {
            $response['error'] = 1;
            $response['message'] = 'Category Not Found.';
            $response['category'] = array();
            echo json_encode($response);
        }
    }

    public function getCity(Request $request)
    {
        $response['error'] = 1;
        $response['message'] = 'City not found..!';
        $response['city_data'] = array();
        if ($locations = DB::table('locations')->select('city')->where('active', 'Y')->groupBy('city')->get()) {
            $response['error'] = 0;
            $response['message'] = 'City List.';
            $response['city_data'] = $locations;
        }
        echo json_encode($response);
    }

    public function getLocationByCity(Request $request)
    {
        $response['error'] = 1;
        $response['message'] = 'Location not found..!';
        $response['city_data'] = array();
        if ($locations = DB::table('locations')->select('location')->where('city', 'like', '%' . $request->city . '%')->where('active', 'Y')->get()) {
            $response['error'] = 0;
            $response['message'] = 'Location List.';
            $response['city_data'] = $locations;
        }
        echo json_encode($response);
    }

    public function vendorRegisterOtp(Request $request)
    {
        $aData = $request->all();
        // dd();
        $sMobileNumber = "91" . $request->mobile;
        $sUserType = 'vendor';
        $response = array();

        $vendor_data = $aVendorInfo = DB::table('vendors')
        ->where('register_by_self', '=', '1')
        ->where(function ($query) use ($request , $sMobileNumber) {
            $query->orWhere('mobile', '=',  $request->mobile)
                  ->orWhere('email', '=', $request->email)
                  ->orWhere('mobile', '=', $sMobileNumber);
        })
        ->first();

       // if ($vendor_data = Vendor::where('mobile', $request->mobile)->orWhere('email', $request->email)->first()) {
        if ($vendor_data) {
            $response['error'] = 1;
            $response['message'] = 'Number or Email is already registered..!';
            return json_encode($response);
        }

       // $aVendorInfo = Vendor::where('mobile', $request->mobile)->orWhere('mobile', $sMobileNumber)->first();
        if ($aVendorInfo) {
            $response['error'] = 1;
            $response['message'] = "Number is already registered..!";
            echo json_encode($response);
        } else {
            if ($sMobileNumber) {
                $otp = rand(100000, 999999);
                $MSG91 = new MSG91();

                $msg91Response = $MSG91->sendOTP($otp, $sMobileNumber, $sUserType);

                if ($msg91Response['error']) {
                    $response['error'] = 1;
                    $response['message'] = "Sorry!!!. Please try again";
                } else {
                    // $bUpdateVendor = Vendor::where('mobile', $request->mobile)->update(['isVerified' => 1, 'device_token' => $request->device_token]);
                    $response['error'] = 0;
                    $response['message'] = 'Your OTP is sent successfull..!';
                    $response['OTP'] = $otp;
                }
                echo json_encode($response);
            } else {
                $response['error'] = 1;
                $response['message'] = "Sorry!!!. Please try again";
                echo json_encode($response);
            }
        }
    }
}