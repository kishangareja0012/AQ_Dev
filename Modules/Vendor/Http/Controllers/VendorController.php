<?php
namespace Modules\Vendor\Http\Controllers;

use App\Category;
use App\Http\Controllers\Controller;
use App\MSG91;
use App\Quote;
use App\User;
use App\Vendor;
use App\VendorQuote;
use App\VendorQuoteProducts;
use DB;
use Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Input;
use Mail;
use Razorpay\Api\Api;
use Session;
use App\Notification;

class VendorController extends Controller
{

    public function __construct()
    {
        //$this->middleware(['auth', 'clearance'])->except('index', 'quote_store');
    }

    public function index()
    {
        $categories = DB::select('select * from categories ORDER BY category_name ASC');
        return view('vendor', compact('categories'));
    }

    public function getDownload($filename)
    {
        $pathToFile = public_path('assets/documents/quote-responses/' . $filename);
        return response()->download($pathToFile);
    }

    public function categoriesAjax()
    {
        $data = [];
        //if($request->has('q')){
        //$search = $request->q;
        $categoriesdata = DB::table("categories")
            ->select("id", "category_name")
        //->where('category_name','LIKE',"%$search%")
            ->get();
        //}
        if (count($categoriesdata) > 0) {
            foreach ($categoriesdata as $category) {
                $data[] = $category->category_name;
            }
        }
        sort($data);
        return response()->json($data);
    }

    public function register(Request $request)
    {
        // dd($request->all());
        // 'mobile'=>'required|numeric|unique:vendors',
        // 'email'=>'required|email|unique:vendors',

        $this->validate($request, [
            'company_name' => 'required',
            'contact_person' => 'required',
            'mobile' => 'required|numeric',
            // 'email'=>'required|email',
            'categories' => 'required',
            'company_address' => 'required',
            // 'website'=>'required',
            'locations' => 'required',
            'pincode' => 'required',
        ]);

        $categories = array();
        if (!empty($request->categories)) {
            $categoriesdata = Category::select('id', 'category_name')->whereIn('category_name', explode(",", $request->categories))->get();
            $categoriesarray = array();
            foreach ($categoriesdata as $category) {
                $categories[] = $category->id;
            }
        }

        $sPassword = Hash::make('123456');
        if ($request->email) {
            //Auth::login($user);
            preg_match('/(\S+)(@(\S+))/', $request->email, $match);
            $sSubMail = $match[1]; // output: `user`
            $sPassword = Hash::make($sSubMail);
        }
        $sMobileNumber = "91" . $request->mobile;
        $exists = DB::table('vendors')
        //->where('register_by_self', '=', '1')
        ->where(function ($query) use ($request , $sMobileNumber) {
            $query->orWhere('mobile', '=',  $request->mobile)
                  ->orWhere('email', '=', $request->email)
                  ->orWhere('mobile', '=', $sMobileNumber);
        })
        ->first();
        //$exists = \DB::table('vendors')->where('mobile', '=', $request->mobile)->first()
        if ($exists) {
            if ($exists->register_by_self == 0) {
                $register_data = array(
                    'name' => $request->company_name ? $request->company_name : $exists->name,
                    'password' => $exists->password ? $exists->password : '',
                    'company_name' => $request->company_name ? $request->company_name : $exists->name,
                    'mobile' => $request->mobile,
                    'company_phone' => $request->mobile,
                    'email' => $request->email ? $request->email : $exists->email,
                    'company_email' => $request->email ? $request->email : $exists->email,
                    'contact_person' => $request->contact_person ? $request->contact_person : $exists->contact_person,
                    'category' => $request->categories,
                    'company_address' => $request->company_address . ',' . $request->locations . ',' . $request->pincode,
                    'company_city' => $request->locations,
                    'company_pin' => $request->pincode,
                    'website' => $request->website ? $request->website : $exists->website,
                    'register_by_self' => 1,
                );
                $sVendor = \DB::table('vendors')->where('id', '=', $exists->id)->update($register_data);
                if ($request->quote_id) {
                    \DB::table('vendor_quotes')->where('vendor_id', $exists->id)->where('id', '!=', $request->quote_id)->where('isResponded', '0')->delete();
                    Session::put('VENDORID', $request->vendor_id);
                    $url = url('vendor/send-quote/' . $request->quote_id);
                    return json_encode(array('success' => 1, 'message' => 'Register Successfully..!', 'url' => $url));
                } else {
                    \DB::table('vendor_quotes')->where('vendor_id', $exists->id)->where('isResponded', '0')->delete();
                    return json_encode(array('success' => 1, 'message' => 'Register Successfully..!', 'url' => ''));
                }
            } else {
                return json_encode(array('success' => 0, 'message' => 'EmailID and Phone number is already exists please try to login it..!'));
                // return redirect('/')->with('flash_error_message', 'EmailID and Phone number is already exists please try to login it..!');
            }
        } else {

            $sVendor = Vendor::create([
                'name' => $request->company_name ? $request->company_name : "",
                'password' => $sPassword ? $sPassword : '',
                'company_name' => $request->company_name ? $request->company_name : "",
                'mobile' => $request->mobile ? $request->mobile : "",
                'company_phone' => $request->mobile ? $request->mobile : "",
                'email' => $request->email ? $request->email : "",
                'company_email' => $request->email ? $request->email : "",
                'contact_person' => $request->contact_person ? $request->contact_person : "",
                'category' => $request->categories ? $request->categories : "",
                'company_address' => $request->company_address . ',' . $request->locations . ',' . $request->pincode,
                'company_city' => $request->locations ? $request->locations : "",
                'company_pin' => $request->pincode ? $request->pincode : "",
                //'company_state'=> $request->company_address ? $request->company_address : "",
                //'company_city'=> $request->company_address ? $request->company_address : "",
                //'company_pin'=> $request->company_address ? $request->company_address : "",
                'website' => $request->website ? $request->website : "",
                'register_by_self' => 1,
            ]);
        }

        if ($sVendor) {
            $this->sendSMS($request->mobile, $request->company_name);
            // if successful, then redirect to their intended location
            return json_encode(array('success' => 1, 'message' => 'Register Successfully..!'));
            // return redirect()->intended(url('/vendor'))->with('flash_message', 'Your account has been successfully created. Login to check your quotes and responses.');
        }
        return json_encode(array('success' => 1, 'message' => 'Register Successfully..!'));
        // if unsuccessful, then redirect back to the login with the form data
        // return redirect()->back()->withInput($request->only('emailid', 'remember'));
    }

