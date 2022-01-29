<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Quotes;
use App\Quote;
use App\User;
use App\MSG91;
use App\Vendor;
use App\Customer;
use App\Category;
use App\VendorQuote;
use Mail;
use Auth;
use Hash;
use DB;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Session;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Input;

class VendorController extends Controller{

    public function __construct()
    {
        //$this->middleware(['auth', 'clearance'])->except('index', 'quote_store');
    }

    public function index()
    {
        $categories = DB::select('select * from categories ORDER BY category_name ASC');
        return view('vendor', compact('categories'));
    }
   	
	public function categoriesAjax()
    {
		$data = [];

		//if($request->has('q')){
			//$search = $request->q;
			$categoriesdata = DB::table("categories")
				->select("id","category_name")
				//->where('category_name','LIKE',"%$search%")
				->get();
		//}
		
		if(count($categoriesdata)>0){
			foreach($categoriesdata as $category){
				$data[] = $category->category_name;
			}
		}
		
		sort($data);

        return response()->json($data);
	}
	
	
    public function register(Request $request)
    {
        $this->validate($request, [
            'company_name'=>'required',
            'contact_person'=>'required',
            'mobile'=>'required|numeric|unique:vendors',
            'email'=>'required|email|unique:vendors',
            'categories'=>'required',
            'company_address'=>'required',
            'website'=>'required'
        ]);
		
		if(!empty($request->categories)){
			$categoriesdata = Category::select('id', 'category_name')->whereIn('category_name', explode(",", $request->categories))->get();
			$categoriesarray = array();
			
			$categories = array();
			foreach($categoriesdata as $category){
				$categories[] = $category->id;
			}
		}
		
		$sPassword = Hash::make('123456');
        if ($request->email!='') {
            //Auth::login($user);
            preg_match('/(\S+)(@(\S+))/', $request->email, $match);
            $sSubMail = $match[1];  // output: `user`
            $sPassword = Hash::make($sSubMail);
        }

		$sVendor = Vendor::create([
			'name'=> $request->company_name,
			'password'=> $sPassword,
			'company_name'=> $request->company_name,
			'mobile' => $request->mobile,
			'company_phone'=> $request->mobile,
			'email' =>  $request->email,
			'company_email' =>  $request->email,
			'contact_person'=> $request->contact_person,
			'category'=> implode(",",$categories),
			'company_address'=> $request->company_address,
			'company_state'=> $request->company_address,
			'company_city'=> $request->company_address,
			'company_pin'=> $request->company_address,
			'website'=> $request->website,
		]);
		if($sVendor){
			$this->sendSMS($request->mobile, $request->company_name);
			// if successful, then redirect to their intended location
			return redirect()->intended(url('/vendor'))->with('flash_message',
				'Your account has been successfully created. Login to check your quotes and responses.');
		}

        // if unsuccessful, then redirect back to the login with the form data
		return redirect()->back()->withInput($request->only('emailid', 'remember'));
    }


