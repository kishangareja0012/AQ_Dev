<?php
namespace App\Http\Controllers;

use App\Customer;
use App\Http\Controllers\Controller;
use App\Quotes;
use App\User;
use App\Vendor;
use App\VendorQuote;
use Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;

//Importing laravel-permission models

class CustomersController extends Controller
{
    public function __construct()
    {
        //$this->middleware(['auth', 'clearance'])->except('index', 'show');
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function login(Request $request)
    {
        $username = $request->name; //the input field has name='username' in form

        if (filter_var($username, FILTER_VALIDATE_EMAIL)) {
            $customer = User::where('email', $username)->first();
        } else {
            $customer = User::where('name', $username)->first();

        }
        if ($customer) {
            if (Hash::check($request->password, $customer->password)) {
                Auth::login($customer);
                return redirect()->intended(url('/'))->with('flash_message',
                    'Login Successful');
            } else {
                return redirect()->back()->withInput($request->only('email', 'remember'))->with('error_message',
                    'Wrong password');
            }
        } else {
            return redirect()->back()->withInput($request->only('email', 'remember'))->with('error_message',
                'Username does not exist');
        }
    }

    public function register(Request $request)
    {

        $this->validate($request, [
            'mobile' => 'required|numeric',
            'email' => 'required|email|unique:customers',
        ]);
        // Attempt to log the user in
        $in_user = Customer::where('email', $request->email)->first();

        if ($in_user) {
            return redirect()->back()->withInput($request->only('email', 'remember'))->with('flash_message',
                'Email already exist.');
        } else {

            $user = Customer::create($request->only('email', 'mobile')); //Retrieving only the email and mobile number
            //$role_r = Role::findOrFail('4');//Assigning Role to User
            //$user->assignRole($role_r);

            if ($user) {

                //Auth::login($user);
                $customer = Customer::create(['mobile' => $request->mobile,
                    'email' => $request->email,
                ]);
                $this->sendOtp($request->mobile);
                // if successful, then redirect to their intended location
                return redirect()->intended(url('/'))->with('flash_message',
                    'Your account has been successfully created');
            }
            // if unsuccessful, then redirect back to the login with the form data
            return redirect()->back()->withInput($request->only('emailid', 'remember'));
        }

    }

    public function sendOtp($sMobileNumber)
    {

        //echo "this is send otp";die();
        /*if($request->mobile == '')
        {
        return response()->json(array('status'=>false, 'message'=>'Please provied mobile number', 'status_code'=> 400));
        }*/
        $otp = (string) rand(1000, 9999);
        $senderid = 'IQESMS';
        $message = 'Your Otp is ' . $otp;
        $authkey = '265055AdgWc9mN8W0r5c766da0';
        $mobile = "91" . $sMobileNumber; //"919642715020";//.$request->mobilenumber;
        $this->getOtp($otp, $senderid, $message, $mobile, $authkey);die();
        $result = json_decode($this->getOtp($otp, $senderid, $message, $mobile, $authkey));

        $checkMobile = Customer::where('mobile', $request->mobile)->first();
        if ($request->mobile != '' && !empty($checkMobile)) {
            $mobile = "91" . $request->mobile;
            // $result = json_decode($this->getOtp($otp, $senderid, $message, $mobile, $authkey));
            if ($request->mobile) {
                $customer = Customer::where('id', $checkMobile->id)->first();
                $customer->update(['otp' => $otp]);
                return response()->json(array('status' => true, 'message' => 'Otp message send Successfully', 'status_code' => 200));
            }

        } else {
            return response()->json(array('status' => false, 'message' => 'Mobile Number is Not register', 'status_code' => 400));
        }

    }

    public function sendOTP_Bkp($sPhoneNumber)
    {

        //echo 'Phonenumber:'.$sPhoneNumber;die();
        $sAPIKey = '3257e7dc-7548-11e9-ade6-0200cd936042';
        $sCustOTP = (string) rand(100000, 999999);

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "http://2factor.in/API/V1/" . $sAPIKey . "/SMS/" . $sPhoneNumber . "/" . $sCustOTP,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_POSTFIELDS => "",
            CURLOPT_HTTPHEADER => array(
                "content-type: application/x-www-form-urlencoded",
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            echo "cURL Error #:" . $err;
        } else {
            echo $response;
        }
    }

    public function getOtp($otp, $senderid, $message, $mobile, $authkey)
    {

        //echo "getotp";die();
        //echo "https://control.msg91.com/api/sendotp.php?otp=".$otp."&sender=".$senderid."&message=".$message."&mobile=".$mobile."&authkey=".$authkey;die();
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
            echo $err;die();
            return $err;
        } else {
            echo $response;die();
            return $response;
        }
    }

    public function customerVQuotes($quote_id)
    {
        $quote = Quotes::findOrFail($quote_id);
        if (isset($quote)) {
            Auth::logout();
            $user = User::where('id', $quote->userId)->first();
            if ($user) {
                Auth::login($user);
                $vendor_quotes = vendorQuote::where('quote_id', $quote_id)->get();
                $customer = Customer::where('user_id', $quote->userId)->first();
                $vendor = array();
                foreach ($vendor_quotes as $vendor_quote) {
                    $vendor = Vendor::findOrFail($vendor_quote->vendor_id);
                }
            }
        }
        return view('customer_vendor_quote', compact('customer', 'vendor', 'quote', 'vendor_quotes'));
    }
    public function myQuotes()
    {
        $quotes = Quotes::where('user_id', Auth::user()->id)->get();
        if (isset($quotes)) {
            return view('customer_quotes', compact('quotes'));
        }
        return Redirect::back()->with('error_message', 'No Quotes Avaliable');
    }
}