    public function sendSMS($sVendorMobile, $sVendorCompanyName)
    {
        $authentication_key = '265055AdgWc9mN8W0r5c766da0';
        $sMsgToVendor = "You have successfully registered. Please check your mail for login details. \n Thanks,\n Interior Quotes";
        $sMsgToAdmin = "New Vendor Registered. Vendor Name : " . $sVendorCompanyName . ", Mobile : " . $sVendorMobile;
        $sAdminMobile = "8125449686"; // "9885344485", "9642715020"
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://api.msg91.com/api/v2/sendsms",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => "{ \"sender\": \"SOCKET\", \"route\": \"4\", \"country\": \"91\",
            \"sms\": [ { \"message\": \"$sMsgToVendor\", \"to\": [ \"$sVendorMobile\" ] },
            { \"message\": \"$sMsgToAdmin\", \"to\": [ \"$sAdminMobile\" ] } ] }",
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_HTTPHEADER => array(
                "authkey: $authentication_key",
                "content-type: application/json",
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        if ($err) {
            echo "cURL Error #:" . $err;
        } else {
            return true;
            // dd($response);
            // echo $response;
        }
    }

    public function view_sent_quote($vendor_quote_id, Request $request)
    {

        $quote_id = $vendor_quote_id;
        $quote = VendorQuote::join('quotes', 'quotes.id', '=', 'vendor_quotes.quote_id')
            ->leftjoin('users', 'users.id', '=', 'vendor_quotes.user_id')
            ->select('users.name as customer_name', 'users.mobile as customer_mobile', 'users.email as customer_email', 'vendor_quotes.*', 'quotes.*', 'vendor_quotes.created_at as vendorquote_created_at', 'quotes.created_at as quote_created_at', 'quotes.is_privacy')
            ->where('vendor_quotes.id', $vendor_quote_id)->first();

        // if opened change status to 'viewed'
        if ($quote->vendor_quote_status == 'New') {
            $vendorquote = VendorQuote::findOrFail($quote_id);
            $vendorquote->status = 'Viewed';
            $vendorquote->save();
        }
        $vendorquote_products = array();
        $vendor_quote_products_count = VendorQuoteProducts::where('vendor_quote_id', $quote_id)->count();
        if ($vendor_quote_products_count > 0) {
            $vendorquote_products = VendorQuoteProducts::where('vendor_quote_id', $quote_id)->get();
        }
        return view('vendor::view-send-quote-response', compact('quote', 'quote_id', 'vendorquote_products', 'vendor_quote_products_count'));
    }

    public function send_quote($quote_id, $vendorId = '', Request $request)
    {
        if ($vendorId != '') {
            $vendor_info = Vendor::where('id', $vendorId)->first();
            if ($vendor_info) {
                Session::put('VENDORID', $vendor_info['id']);
                Session::put('VENDORNAME', $vendor_info['name']);
                Session::put('IS_PREMIUM', $vendor_info['is_premium']);
                $sMobileNumber = $vendor_info['mobile'];
                $otp = rand(1111, 9999);
                $MSG91 = new MSG91();
                $msg91Response = $MSG91->sendQuoteOtp($otp, $sMobileNumber);
                $otp_string = md5($otp);
                // dd($msg91Response);
                return redirect(url('vendor_login/' . $quote_id . '/' . $vendorId . '/' . $otp_string));
            }
        }
        // echo "quote_id".$quote_id;
        // echo "vendorId".$vendorId;
        $session_id = $request->session()->get('VENDORID');
        // echo "session_id".$session_id;
        // dd($request->all());
        if ($session_id == '') {
            return redirect('/');
        }

        //$quote = Quote::leftjoin('users','users.id','=','quotes.user_id')
        //->select('users.name as customer_name','users.mobile as customer_mobile','users.email as customer_email','quotes.*')
        //->where('quotes.id',$quote_id)->first();

        $quote = VendorQuote::join('quotes', 'quotes.id', '=', 'vendor_quotes.quote_id')
            ->leftjoin('users', 'users.id', '=', 'vendor_quotes.user_id')
            ->select('users.name as customer_name', 'users.mobile as customer_mobile', 'users.email as customer_email', 'vendor_quotes.*', 'quotes.*', 'vendor_quotes.created_at as vendorquote_created_at', 'vendor_quotes.status as vendor_quote_status', 'quotes.created_at as quote_created_at', 'quotes.is_privacy')
            ->where('vendor_quotes.id', $quote_id)->first();
        // print_r($quote);  exit;
        // if opened change status to 'viewed'
        if ($quote->vendor_quote_status == 'New') {
            $vendorquote = VendorQuote::findOrFail($quote_id);
            $vendorquote->status = 'Viewed';
            $vendorquote->save();
        }

        /*
        $vendoQquote = VendorQuote::create([
        'user_id' => $quote->user_id,
        'quote_id' => $quote_id,
        'vendor_id' => $session_id,
        'quote_response' => 'I am intrested for this quote.',
        'status' => 'Quote Raised',
        //'created_at' => new \DateTime()
        ]);
         */
        $vendorquote_products = array();
        $vendor_quote_products_count = VendorQuoteProducts::where('vendor_quote_id', $quote_id)->count();
        if ($vendor_quote_products_count > 0) {
            $vendorquote_products = VendorQuoteProducts::where('vendor_quote_id', $quote_id)->get();
        }
        //print_r($vendorquote_products); exit;

        $categories = DB::select('select * from categories ORDER BY category_name ASC');
        return view('vendor::send-quote-response', compact('quote', 'categories', 'vendor_quote_products_count', 'vendorquote_products', 'quote_id'));
    }

    public function submitSendQuote($quote_id, Request $request)
    {
        $requestdata = $request->all();
        $vendor_id = $request->session()->get('VENDORID');
        $quote = Quote::findOrFail($requestdata['quote_id']);
        $vendorresponses = VendorQuote::where('id', $quote_id)->count();
        $vendor = Vendor::where('id', '=', $vendor_id)->first();
        $Notification = new Notification();

        $filename1 = '';
        if ($request->hasFile('myfile')) {
            $image = $requestdata['myfile'];
            $path = public_path('assets/images/quote-responses/');
            $filename1 = 'photo-' . time() . '.' . $image->getClientOriginalExtension();
            $image->move($path, $filename1);
        }

        $filename2 = '';
        if ($request->hasFile('mydocument')) {
            $image = $requestdata['mydocument'];
            $path = public_path('assets/documents/quote-responses/');
            $filename2 = 'doc-' . time() . '.' . $image->getClientOriginalExtension();
            $image->move($path, $filename2);
        }

        $curdate = date('Y-m-d H:i:s');
        if ($vendorresponses > 0) {
            $vendorQuote = VendorQuote::where('id', $quote_id)->first();
            $vendorQuote->discount = $requestdata['discount'];
            $vendorQuote->price = $requestdata['price'];
            $vendorQuote->additional_details = $requestdata['additional_details'];
            if (!empty($filename1)) {
                $vendorQuote->photo = $filename1;
            }
            if (!empty($filename2)) {
                $vendorQuote->document = $filename2;
            }
            $vendorQuote->status = 'Responded';
            $vendorQuote->responded_at = $curdate;
            $vendorQuote->expiry_date = $requestdata['expiry_date'];
            $vendorQuote->isResponded = 1;
            $vendorQuote->save();
        } else {
            $vendorQuote = VendorQuote::create([
                'discount' => $requestdata['discount'],
                'price' => $requestdata['price'],
                'additional_details' => $requestdata['additional_details'],
                'photo' => $filename1,
                'document' => $filename2,
                'user_id' => $quote->user_id,
                'quote_id' => $quote_id,
                'vendor_id' => $vendor_id,
                'status' => 'Responded',
                'responded_at' => $curdate,
                'expiry_date' => $requestdata['expiry_date'],
                'isResponded' => 1,
            ]);
        }

        $json_quote = json_encode([
            'user_id' => $quote['user_id'],
            'quote_id' => $quote['id'],
            'vendor_id' => $vendor_id,
            'vendor_name' => $vendor['name'],
            'vendor_mobile' => $vendor['mobile'],
            'vendor_address' => $vendor['company_address'],
            'vendor_website' => $vendor['website'],
            'item' => isset($quote['item']) ? $quote['item'] : '',
        ]);
        $customer = User::findOrFail($quote['user_id']);
        $Notification->sendPushNotificationToCustomer("AnyQuote", "Dear user you got respond from vendor and  price is ".$quote['price'].".", $customer['device_token'],'',$customer['device_type'], $json_quote);

        //Update Quote Status
        $quote->status = 'Quote Responsed';
        $quote->update();

        //print_r($vendorQuote); exit;

        // if(isset($requestdata['product_name'][0]) && !empty($requestdata['product_name'][0])){
        foreach ($requestdata['product_name'] as $num => $product_name) {
            if ($requestdata['product_name'][$num] != '') {
                $product_file = '';

                if (isset($requestdata['myfile1'][$num]) && $requestdata['myfile1'][$num] != '' && $_FILES['myfile1']['error'][$num] == 0) {
                    $image = $requestdata['myfile1'][$num];
                    $path = public_path('assets/images/quote-responses/');
                    $product_file = 'photo-' . time() . rand() . '.' . $image->getClientOriginalExtension();
                    $image->move($path, $product_file);
                }

                if (isset($requestdata['product_id'][$num]) && !empty($requestdata['product_id'][$num])) {
                    $vendor_quote_productid = $requestdata['product_id'][$num];
                    $productdata = VendorQuoteProducts::find($vendor_quote_productid);
                    $productdata->quote_id = $requestdata['quote_id'];
                    $productdata->vendor_quote_id = $vendorQuote->id;
                    $productdata->product_name = $requestdata['product_name'][$num];
                    $productdata->product_description = $requestdata['product_description'][$num];
                    $productdata->product_price = $requestdata['product_price'][$num];
                    $productdata->product_discount = $requestdata['product_discount'][$num];
                    $productdata->product_expirydate = $requestdata['product_expirydate'][$num];
                    $productdata->product_file = $product_file;
                    $productdata->save();
                } else {
                    $similarproduct = array();
                    $similarproduct['quote_id'] = $requestdata['quote_id'];
                    $similarproduct['vendor_quote_id'] = $vendorQuote->id;
                    $similarproduct['product_name'] = $requestdata['product_name'][$num];
                    $similarproduct['product_description'] = $requestdata['product_description'][$num];
                    $similarproduct['product_price'] = $requestdata['product_price'][$num];
                    $similarproduct['product_discount'] = $requestdata['product_discount'][$num];
                    $similarproduct['product_expirydate'] = $requestdata['product_expirydate'][$num];
                    $similarproduct['product_file'] = $product_file;
                    VendorQuoteProducts::create($similarproduct);
                }
            }
        }
        // }

        $customer = User::findOrFail($quote->user_id);
        // $senderid = 'IQCSMS';
        $senderid = 'aQuote';
        $authkey = '265055AdgWc9mN8W0r5c766da0';
        $cparameter = Crypt::encrypt($customer->user_id);

        // $message = "You Have Recived New Quote Request URL: http://avedemos.uk/interiorquotesv2/public/";
        // $smsmessage = "Hi $customer->name, You Have Recived Vendor Quote from ".$vendor->company_name." with mobile : ".$vendor->mobile;
        // $mobile = $customer->mobile;
        //$result = $this->getMsg( $senderid, $smsmessage, $mobile, $authkey);
        //$data = array('customer'=>$customer,'quote'=>$vendoQquote,'vendor'=>$vendor,'email' => $customer->email, 'first_name' => $customer->username, 'from' => 'info@interiorquotes.com', 'from_name' =>$vendor->username);
        /*
        Mail::send('emails.mail', $data, function($message)use ($data) {
        $message->to( $data['email'] )->from( $data['from'], $data['first_name'] )
        ->subject('Vendor Quote');
        $message->from('info@interiorquotes.com','From IQ');
        });
         */
        return redirect('vendor/quote-requests')->with('flash_message', 'Quote Response Sent Successfully.');
    }

    public function getMsg($senderid, $smsmessage, $mobile, $authkey)
    {
        $url = "https://api.msg91.com/api/sendhttp.php?mobiles=$mobile&authkey=$authkey&route=4&sender=$senderid&message=$smsmessage&country=91";
        $url = str_replace(" ", '%20', $url);
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
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

    public function sendVOtp(Request $request)
    {
        $aData = Input::all();
        /* testing detail verify */
        if ($request->vendMobile=="8989582895" || $request->vendMobile=="918989582895") {
            $user = Vendor::where('mobile', '918989582895')->orWhere('mobile', '918989582895')->first();
            $response['error'] = 0;
            $response['message'] = "OTP sent to Mobile...!";
            $response['loggedIn'] = 1;
            return json_encode($response);
        }
        /* end testing detail verify */
        $sMobileNumber = "91" . $request->vendMobile;
        if(strlen($request->vendMobile) > 10) {
            $sMobileNumber = $request->vendMobile;
        } 
        $sUserType = $request->usertype;
        $response = array();
        $aVendorInfo = Vendor::where('mobile', $request->vendMobile)->first();
        if ($aVendorInfo) {
            $otp = rand(100000, 999999);
            $MSG91 = new MSG91();
            $msg91Response = $MSG91->sendOTP($otp, $sMobileNumber, $sUserType);
            if ($msg91Response['error']) {
                $response['error'] = 1;
                $response['message'] = $msg91Response['message'];
                $response['loggedIn'] = 1;
            } else {
                //Session::put('OTP', $otp);
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

    public function verifyVOtp(Request $request)
    {
        $msg91Response = array();
        $aData = Input::all();
        /* testing detail verify */
        if ($request->cust_otp1 == '' || $request->cust_otp2 == '' || $request->cust_otp3 == '' || $request->cust_otp4 == '') {
            return json_encode(array('success' => 0, 'message' => 'Please enter OTP'));
        }
        $customer_otp = $request->cust_otp1.$request->cust_otp2.$request->cust_otp3.$request->cust_otp4;
        if (($request->vendor_mobile=="8989582895" && $customer_otp=='1234') || ($request->vendor_mobile=="918989582895" && $customer_otp=='1234')) {
            $vendor = DB::table('vendors')->where('mobile', $request->vendor_mobile)->first();
            Session::put('VENDORID', $vendor->id);
            Session::put('VENDORNAME', $vendor->name);
            Session::put('IS_PREMIUM', $vendor->is_premium);
            Session::push('vendor_data', $vendor);
            $response['error'] = 0;
            $response['message'] = "Login Successful..!";
            $response['loggedIn'] = 1;
            return json_encode(array('success' => 1, 'message' => 'Login Successful..!'));
        }
        /* end testing detail verify */
        $sMobileNumber = "91" . $request->vendor_mobile;
        $sUserType = $request->usertype;
        // $sOTP = $request->vend_otp;
        if ($request->cust_otp1 == '' || $request->cust_otp2 == '' || $request->cust_otp3 == '' || $request->cust_otp4 == '') {
            return json_encode(array('success' => 0, 'message' => 'Please enter OTP'));
        }

        if ($request->vendor_mobile == '') {
            return json_encode(array('success' => 0, 'message' => 'Mobile is required..!'));
        }

        $sOTP = $request->cust_otp1 . '' . $request->cust_otp2 . '' . $request->cust_otp3 . '' . $request->cust_otp4;
        $response = array();
        $MSG91 = new MSG91();
        $msg91Response = $MSG91->verifyOTP($sOTP, $sMobileNumber, $sUserType);
        $msg91Response = (array) json_decode($msg91Response, true);
        //$msg91Response["message"] = "otp_verified";
        //print_r($msg91Response);
        if ($msg91Response["message"] == "otp_verified" || $msg91Response["message"] == "already_verified") {
            // Updating user's status "isVerified" as 1.
            $bUpdateVendor = Vendor::where('mobile', $request->vendor_mobile)->update(['isVerified' => 1]);
            // If isVerified == 1 the following if condition fails
            if ($bUpdateVendor = true) {
                $response['error'] = 0;
                $response['isVerified'] = 1;
                $response['loggedIn'] = 1;
                $response['message'] = "Your Number is Verified.";
                //echo $request->customer_mobile;
                // Get user record
                $vendor = DB::table('vendors')->where('mobile', $request->vendor_mobile)->first();
                // Set Auth Details
                //Auth::login($user);
                Session::put('VENDORID', $vendor->id);
                Session::put('VENDORNAME', $vendor->name);
                Session::put('IS_PREMIUM', $vendor->is_premium);
                Session::push('vendor_data', $vendor);
                // if successful, then redirect to their intended location
                // Redirect home page
                // if successful, then redirect to their intended location
                return json_encode(array('success' => 1, 'message' => 'Login Successful..!'));
                // return redirect()->intended(url('vendor/quote-requests'));
            }
        } else {
            $response['error'] = 1;
            $response['isVerified'] = 0;
            $response['loggedIn'] = 0;
            $response['message'] = "OTP does not match.";
            return json_encode(array('success' => 0, 'message' => 'OTP is not verified...!'));
            // return redirect()->back();
            //echo json_encode($response);
        }
    }

    public function sendVendorOtp(Request $request)
    {
        $aData = Input::all();
        if ($aData['post_key'] == "Email") {
            // Mail::to($data['contact_email'])->send(new ContactUs($contacts));
            $data = array(
                'otp' => rand(1111, 9999),
                'email' => $aData['vendMobile'],
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

        } else {
            $sMobileNumber = "91" . $request->vendMobile;
                if(strlen($request->vendMobile) > 10) {
                $sMobileNumber = $request->vendMobile;
            } 
            $sUserType = $request->usertype;
            $response = array();
            //$aVendorInfo = Vendor::where('mobile', $request->vendMobile)->first();
            //if($aVendorInfo){
            $otp = rand(100000, 999999);
            $MSG91 = new MSG91();
            $msg91Response = $MSG91->sendOTP($otp, $sMobileNumber, $sUserType);
            if ($msg91Response['error']) {
                $response['error'] = 1;
                $response['message'] = $msg91Response['message'];
                $response['loggedIn'] = 1;
            } else {
                //Session::put('OTP', $otp);
                $response['error'] = 0;
                $response['message'] = 'OTP sent to Mobile/Email.';
                $response['OTP'] = $otp;
                $response['loggedIn'] = 1;
            }
        }
        echo json_encode($response);
    }

    public function verifyVendorOtp(Request $request)
    {
        $msg91Response = array();
        $aData = Input::all();
        $sMobileNumber = "91" . $request->vendor_mobile;
        $sUserType = $request->usertype;
        $sOTP = $request->vend_otp;
        $response = array();
        $MSG91 = new MSG91();
        $msg91Response = $MSG91->verifyOTP($sOTP, $sMobileNumber, $sUserType);

        $msg91Response = (array) json_decode($msg91Response, true);
        //$msg91Response["message"] = "otp_verified";
        //print_r($msg91Response);
        if ($msg91Response["message"] == "otp_verified" || $msg91Response["message"] == "already_verified") {
            // Updating user's status "isVerified" as 1.

            $bUpdateVendor = Vendor::where('mobile', $request->vendor_mobile)->update(['isVerified' => 1]);
            // If isVerified == 1 the following if condition fails
            if ($bUpdateVendor = true) {
                $response['error'] = 0;
                $response['isVerified'] = 1;
                $response['loggedIn'] = 1;
                $response['message'] = "Your Number is Verified.";
                //echo $request->customer_mobile;
                // Get user record
                // $vendor = DB::table('vendors')->where('mobile', $request->vendor_mobile)->first();
                // Set Auth Details
                //Auth::login($user);
                // Session::put('VENDORID', $vendor->id);
                // if successful, then redirect to their intended location
                // Redirect home page
                // if successful, then redirect to their intended location
                //return redirect()->intended(url('vendor/quote-requests'));
                echo json_encode($response);
            }
        } else {
            $response['error'] = 1;
            $response['isVerified'] = 0;
            $response['loggedIn'] = 0;
            $response['message'] = "OTP does not match.";
            //return redirect()->back();
            echo json_encode($response);
        }
    }

    /**
     * Display the the Quote Requests.
     *
     * @return Response
     */
    public function quoteRequests(Request $request)
    {
        $vendor_id = $request->session()->get('VENDORID');
        if (!$vendor_id) {
            return redirect('/');
        }
        if ($request->session()->has('VENDORID')) {
            $vendor_id = $request->session()->get('VENDORID');
            $vendor_detail = Vendor::find($vendor_id);
            $is_premium = $vendor_detail->is_premium;
            $quotes_sent_count = VendorQuote::where('vendor_id', '=', $vendor_id)->count();
            $quotes_responded_count = VendorQuote::where('vendor_id', '=', $vendor_id)->where('isResponded', '=', 1)->count();
            $today_quotes_sent_count = VendorQuote::where('vendor_id', '=', $vendor_id)->whereDate('created_at', date('Y-m-d'))->count();
            $today_quotes_responded_count = VendorQuote::where('vendor_id', '=', $vendor_id)->where('isResponded', '=', 1)->whereDate('responded_at', date('Y-m-d'))->count();
            $keyword = Input::get('keyword');
            $quotequery = VendorQuote::leftjoin('vendors', 'vendors.id', '=', 'vendor_quotes.vendor_id')
                ->leftjoin('quotes', 'quotes.id', '=', 'vendor_quotes.quote_id')
                ->leftjoin('categories', 'quotes.category', '=', 'categories.id')
                ->leftjoin('users', 'users.id', '=', 'vendor_quotes.user_id')
                ->select('users.name as customer_name', 'users.mobile as customer_mobile', 'users.email as customer_email', 'vendor_quotes.id as vendor_quote_id', 'categories.category_name', 'vendor_quotes.created_at as response_created_at', 'vendors.*', 'vendor_quotes.isResponded', 'vendor_quotes.status as vendor_quote_status', 'quotes.item', 'quotes.item_sample', 'quotes.location', 'quotes.item_description', 'quotes.created_at as quote_created_at', 'quotes.id as my_quote_id', 'quotes.is_privacy')
                ->where('vendor_quotes.vendor_id', '=', $vendor_id);

            if ($keyword != '') {
                $quotequery->where(function ($query) use ($keyword) {
                    $query->where('quotes.item', 'LIKE', '%' . $keyword . '%');
                    $query->orWhere('quotes.item_description', 'LIKE', '%' . $keyword . '%');
                    $query->orWhere('quotes.location', 'LIKE', '%' . $keyword . '%');
                });
            }

            $vQuotes = $quotequery->orderBy('vendor_quotes.id', 'DESC')->get();

            return view("vendor::quote-requests", compact('vQuotes', 'quotes_sent_count', 'quotes_responded_count', 'today_quotes_sent_count', 'today_quotes_responded_count', 'is_premium'));
        }
    }

    public function ajax_load_quote(Request $request)
    {
        $vendor_id = $request->session()->get('VENDORID');
        if ($request->session()->has('VENDORID')) {
            $vendor_id = $request->session()->get('VENDORID');
            $vendor_detail = Vendor::find($vendor_id);
            $is_premium = $vendor_detail->is_premium;
            $quotes_sent_count = VendorQuote::where('vendor_id', '=', $vendor_id)->count();
            $quotes_responded_count = VendorQuote::where('vendor_id', '=', $vendor_id)->where('isResponded', '=', 1)->count();
            $today_quotes_sent_count = VendorQuote::where('vendor_id', '=', $vendor_id)->whereDate('created_at', date('Y-m-d'))->count();
            $today_quotes_responded_count = VendorQuote::where('vendor_id', '=', $vendor_id)->where('isResponded', '=', 1)->whereDate('responded_at', date('Y-m-d'))->count();
            $keyword = Input::get('keyword');
            $quotequery = VendorQuote::leftjoin('vendors', 'vendors.id', '=', 'vendor_quotes.vendor_id')
                ->leftjoin('quotes', 'quotes.id', '=', 'vendor_quotes.quote_id')
                ->leftjoin('categories', 'quotes.category', '=', 'categories.id')
                ->leftjoin('users', 'users.id', '=', 'vendor_quotes.user_id')
                ->select('users.name as customer_name', 'users.mobile as customer_mobile', 'users.email as customer_email', 'vendor_quotes.id as vendor_quote_id', 'categories.category_name', 'vendor_quotes.created_at as response_created_at', 'vendors.*', 'vendor_quotes.isResponded', 'vendor_quotes.status as vendor_quote_status', 'quotes.item', 'quotes.item_sample', 'quotes.location', 'quotes.item_description', 'quotes.created_at as quote_created_at', 'quotes.id as my_quote_id', 'quotes.is_privacy')
                ->where('vendor_quotes.vendor_id', '=', $vendor_id);

            if ($keyword != '') {
                $quotequery->where(function ($query) use ($keyword) {
                    $query->where('quotes.item', 'LIKE', '%' . $keyword . '%');
                    $query->orWhere('quotes.item_description', 'LIKE', '%' . $keyword . '%');
                    $query->orWhere('quotes.location', 'LIKE', '%' . $keyword . '%');
                });
            }

            $vQuotes = $quotequery->orderBy('vendor_quotes.id', 'DESC')->get();
            // return view("vendor::quote-requests", compact('vQuotes','quotes_sent_count','quotes_responded_count','today_quotes_sent_count','today_quotes_responded_count','is_premium'));
            $this->data['vQuotes'] = $vQuotes;
            $this->data['quotes_sent_count'] = $quotes_sent_count;
            $this->data['quotes_responded_count'] = $quotes_responded_count;
            $this->data['today_quotes_sent_count'] = $today_quotes_sent_count;
            $this->data['today_quotes_responded_count'] = $today_quotes_responded_count;
            $this->data['is_premium'] = $is_premium;
            $returnHTML = view('vendor::ajax_quote_load')->with($this->data)->render();
            return response()->json(array('success' => true, 'popup_html' => $returnHTML));
        }
    }

    /**
     * Display the Profile page.
     *
     * @return Response
     */
    public function profile(Request $request)
    {
        $categories = DB::select('select * from categories where status="Active" ORDER BY category_name ASC');
        if ($request->session()->has('VENDORID')) {
            $userdata = Vendor::where('id', '=', $request->session()->get('VENDORID'))
                ->orderBy('id', 'DESC')
                ->first();
            return view("vendor::profile", compact('userdata', 'categories'));
        }
    }

    /**
     * Display the Dashboard.
     *
     * @return Response
     */
    public function dashboard(Request $request)
    {
        if ($request->session()->has('VENDORID')) {
            $vendor_id = $request->session()->get('VENDORID');
            $vendor_detail = Vendor::find($vendor_id);
            $is_premium = $vendor_detail->is_premium;

            //Getting User information.
            $vendordata = Vendor::where('id', $vendor_id)->first();
            $quotes_sent_count = VendorQuote::where('vendor_id', '=', $vendor_id)->count();
            $quotes_responded_count = VendorQuote::where('vendor_id', '=', $vendor_id)->where('isResponded', '=', 1)->count();
            $today_quotes_sent_count = VendorQuote::where('vendor_id', '=', $vendor_id)->whereDate('created_at', date('Y-m-d'))->count();
            $today_quotes_responded_count = VendorQuote::where('vendor_id', '=', $vendor_id)->where('isResponded', '=', 1)->whereDate('responded_at', date('Y-m-d'))->count();
            $quotes = VendorQuote::leftjoin('vendors', 'vendors.id', '=', 'vendor_quotes.vendor_id')
                ->leftjoin('quotes', 'quotes.id', '=', 'vendor_quotes.quote_id')
                ->leftjoin('users', 'users.id', '=', 'vendor_quotes.user_id')
                ->select('users.name as customer_name', 'users.mobile as customer_mobile', 'users.email as customer_email', 'vendor_quotes.id as vendor_quote_id', 'vendor_quotes.quote_id', 'vendor_quotes.created_at as response_created_at', 'vendors.*', 'vendor_quotes.isResponded', 'quotes.item', 'quotes.item_sample', 'quotes.item_description', 'quotes.location', 'quotes.created_at as quote_created_at', 'quotes.id as my_quote_id', 'quotes.is_privacy')
                ->where('vendor_quotes.vendor_id', '=', $vendor_id)
                ->orderBy('vendor_quotes.id', 'DESC')
                ->get();
            return view("vendor::dashboard", ['quote_requests' => $quotes, 'quotes_responded_count' => $quotes_responded_count, 'is_premium' => $is_premium, 'today_quotes_sent_count' => $today_quotes_sent_count, 'today_quotes_responded_count' => $today_quotes_responded_count, 'quotes_sent_count' => $quotes_sent_count]);
        } else {
            return redirect('/');
        }
    }

    public function subscribeNow(Request $request)
    {
        $vendor_id = $request->session()->get('VENDORID');
        if (!$vendor_id) {
            return redirect('/');
        }
        return view("vendor::subscribe_now");
    }

    public function payment(Request $request)
    {
        //use when composer is not availalbe
        include app_path('Libraries/razorpay/Razorpay.php');
        //Input items of form
        $input = Input::all();
        //get API Configuration
        $api = new Api(config('razorpay.razor_key'), config('razorpay.razor_secret'));
        //Fetch payment information by razorpay_payment_id
        $payment = $api->payment->fetch($input['razorpay_payment_id']);

        if (count($input) && !empty($input['razorpay_payment_id'])) {
            try {
                $response = $api->payment->fetch($input['razorpay_payment_id'])->capture(array('amount' => $payment['amount']));

            } catch (\Exception $e) {
                return $e->getMessage();
                \Session::put('error', $e->getMessage());
                return redirect('vendor/subscribe-now')->with('flash_message', 'You are now premium user.');
                // return redirect()->back();
            }

            // dd($response);
            // Do something here for store payment details in database...
            $vendor_id = $request->session()->get('VENDORID');
            $vendor = Vendor::find($vendor_id);
            $vendor->is_premium = 1;
            $vendor->save();
        }

        \Session::put('success', 'Payment successful, your order will be despatched in the next 48 hours.');
        return redirect('vendor/quote-requests')->with('flash_message', 'You are now premium vendor.');
        // return redirect()->back();
    }

    public function edit_profile(Request $request)
    {
        $vendor_id = $request->session()->get('VENDORID');
        if (!$vendor_id) {
            return redirect('/');
        }
        $userdata = array();
        if ($request->session()->has('VENDORID')) {
            $userdata = Vendor::where('id', '=', $request->session()->get('VENDORID'))->orderBy('id', 'DESC')->first();
        }

        $category = Category::find($userdata->category);
        $categories = Category::all();
        return view("vendor::edit_profile", compact('userdata', 'category', 'categories'));
    }

    public function submit_profile(Request $request)
    {
        $user_id = $request->session()->get('VENDORID');
        $this->validate($request, [
            'contact_person' => 'required',
            // 'company_email' => 'required',
            'company_address' => 'required',
            // 'website' => 'required',
        ]);

        $category_array = implode($request->categories, ',');
        // dd($categories);

        $user = Vendor::find($user_id);
        $user->contact_person = $request->contact_person ? $request->contact_person : '';
        $user->company_email = $request->company_email ? $request->company_email : '';
        $user->email = $request->company_email ? $request->company_email : '';
        $user->company_address = $request->company_address ? $request->company_address : '';
        $user->website = $request->website ? $request->website : '';
        if (isset($request->categories)) {
            $user->category = $category_array;
        }
        $user->save();
        return redirect('/vendor/edit_profile')->with('flash_message', 'Profile Updated Successfully.');
    }
}