    public function sendSMS($sVendorMobile, $sVendorCompanyName){
        $authentication_key = '265055AdgWc9mN8W0r5c766da0';
        $sMsgToVendor = "You have successfully registered. Please check your mail for login details. \n Thanks,\n Interior Quotes";
        $sMsgToAdmin = "New Vendor Registered. Vendor Name : ".$sVendorCompanyName.", Mobile : ".$sVendorMobile;
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
                "content-type: application/json"
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
    
	
	public function view_sent_quote($vendor_quote_id, Request $request)
    {
		$vendorquote = VendorQuote::join('quotes','quotes.id','=','vendor_quotes.quote_id')->select('vendor_quotes.*','quotes.*','vendor_quotes.created_at as vendorquote_created_at','quotes.created_at as quote_created_at')->where('vendor_quotes.id',$vendor_quote_id)->first();
		return view('vendor/view-send-quote-response', compact('vendorquote'));
	}	
	
	
	public function send_quote($quote_id, Request $request)
    {
		$vendor_id = $request->session()->get('VENDORID');
		
		$quote = Quote::where('id',$quote_id)->first();
		
		/*
		$vendoQquote = VendorQuote::create([
                'user_id' => $quote->user_id,
                'quote_id' => $quote_id,
                'vendor_id' => $vendor_id,
                'quote_response' => 'I am intrested for this quote.',
                'status' => 'Quote Raised',
				//'created_at' => new \DateTime()
            ]);
		*/	
		
		$categories = DB::select('select * from categories ORDER BY category_name ASC');
        return view('vendor/send-quote-response', compact('quote','categories'));
	}
	
	
	public function submitSendQuote($quote_id, Request $request)
    {
		$requestdata = $request->all(); 
		$vendor_id = $request->session()->get('VENDORID');
		$quote = Quote::findOrFail($quote_id);
		
		$vendorresponses = VendorQuote::where('quote_id', $quote_id)->where('vendor_id', $vendor_id)->count();
		$vendor = Vendor::where('id', '=', $vendor_id)->first();
		
		$filename1 = '';
		$image1_title = '';
        if ($request->hasFile('image1')) {
            $image = $requestdata['image1'];
            $path = public_path('assets/images/quote-responses/');
            $filename1 = time() . '.' . $image->getClientOriginalExtension();
			$image->move($path, $filename1);
			$image1_title = $requestdata['image1_title'];
		}
		
		$filename2 = '';
		$image2_title = '';
        if ($request->hasFile('image2')) {
            $image = $requestdata['image2'];
            $path = public_path('assets/images/quote-responses/');
            $filename2 = time() . '.' . $image->getClientOriginalExtension();
			$image->move($path, $filename2);
			$image2_title = $requestdata['image2_title'];
		}
		
		if($vendorresponses > 0){
			$vendorQuote = VendorQuote::where('quote_id', $quote_id)->where('vendor_id', $vendor_id)->first();
			$vendorQuote->discount = $requestdata['discount'];
			$vendorQuote->price = $requestdata['price'];
			$vendorQuote->additionalDetails = $requestdata['additionalDetails'];
			$vendorQuote->photo1 = $filename1;
			$vendorQuote->photo1_title = $image1_title;
			$vendorQuote->photo2 = $filename2;
			$vendorQuote->discount = $image2_title;
			$vendorQuote->status = 'Quote Response Received';
			$vendorQuote->isResponded = 1;
			$vendorQuote->save();
		}else{
			$vendoQquote = VendorQuote::create([
				'discount' => $requestdata['discount'],
				'price' => $requestdata['price'],
				'additionalDetails' => $requestdata['additionalDetails'],
				'photo1' =>  $filename1,
				'photo1_title' => $image1_title,
				'photo2' =>  $filename2,
				'photo2_title' => $image2_title,
				'user_id' => $quote->user_id,
				'quote_id' => $quote_id,
				'vendor_id' => $vendor_id,
				'status' => 'Quote Response Received',
				'isResponded' => 1,
			]);
		}
		
		//Update Quote Status
		$quote->status = 'R';
		$quote->update();
			
		$customer = User::findOrFail($quote->user_id);
		// $senderid = 'IQCSMS';
		$senderid = 'aQuote';
		$authkey = '265055AdgWc9mN8W0r5c766da0';
		$cparameter= Crypt::encrypt($customer->user_id);
		
		// $message = "You Have Recived New Quote Request URL: http://avedemos.uk/interiorquotesv2/public/";
		$smsmessage = "Hi $customer->name, You Have Recived Vendor Quote from ".$vendor->company_name." with mobile : ".$vendor->mobile;
		$mobile = $customer->mobile;
		//$result = $this->getMsg( $senderid, $smsmessage, $mobile, $authkey);
		//$data = array('customer'=>$customer,'quote'=>$vendoQquote,'vendor'=>$vendor,'email' => $customer->email, 'first_name' => $customer->username, 'from' => 'info@interiorquotes.com', 'from_name' =>$vendor->username);
		/*
		Mail::send('emails.mail', $data, function($message)use ($data) {
			$message->to( $data['email'] )->from( $data['from'], $data['first_name'] )
				  ->subject('Vendor Quote');
			$message->from('info@interiorquotes.com','From IQ');
		});
		*/
		return redirect('/vendor/quote-requests')->with('flash_message', 'Quote Response Sent Successfully.');
	}
	
	public function getMsg($senderid, $smsmessage, $mobile, $authkey)
    {
        $url =  "https://api.msg91.com/api/sendhttp.php?mobiles=$mobile&authkey=$authkey&route=4&sender=$senderid&message=$smsmessage&country=91";
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
          return  $err;
        } else {
          return  $response;
        }
    }

    public function sendVOtp(Request $request){

        $aData = Input::all();
        $sMobileNumber = "91".$request->vendMobile;
        $sUserType = $request->usertype;
        $response = array();

        $aVendorInfo = Vendor::where('mobile', $request->vendMobile)->first();

        if($aVendorInfo){

            $otp = rand(100000, 999999);
            $MSG91 = new MSG91();

            $msg91Response = $MSG91->sendOTP($otp, $sMobileNumber, $sUserType);

            if($msg91Response['error']){
                $response['error'] = 1;
                $response['message'] = $msg91Response['message'];
                $response['loggedIn'] = 1;
            }else{

                //Session::put('OTP', $otp);

                $response['error'] = 0;
                $response['message'] = 'OTP sent to Mobile/Email.';
                $response['OTP'] = $otp;
                $response['loggedIn'] = 1;
            }
            echo json_encode($response);
        }else{
            $response['error'] = 1;
            $response['message'] = "Sorry!!!. Please register and try to login";
            $response['loggedIn'] = 0;

        }
    }

    public function verifyVOtp(Request $request) {
        $msg91Response = array();
		$aData = Input::all();
        $sMobileNumber = "91" . $request->vendor_mobile;
        $sUserType = $request->usertype;
        $sOTP = $request->vend_otp;
        $response = array();
        $MSG91 = new MSG91();
        //$msg91Response = $MSG91->verifyOTP($sOTP, $sMobileNumber, $sUserType);

        //$msg91Response = (array)json_decode($msg91Response, true);
		$msg91Response["message"] = "otp_verified";
		
		//exit('Hi');
        
		//print_r($msg91Response);
        if ($msg91Response["message"] == "otp_verified" || $msg91Response["message"] == "already_verified") {
            // Updating user's status "isVerified" as 1.

            $bUpdateVendor = Vendor::where('mobile', $request->vendor_mobile)->update(['isVerified' => 1]);
            // If isVerified == 1 the following if condition fails
            if ($bUpdateVendor) {
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
                return redirect()->intended(url('vendor/quote-requests'));
            }
        } else {
            $response['error'] = 1;
            $response['isVerified'] = 0;
            $response['loggedIn'] = 0;
            $response['message'] = "OTP does not match.";
			return redirect()->back();
            //echo json_encode($response);
        }
    }

    

    /**
     * Display the the Quote Requests.
     *
     * @return Response
     */
    public function quoteRequests(Request $request){
        if($request->session()->has('VENDORID')){
            $vendor_id = $request->session()->get('VENDORID');
			$vQuotes = VendorQuote::leftjoin('vendors','vendors.id','=','vendor_quotes.vendor_id')
				->leftjoin('quotes','quotes.id','=','vendor_quotes.quote_id')
				->leftjoin('categories','quotes.category','=','categories.id')
				->select('vendor_quotes.id as vendor_quote_id','categories.category_name','vendor_quotes.created_at as response_created_at','vendors.*', 'vendor_quotes.isResponded', 'quotes.item','quotes.item_sample','quotes.item_description','quotes.created_at as quote_created_at','quotes.id as my_quote_id')
				->where('vendor_quotes.vendor_id', '=', $vendor_id)
				->orderBy('vendor_quotes.id', 'DESC')
				->get();
				
            return view("vendor/quote-requests", compact('vQuotes'));
        }
    }
	
	
	/**
		* Display the Profile page.
     *
     * @return Response
     */
	public function profile(Request $request){
		if($request->session()->has('VENDORID')){
            $userdata = Vendor::where('id', '=', $request->session()->get('VENDORID'))
                ->orderBy('id', 'DESC')
                ->first();
            return view("vendor/profile",compact('userdata'));
        }
	}
	
	
	/**
     * Display the Dashboard.
     *
     * @return Response
     */
    public function dashboard(Request $request){
        if($request->session()->has('VENDORID')){

            $vendor_id = $request->session()->get('VENDORID');
			
			//Getting User information.
            $vendordata = Vendor::where('id', $vendor_id)->first();
            
			$quotes_responses_count = VendorQuote::where('vendor_id', '=', $vendor_id)->count();
			$quotes_responsed_count = VendorQuote::where('vendor_id', '=', $vendor_id)->where('isResponded', '=', 1)->count();
			
			$quotes = VendorQuote::leftjoin('vendors','vendors.id','=','vendor_quotes.vendor_id')
				->leftjoin('quotes','quotes.id','=','vendor_quotes.quote_id')
				->select('vendor_quotes.id as vendor_quote_id','vendor_quotes.quote_id','vendor_quotes.created_at as response_created_at','vendors.*', 'vendor_quotes.isResponded','quotes.item','quotes.item_sample','quotes.item_description','quotes.created_at as quote_created_at','quotes.id as my_quote_id')
				->where('vendor_quotes.vendor_id', '=', $vendor_id)
				->orderBy('vendor_quotes.id', 'DESC')
				->get();
			
			return view("vendor/dashboard",['quote_requests'=>$quotes,'quotes_responses_count'=>$quotes_responses_count,'quotes_responsed_count'=>$quotes_responsed_count]);
        }else{
            return redirect('/');
        }
    }
}
