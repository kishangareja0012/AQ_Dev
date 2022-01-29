<?php
namespace Modules\Vendor\Http\Controllers\Api;

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
        $this->validate($request, [
            'company_name' => 'required',
            'contact_person' => 'required',
            'mobile' => 'required|numeric|unique:vendors',
            'email' => 'required|email|unique:vendors',
            'categories' => 'required',
            'company_address' => 'required',
            'website' => 'required',
        ]);

        if (!empty($request->categories)) {
            $categoriesdata = Category::select('id', 'category_name')->whereIn('category_name', explode(",", $request->categories))->get();
            $categoriesarray = array();

            $categories = array();
            foreach ($categoriesdata as $category) {
                $categories[] = $category->id;
            }
        }

        //Auth::login($user);
        preg_match('/(\S+)(@(\S+))/', $request->email, $match);

        $sSubMail = $match[1]; // output: `user`
        $sPassword = Hash::make($sSubMail);

        /*$sVendor = Vendor::create([
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
        //'company_state'=> $request->company_address,
        //'company_city'=> $request->company_address,
        //'company_pin'=> $request->company_address,
        'website'=> $request->website,
        ]);*/

        $sVendor = Vendor::create([
            'name' => $request->company_name,
            'password' => $sPassword,
            'company_name' => $request->company_name,
            'mobile' => $request->mobile,
            'company_phone' => $request->mobile,
            'email' => $request->email,
            'company_email' => $request->email,
            'contact_person' => $request->contact_person,
            'category' => implode(",", $categories),
            'company_address' => $request->company_address . ',' . $request->locations . ',' . $request->pincode,
            'company_city' => $request->locations,
            'company_pin' => $request->pincode,
            'website' => $request->website,
            'register_by_self' => 1,
        ]);

        if ($sVendor) {
            $this->sendSMS($request->mobile, $request->company_name);
            // if successful, then redirect to their intended location
            return redirect()->intended(url('/vendor'))->with(
                'flash_message',
                'Your account has been successfully created. Login to check your quotes and responses.'
            );
        }

        // if unsuccessful, then redirect back to the login with the form data
        return redirect()->back()->withInput($request->only('emailid', 'remember'));
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
            echo $response;
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

    public function send_quote(Request $request)
    {
        //$vendor_id = $request->session()->get('VENDORID');

        //$quote = Quote::leftjoin('users','users.id','=','quotes.user_id')
        //->select('users.name as customer_name','users.mobile as customer_mobile','users.email as customer_email','quotes.*')
        //->where('quotes.id',$quote_id)->first();
        $quote_id = $request->quote_id;
        $vendor_id = $request->vendor_id;

        $quote = VendorQuote::join('quotes', 'quotes.id', '=', 'vendor_quotes.quote_id')
            ->leftjoin('users', 'users.id', '=', 'vendor_quotes.user_id')
            ->select('users.name as customer_name', 'users.mobile as customer_mobile', 'users.email as customer_email', 'vendor_quotes.*', 'quotes.*', 'vendor_quotes.created_at as vendorquote_created_at', 'vendor_quotes.status as vendor_quote_status', 'quotes.created_at as quote_created_at', 'quotes.is_privacy')
            ->where('vendor_quotes.id', $quote_id)->first();

        //print_r($quote);  exit;

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
        'vendor_id' => $vendor_id,
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
        $response['error'] = 0;
        $response['quote'] = $quote;
        $response['categories'] = $categories;
        $response['vendor_quote_products_count'] = $vendor_quote_products_count;
        $response['vendorquote_products'] = $vendorquote_products;
        $response['quote_id'] = $quote_id;
        $response['loggedIn'] = 1;
        echo json_encode($response);
        //return view('vendor::send-quote-response', compact('quote','categories','vendor_quote_products_count','vendorquote_products','quote_id'));
    }

    /*
    public function submitSendQuote(Request $request)
    {
    $requestdata = $request->all();
    // echo "<pre>"; print_r($requestdata); exit;
    $vendor_id = $requestdata['vendor_id'];
    $quote_id = $requestdata['quote_id'];
    $vendor_quote_id = $requestdata['vendor_quote_id'];
    //$vendor_id = $request->session()->get('VENDORID');
    $quote = Quote::findOrFail($requestdata['quote_id']);

    $vendorresponses = VendorQuote::where('id', $vendor_quote_id)->count();
    //print_r($vendorresponses);die;
    $vendor = Vendor::where('id', '=', $vendor_id)->first();
    //print_r($vendorresponses);

    $filename1 = '';
    // if ($request->hasFile('myfile')) {
    //     $image = $requestdata['myfile'];
    //     $path = public_path('assets/images/quote-responses/');
    //     $filename1 = 'photo-'.time() . '.' . $image->getClientOriginalExtension();
    //     $image->move($path, $filename1);
    // }
    if($request->myfile){
    $image = $request->myfile;
    $quote_photo = 'photo-'.time().'.jpeg';
    $filename1 = $quote_photo;
    $path = public_path('assets/images/quote-responses/').$quote_photo;
    file_put_contents($path,base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $image)));
    }

    $filename2 = '';

    if ($request->hasFile('mydocument')) {
    $image = $requestdata['mydocument'];
    $path = public_path('assets/documents/quote-responses/');
    $filename2 = 'doc-'.time() . '.' . $image->getClientOriginalExtension();
    $image->move($path, $filename2);
    }

    $curdate = date('Y-m-d');
    if($vendorresponses > 0){
    $vendorQuote = VendorQuote::where('id', $vendor_quote_id)->first();
    //print_r($vendorQuote); die;
    $vendorQuote->discount = $requestdata['discount'];
    $vendorQuote->price = $requestdata['price'];
    $vendorQuote->additional_details = $requestdata['additional_details'];
    if(!empty($filename1)){
    $vendorQuote->photo = $filename1;
    }
    if(!empty($filename2)){
    $vendorQuote->document = $filename2;
    }
    $vendorQuote->status = 'Responded';
    $vendorQuote->responded_at = $curdate;
    $vendorQuote->expiry_date = $requestdata['expiry_date'];
    $vendorQuote->isResponded = 1;
    $vendorQuote->save();
    }else{
    $vendorQuote = VendorQuote::create([
    'discount' => $requestdata['discount'],
    'price' => $requestdata['price'],
    'additional_details' => $requestdata['additional_details'],
    'photo' =>  $filename1,
    'document' =>  $filename2,
    'user_id' => $quote->user_id,
    'quote_id' => $quote_id,
    'vendor_id' => $vendor_id,
    'status' => 'Responded',
    'responded_at' => $curdate,
    'expiry_date' => $requestdata['expiry_date'],
    'isResponded' => 1,
    ]);
    }

    //Update Quote Status
    $quote->status = 'Quote Responsed';
    $quote->update();

    //print_r($vendorQuote); exit;

    if(isset($requestdata['product_name'][0]) && !empty($requestdata['product_name'][0])){
    foreach($requestdata['product_name'] as $num=>$product_name){
    if(isset($requestdata['product_id'][$num]) && !empty($requestdata['product_id'][$num])){
    $vendor_quote_productid = $requestdata['product_id'][$num];
    $productdata = VendorQuoteProducts::find($vendor_quote_productid);
    $productdata->quote_id = $requestdata['quote_id'];
    $productdata->vendor_quote_id = $vendorQuote->id;
    $productdata->product_name = $requestdata['product_name'][$num];
    $productdata->product_description = $requestdata['product_description'][$num];
    $productdata->product_price = $requestdata['product_price'][$num];
    $productdata->product_discount = $requestdata['product_discount'][$num];
    $productdata->product_expirydate = $requestdata['product_expirydate'][$num];
    $productdata->product_file = "";
    $productdata->save();
    }else{
    $similarproduct = array();
    $similarproduct['quote_id'] = $requestdata['quote_id'];
    $similarproduct['vendor_quote_id'] = $vendorQuote->id;
    $similarproduct['product_name'] = $requestdata['product_name'][$num];
    $similarproduct['product_description'] = $requestdata['product_description'][$num];
    $similarproduct['product_price'] = $requestdata['product_price'][$num];
    $similarproduct['product_discount'] = $requestdata['product_discount'][$num];
    $similarproduct['product_expirydate'] = $requestdata['product_expirydate'][$num];
    $similarproduct['product_file'] = "";
    VendorQuoteProducts::create($similarproduct);
    }
    }
    }
    $customer = User::findOrFail($quote->user_id);
    $senderid = 'IQCSMS';
    $authkey = '265055AdgWc9mN8W0r5c766da0';
    $cparameter= Crypt::encrypt($customer->user_id);

    // $message = "You Have Recived New Quote Request URL: http://avedemos.uk/interiorquotesv2/public/";
    $smsmessage = "Hi $customer->name, You Have Recived Vendor Quote from ".$vendor->company_name." with mobile : ".$vendor->mobile;
    $mobile = $customer->mobile;
    //$result = $this->getMsg( $senderid, $smsmessage, $mobile, $authkey);

    //$data = array('customer'=>$customer,'quote'=>$vendoQquote,'vendor'=>$vendor,'email' => $customer->email, 'first_name' => $customer->username, 'from' => 'info@interiorquotes.com', 'from_name' =>$vendor->username);

    // Mail::send('emails.mail', $data, function($message)use ($data) {
    //     $message->to( $data['email'] )->from( $data['from'], $data['first_name'] )
    //           ->subject('Vendor Quote');
    //     $message->from('info@interiorquotes.com','From IQ');
    // });

    $response['error'] = 0;
    $response['message'] = "Quote Response Sent Successfully.";
    $response['loggedIn'] = 1;
    echo json_encode($response);
    //return redirect('vendor/quote-requests')->with('flash_message', 'Quote Response Sent Successfully.');
    } */

    public function base64ToImage($image, $storage_path)
    {
        $image = str_replace('data:image/png;base64,', '', $image);
        $image = str_replace(' ', '+', $image);
        $imageName = str_random(10) . '.' . 'png';
        \File::put($storage_path . $imageName, base64_decode($image));
        return $imageName;
    }

    public function submitSendQuote(Request $request)
    {
        $requestdata = $request->all();
        /*$quote_responded = VendorQuote::where('quote_id', $requestdata['quote_id'])->where('vendor_id', $requestdata['vendor_id'])->first();
        if(!$quote_responded || $quote_responded==''){
        $response['error'] = 0;
        $response['message'] = "Quote Request is not found for this vendor.";
        $response['loggedIn'] = 1;
        return json_encode($response);
        } */

        /* $quote_responded = VendorQuote::where('quote_id', $requestdata['quote_id'])->where('vendor_id', $requestdata['vendor_id'])->where('isResponded',1)->first();
        if($quote_responded){
        $response['error'] = 0;
        $response['message'] = "Quote Request is already responded.";
        $response['loggedIn'] = 1;
        return json_encode($response);
        } */

        $vendor_id = $requestdata['vendor_id'];
        $quote = Quote::findOrFail($requestdata['quote_id']);

        $vendorresponses = VendorQuote::where('quote_id', $requestdata['quote_id'])->count();

        $vendor = Vendor::where('id', '=', $vendor_id)->first();

        $filename1 = $filename2 = '';
        /* if ($request->hasFile('photo')) {
        $image = $requestdata['photo'];
        $path = public_path('assets/images/quote-responses/');
        $filename1 = 'photo-' . time() . '.jpeg';
        $image->move($path, $filename1);
        }
        if ($request->hasFile('mydocument')) {
        $image = $requestdata['mydocument'];
        $path = public_path('assets/documents/quote-responses/');
        $filename2 = 'doc-' . time() . '.' . $image->getClientOriginalExtension();
        $image->move($path, $filename2);
        } */

        if ($request->photo != '') {
            $path = public_path('assets/images/quote-responses/');
            $filename1 = $this->base64ToImage($request->photo, $path);
        }

        if ($request->mydocument != '') {
            $path = public_path('assets/documents/quote-responses/');
            $filename2 = $this->base64ToImage($request->mydocument, $path);
        }

        $curdate = date('Y-m-d H:i:s');
        if ($vendorresponses > 0) {
            $vendorQuote = VendorQuote::where('quote_id', $requestdata['quote_id'])->where('vendor_id', $requestdata['vendor_id'])->first();
            if ($vendorQuote['isResponded'] == 0) {
                $vendorQuote = VendorQuote::where('quote_id', $requestdata['quote_id'])->where('vendor_id', $requestdata['vendor_id'])->first();
                $vendorQuote->discount = isset($requestdata['discount']) ? $requestdata['discount'] : '';
                $vendorQuote->price = isset($requestdata['price']) ? $requestdata['price'] : '';
                $vendorQuote->additional_details = isset($requestdata['additional_details']) ? $requestdata['additional_details'] : '';
                if (!empty($filename1)) {
                    $vendorQuote->photo = $filename1;
                }
                if (!empty($filename2)) {
                    $vendorQuote->document = $filename2;
                }
                $vendorQuote->status = 'Responded';
                $vendorQuote->responded_at = $curdate;
                $vendorQuote->expiry_date = isset($requestdata['expiry_date']) ? $requestdata['expiry_date'] : $curdate;
                $vendorQuote->isResponded = 1;
                $vendorQuote->save();
            }
        } else {
            /* $vendorQuote = VendorQuote::create([
        'discount' => $requestdata['discount'],
        'price' => $requestdata['price'],
        'additional_details' => $requestdata['additional_details'],
        'photo' => $filename1,
        'document' => $filename2,
        'user_id' => $quote->user_id,
        'quote_id' => $requestdata['quote_id'],
        'vendor_id' => $vendor_id,
        'status' => 'Responded',
        'responded_at' => $curdate,
        'expiry_date' => $requestdata['expiry_date'],
        'isResponded' => 1,
        ]); */
        }

        //Update Quote Status
        $quote->status = 'Quote Responsed';
        $quote->update();

        if (isset($requestdata['add_more_products']) && $requestdata['add_more_products']) {
            foreach ($requestdata['add_more_products'] as $value) {
                $product_file = '';
                if (isset($value['myfile']) && $value['myfile'] != '') {
                    $path = public_path('assets/images/quote-responses/');
                    $product_file = $this->base64ToImage($value['myfile'], $path);
                }
                VendorQuoteProducts::create([
                    'quote_id' => $value['quote_id'],
                    'vendor_quote_id' => $vendorQuote->id,
                    'product_name' => $value['product_name'],
                    'product_description' => $value['product_description'],
                    'product_price' => $value['product_price'],
                    'product_discount' => $value['product_discount'],
                    'product_expirydate' => $value['product_expirydate'],
                    'product_file' => $product_file,
                ]);
            }
        }

        $customer = User::findOrFail($quote->user_id);
        // $senderid = 'IQCSMS';
        $senderid = 'aQuote';
        $authkey = '265055AdgWc9mN8W0r5c766da0';
        $cparameter = Crypt::encrypt($customer->user_id);

        // $message = "You Have Recived New Quote Request URL: http://avedemos.uk/interiorquotesv2/public/";
        $smsmessage = "Hi $customer->name, You Have Recived Vendor Quote from " . $vendor->company_name . " with mobile : " . $vendor->mobile;
        $mobile = $customer->mobile;
        //$result = $this->getMsg( $senderid, $smsmessage, $mobile, $authkey);
        //$data = array('customer'=>$customer,'quote'=>$vendoQquote,'vendor'=>$vendor,'email' => $customer->email, 'first_name' => $customer->username, 'from' => 'info@interiorquotes.com', 'from_name' =>$vendor->username);
        $vendor_quote_id = $vendorQuote->id;
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

        $quote->photo_url = $quote->document_url = asset('public/assets/images/default.jpeg');
        if (isset($quote->photo) && $quote->photo != '') {
            $quote->photo_url = asset('public/assets/images/quote-responses/' . $quote->photo);
        }

        if (isset($quote->document) && $quote->document != '') {
            $quote->document_url = asset('public/assets/documents/quote-responses/' . $quote->document);
        }

        // if (isset($quote->item_sample) && $quote->item_sample != '') {
        //     $quote->myFile_url = asset('public/assets/images/quotes/' . $quote->item_sample);
        // }

        $vendorquote_products = array();
        $vendor_quote_products_count = VendorQuoteProducts::where('vendor_quote_id', $vendor_quote_id)->count();
        if ($vendor_quote_products_count > 0) {
            $vendorquote_products = VendorQuoteProducts::where('vendor_quote_id', $vendor_quote_id)->get();
        }

        foreach ($vendorquote_products as $key => $value) {
            $vendorquote_products[$key] = $value;
            $vendorquote_products[$key]['product_file_url'] = asset('public/assets/images/default.jpeg');
            if ($value['product_file'] != '') {
                $vendorquote_products[$key]['product_file_url'] = asset('public/assets/images/quote-responses/' . $value['product_file']);
            }
        }

        $response['error'] = 0;
        $response['message'] = "Quote Response Sent Successfully.";
        $response['loggedIn'] = 1;
        $response['quote'] = $quote;
        $response['vendorquote_products'] = count($vendorquote_products) ? $vendorquote_products : array();
        $response['vendor_quote_products_count'] = $vendor_quote_products_count;
        echo json_encode($response);
    }

    public function submitQuoteResponse(Request $request)
    {  
        $requestdata = $request->all();
     
        $Notification = new Notification();
        $vendor_id = $requestdata['vendor_id'];
        $quote = Quote::findOrFail($requestdata['quote_id']);
        
        $vendorresponses = VendorQuote::where('quote_id', $requestdata['quote_id'])->count();
        $vendor = Vendor::where('id', '=', $vendor_id)->first();

        $filename1 = $filename2 = '';
        if (isset($request->photo) && $request->photo != '') {
            $path = public_path('assets/images/quote-responses/');
            $filename1 = $this->base64ToImage($request->photo, $path);
        }

        if (isset($request->mydocument) && $request->mydocument != '') {
            $path = public_path('assets/documents/quote-responses/');
            $filename2 = $this->base64ToImage($request->mydocument, $path);
        }
     
        $curdate = date('Y-m-d H:i:s');
        if ($vendorresponses > 0) {         
            $vendorQuote = VendorQuote::where('quote_id', $requestdata['quote_id'])->where('vendor_id', $requestdata['vendor_id'])->first();
            if ($vendorQuote->isResponded == 0) {           
                $vendorQuote = VendorQuote::where('quote_id', $requestdata['quote_id'])->where('vendor_id', $requestdata['vendor_id'])->first();
                $vendorQuote->discount = isset($requestdata['discount']) ? $requestdata['discount'] : '';
                $vendorQuote->price = isset($requestdata['price']) ? $requestdata['price'] : '';
                $vendorQuote->additional_details = isset($requestdata['additional_details']) ? $requestdata['additional_details'] : '';
                if (!empty($filename1)) {
                    $vendorQuote->photo = $filename1;
                }
                if (!empty($filename2)) {
                    $vendorQuote->document = $filename2;
                }
                $vendorQuote->status = 'Responded';
                $vendorQuote->responded_at = $curdate;
                $vendorQuote->expiry_date = isset($requestdata['expiry_date']) ? $requestdata['expiry_date'] : $curdate;
                $vendorQuote->isResponded = 1;
                $vendorQuote->save();
            }
        }
       
        //Update Quote Status
        $quote->status = 'Quote Responsed';
        $quote->update();

        if (isset($requestdata['add_more_products']) && $requestdata['add_more_products']) {
            $add_more_products = json_decode($requestdata['add_more_products']);
            foreach ($add_more_products as $value) {
                $product_file = '';
                if (isset($value->myfile) && $value->myfile != '') {
                    $path = public_path('assets/images/quote-responses/');
                    $product_file = $this->base64ToImage($value->myfile, $path);
                }
                VendorQuoteProducts::create([
                    'quote_id' => $value->quote_id,
                    'vendor_quote_id' => $vendorQuote->id,
                    'product_name' => $value->product_name ? $value->product_name : '',
                    'product_description' => $value->product_description ? $value->product_description : '',
                    'product_price' => $value->product_price ? $value->product_price : '',
                    'product_discount' => $value->product_discount ? $value->product_discount : "",
                    'product_expirydate' => $value->product_expirydate ? $value->product_expirydate : date('Y-m-d'),
                    'product_file' => $product_file ? $product_file : '',
                ]);
            }
        }

        $customer = User::findOrFail($quote->user_id);
        // $senderid = 'IQCSMS';
        $senderid = 'aQuote';
        $authkey = '265055AdgWc9mN8W0r5c766da0';
        $cparameter = Crypt::encrypt($customer->user_id);

        // $message = "You Have Recived New Quote Request URL: http://avedemos.uk/interiorquotesv2/public/";
        $smsmessage = "Hi $customer->name, You Have Recived Vendor Quote from " . $vendor->company_name . " with mobile : " . $vendor->mobile;
        $mobile = $customer->mobile;
        //$result = $this->getMsg( $senderid, $smsmessage, $mobile, $authkey);
        //$data = array('customer'=>$customer,'quote'=>$vendoQquote,'vendor'=>$vendor,'email' => $customer->email, 'first_name' => $customer->username, 'from' => 'info@interiorquotes.com', 'from_name' =>$vendor->username);
        $vendor_quote_id = $vendorQuote->id;
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

        // $quote->photo_url = $quote->document_url = asset('public/assets/images/default.jpeg');
        $quote->photo_url = $quote->document_url = '';
        if (isset($quote->photo) && $quote->photo != '') {
            $quote->photo_url = asset('public/assets/images/quote-responses/' . $quote->photo);
        }

        if (isset($quote->document) && $quote->document != '') {
            $quote->document_url = asset('public/assets/documents/quote-responses/' . $quote->document);
        }

        // if (isset($quote->item_sample) && $quote->item_sample != '') {
        //     $quote->myFile_url = asset('public/assets/images/quotes/' . $quote->item_sample);
        // }

        $vendorquote_products = array();
        $vendor_quote_products_count = VendorQuoteProducts::where('vendor_quote_id', $vendor_quote_id)->count();
        if ($vendor_quote_products_count > 0) {
            $vendorquote_products = VendorQuoteProducts::where('vendor_quote_id', $vendor_quote_id)->get();
        }

        foreach ($vendorquote_products as $key => $value) {
            $vendorquote_products[$key] = $value;
            // $vendorquote_products[$key]['product_file_url'] = asset('public/assets/images/default.jpeg');
            $vendorquote_products[$key]['product_file_url'] = '';
            if ($value['product_file'] != '') {
                $vendorquote_products[$key]['product_file_url'] = asset('public/assets/images/quote-responses/' . $value['product_file']);
            }
        }
        $json_quote = json_encode([
            'user_id' => $customer->id,
            'quote_id' => $quote->id,
            'vendor_id' => $requestdata['vendor_id'],
            'vendor_name' => $vendor['name'],
            'vendor_mobile' => $vendor['mobile'],
            'vendor_address' => $vendor['company_address'],
            'vendor_website' => $vendor['website'],
            'item' => isset($quote->item) ? $quote->item : '',
        ]);

        $Notification->sendPushNotificationToCustomer("AnyQuote", "Dear user you got respond from vendor and  price is ".$quote->price.".", $customer->device_token,'',$customer->device_type, $json_quote);

        $response['error'] = 0;
        $response['message'] = "Quote Response Sent Successfully.";
        $response['loggedIn'] = 1;
        $response['quote'] = $quote;
        $response['vendorquote_products'] = count($vendorquote_products) ? $vendorquote_products : array();
        $response['vendor_quote_products_count'] = $vendor_quote_products_count;
        echo json_encode($response);
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
        if ($request->vendMobile == "8989582895" || $request->vendMobile == "918989582895" || $request->vendMobile == "9885344485") {
            $user = Vendor::where('mobile', '8989582895')->orWhere('mobile', '918989582895')->orWhere('mobile', '9885344485')->first();
            $response['error'] = 0;
            $response['message'] = "OTP sent to Mobile...!";
            $response['loggedIn'] = 1;
            return json_encode($response);
        }
        /* end testing detail verify */
        $sMobileNumber = "91" . $request->vendMobile;
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

    public function verifyVOtp(Request $request)
    {
        $msg91Response = array();
        $aData = Input::all();
        /* testing detail verify */
        if ($request->cust_otp1 == '' || $request->cust_otp2 == '' || $request->cust_otp3 == '' || $request->cust_otp4 == '') {
            return json_encode(array('success' => 0, 'message' => 'Please enter OTP'));
        }
        $customer_otp = $request->cust_otp1 . $request->cust_otp2 . $request->cust_otp3 . $request->cust_otp4;
        if (($request->vendor_mobile == "8989582895" && $customer_otp != '1234') || $request->vendor_mobile == "9885344485" && $customer_otp != '1234') {
            $response['error'] = 0;
            $response['message'] = "OTP does not match.";
            $response['loggedIn'] = 1;
            $response['user'] = array();
            return json_encode($response);
        }
        if (($request->vendor_mobile == "8989582895" && $customer_otp == '1234') || ($request->vendor_mobile == "918989582895" && $customer_otp == '1234') || ($request->vendor_mobile == "9885344485" && $customer_otp == '1234')) {
            $vendor = Vendor::where('mobile', '8989582895')->orWhere('mobile', '918989582895')->orWhere('mobile', '9885344485')->first();
            Session::put('VENDORID', $vendor->id);
            Session::put('VENDORNAME', $vendor->name);
            Session::put('IS_PREMIUM', $vendor->is_premium);
            Session::push('vendor_data', $vendor);
            $response['error'] = 0;
            $response['message'] = "Login Successful..!";
            $response['loggedIn'] = 1;
            $response['user'] = $vendor;
            return json_encode($response);
        }
        /* end testing detail verify */
        $sMobileNumber = "91" . $request->vendor_mobile;
        $sUserType = $request->usertype;
        // $sOTP = $request->vend_otp;
        $sOTP = $request->cust_otp1 . '' . $request->cust_otp2 . '' . $request->cust_otp3 . '' . $request->cust_otp4;
        $response = array();
        $MSG91 = new MSG91();
        $msg91Response = $MSG91->verifyOTP($sOTP, $sMobileNumber, $sUserType);

        $msg91Response = (array) json_decode($msg91Response, true);
        //$msg91Response["message"] = "otp_verified";
        //print_r($msg91Response);
        if ($msg91Response["message"] == "otp_verified" || $msg91Response["message"] == "already_verified") {
            // Updating user's status "isVerified" as 1.

            $bUpdateVendor = Vendor::where('mobile', $request->vendor_mobile)->update([
                'isVerified' => 1,
                'device_token' => $request->device_token,
                'device_type' => $request->device_type,
            ]);
            // If isVerified == 1 the following if condition fails
            if ($bUpdateVendor = true) {
                // Get user record
                $vendor = DB::table('vendors')->where('mobile', $request->vendor_mobile)->first();
                // Set Auth Details
                //Auth::login($user);
                Session::put('VENDORID', $vendor->id);
                // if successful, then redirect to their intended location
                // Redirect home page
                // if successful, then redirect to their intended location
                $response['error'] = 0;
                $response['isVerified'] = 1;
                $response['loggedIn'] = 1;
                $response['message'] = "Your Number is Verified.";
                $response['user'] = $vendor;
                //return redirect()->back();
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

    public function sendVendorOtp(Request $request)
    {
        $aData = Input::all();
        $sMobileNumber = "91" . $request->vendMobile;
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
            $response['message'] = 'Your OTP is created.';
            $response['OTP'] = $otp;
            $response['loggedIn'] = 1;
        }
        echo json_encode($response);
        /*}else{
    $response['error'] = 1;
    $response['message'] = "Sorry!!!. Please register and try to login";
    $response['loggedIn'] = 0;
    echo json_encode($response);
    }*/
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
        $vendor_id = $request->vendorId;
        $vendor_quote_status = $request->vendor_quote_status;
        $page_no = ($request->page_no != 0) ? $request->page_no : "1";
        $no_of_record = $page_no * 10;
        $offset = $no_of_record - 10;
        if ($vendor_quote_status == "N") {
            $vendor_quote_status = "New";
        } else if ($vendor_quote_status == "V") {
            $vendor_quote_status = "Viewed";
        } else if ($vendor_quote_status == "R") {
            $vendor_quote_status = "Responded";
        }

        $quotes_sent_count = VendorQuote::where('vendor_id', '=', $vendor_id)->count();
        if ($vendor_quote_status) {
            $quotes_sent_count = VendorQuote::where('vendor_id', '=', $vendor_id)->where('status', '=', $vendor_quote_status)->count();
        }

        $quotes_responded_count = VendorQuote::where('vendor_id', '=', $vendor_id)->where('isResponded', '=', 1)->count();
        $today_quotes_sent_count = VendorQuote::where('vendor_id', '=', $vendor_id)->whereDate('created_at', date('Y-m-d'))->count();
        $today_quotes_responded_count = VendorQuote::where('vendor_id', '=', $vendor_id)->where('isResponded', '=', 1)->whereDate('responded_at', date('Y-m-d'))->count();

        $keyword = $request->keyword;

        $quotequery = VendorQuote::leftjoin('vendors', 'vendors.id', '=', 'vendor_quotes.vendor_id')
            ->leftjoin('quotes', 'quotes.id', '=', 'vendor_quotes.quote_id')
            ->leftjoin('categories', 'quotes.category', '=', 'categories.id')
            ->leftjoin('users', 'users.id', '=', 'vendor_quotes.user_id')
            ->select('users.name as customer_name', 'users.mobile as customer_mobile', 'users.email as customer_email', 'vendor_quotes.id as vendor_quote_id', 'vendor_quotes.user_id as quote_user_id', 'categories.category_name', 'vendor_quotes.created_at as response_created_at', 'vendors.*', 'vendor_quotes.isResponded', 'vendor_quotes.status as vendor_quote_status', 'vendor_quotes.price', 'vendor_quotes.discount', 'vendor_quotes.additional_details', 'vendor_quotes.expiry_date', 'quotes.item', 'quotes.item_sample', 'quotes.location', 'quotes.item_description', 'quotes.created_at as quote_created_at', 'quotes.id as my_quote_id', 'quotes.is_privacy')
            ->where('vendor_quotes.vendor_id', '=', $vendor_id);

        if ($keyword != '') {
            $quotequery->where(function ($query) use ($keyword) {
                $query->where('quotes.item', 'LIKE', '%' . $keyword . '%');
                $query->orWhere('quotes.item_description', 'LIKE', '%' . $keyword . '%');
                $query->orWhere('quotes.location', 'LIKE', '%' . $keyword . '%');
            });
        }

        if ($vendor_quote_status != '') {
            $quotequery->where('vendor_quotes.status', '=', $vendor_quote_status);
        }

        // $vQuotes = $quotequery->orderBy('vendor_quotes.id', 'DESC')->limit(10)->get();
        if (isset($request->page_no) && $request->page_no != 0) {
            $vQuotes = $quotequery->orderBy('vendor_quotes.id', 'DESC')->offset($offset)->limit(10)->get();
        } else {
            $vQuotes = $quotequery->orderBy('vendor_quotes.id', 'DESC')->get();
        }

        $requests = array();
        foreach ($vQuotes as $key => $value) {
            $requests[$key] = $value;
            if ($value->item_sample != '') {
                $requests[$key]->item_sample = asset('public/assets/images/quotes/' . $value->item_sample);
            } else {
                $requests[$key]->item_sample = asset('public/assets/images/default.jpeg');
            }
            if ($value->customer_name != '') {
                $requests[$key]->customer_name = 'Anyquote Customer';
            }
            if ($value->customer_mobile != '') {
                $requests[$key]->customer_mobile = '';
            }
            if ($value->customer_email != '') {
                $requests[$key]->customer_email = '';
            }
        }
        $vQuotes = $requests;

        // return view("vendor::quote-requests", compact('vQuotes','quotes_sent_count','quotes_responded_count','today_quotes_sent_count','today_quotes_responded_count'));
        $response['error'] = 0;
        $response['vQuotes'] = $vQuotes;
        // $response['quotes_sent_count'] = $quotes_sent_count;
        $response['quotes_sent_count'] = $quotes_sent_count >= 200 ? 'more than 200' : $quotes_sent_count;
        $response['total_quotes_count'] = $quotes_sent_count;
        $response['next_page'] = $quotes_sent_count - ($page_no * 10) > 0 ? 1 : 0;
        $response['quotes_responded_count'] = $quotes_responded_count;
        $response['today_quotes_sent_count'] = $today_quotes_sent_count;
        $response['today_quotes_responded_count'] = $today_quotes_responded_count;
        $response['loggedIn'] = 1;
        echo json_encode($response);
    }

    /**
     * Display the Profile page.
     *
     * @return Response
     */
    public function profile(Request $request)
    {
        if ($request->session()->has('VENDORID')) {
            $userdata = Vendor::where('id', '=', $request->session()->get('VENDORID'))
                ->orderBy('id', 'DESC')
                ->first();
            return view("vendor::profile", compact('userdata'));
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

            //Getting User information.
            $vendordata = Vendor::where('id', $vendor_id)->first();

            $quotes_responses_count = VendorQuote::where('vendor_id', '=', $vendor_id)->count();
            $quotes_responsed_count = VendorQuote::where('vendor_id', '=', $vendor_id)->where('isResponded', '=', 1)->count();

            $quotes = VendorQuote::leftjoin('vendors', 'vendors.id', '=', 'vendor_quotes.vendor_id')
                ->leftjoin('quotes', 'quotes.id', '=', 'vendor_quotes.quote_id')
                ->leftjoin('users', 'users.id', '=', 'vendor_quotes.user_id')
                ->select('users.name as customer_name', 'users.mobile as customer_mobile', 'users.email as customer_email', 'vendor_quotes.id as vendor_quote_id', 'vendor_quotes.quote_id', 'vendor_quotes.created_at as response_created_at', 'vendors.*', 'vendor_quotes.isResponded', 'quotes.item', 'quotes.item_sample', 'quotes.item_description', 'quotes.location', 'quotes.created_at as quote_created_at', 'quotes.id as my_quote_id', 'quotes.is_privacy')
                ->where('vendor_quotes.vendor_id', '=', $vendor_id)
                ->orderBy('vendor_quotes.id', 'DESC')
                ->get();

            return view("vendor::dashboard", ['quote_requests' => $quotes, 'quotes_responses_count' => $quotes_responses_count, 'quotes_responsed_count' => $quotes_responsed_count]);
        } else {
            return redirect('/');
        }
    }

    public function searchQuote(Request $request)
    {
        // dd($request->all());
        /* SELECT `vendor_quotes`.`id` AS `vendor_quote_id`, `vendor_quotes`.`isResponded`, `vendor_quotes`.`created_at` AS `response_created_at`, `vendor_quotes`.`expiry_date`, `vendor_quotes`.`price`, `vendors`.*  FROM `vendor_quotes`  LEFT JOIN `vendors` ON `vendors`.`id` = `vendor_quotes`.`vendor_id`  WHERE `quote_id` = '805' AND (`isResponded` != 0 OR `vendor_quotes`.`vendor_id` IN(SELECT id FROM vendors WHERE `register_by_self`!=0 AND (`company_city` LIKE '%Nizam%' OR `name` LIKE '%kishan%'))); */
        $userid = $request->userid;
        $quote_id = $request->quote_id;
        $page_no = ($request->page_no != 0) ? $request->page_no : "1";
        $no_of_record = $page_no * 10;
        $offset = $no_of_record - 10;
        // $quotedata = Quotes::where('id', '=', $quote_id)->first();

        $settings = DB::table('settings')->where('id', 1)->first();
        $phone_number_visible = $settings->phone_number_visible;
        $address_visible = $settings->address_visible;
        $email_visible = $settings->email_visible;
        $hide_all = $settings->hide_all;
        $vendor_request_to_users = $settings->vendor_request_to_users;

        $where = "`register_by_self`=0 OR `register_by_self`!=0";
        if ($vendor_request_to_users == 'non_registered_vendor') {
            $where = "`register_by_self`=0";
        }
        if ($vendor_request_to_users == 'registered_vendor') {
            $where = "`register_by_self`!=0";
        }

        $location = '';
        $vendor = '';
        $sortby = '';
        // if key is 0 then got all quote
        if ($request->key == 0) {
            $search_query = "SELECT `vendor_quotes`.`id` AS `vendor_quote_id`, `vendor_quotes`.`isResponded`, `vendor_quotes`.`created_at` AS `response_created_at`,
            `vendor_quotes`.`expiry_date`, `vendor_quotes`.`price`,`vendor_quotes`.`photo`,`vendor_quotes`.`document`, `vendors`.*
            FROM `vendor_quotes`
            LEFT JOIN `vendors` ON `vendors`.`id` = `vendor_quotes`.`vendor_id`
            WHERE `quote_id` = '$quote_id' AND `user_id` = '$userid' AND (`isResponded` != 0 OR `vendor_quotes`.`vendor_id` IN(SELECT id FROM vendors WHERE $where )) ORDER BY `vendor_quotes`.`isResponded` DESC LIMIT $offset,10";
        }
        // if key is 1 then got quote by vendor name
        if ($request->key == 1) {
            $vendor = isset($request->search_key) ? $request->search_key : '';
            $search_query = "SELECT `vendor_quotes`.`id` AS `vendor_quote_id`, `vendor_quotes`.`isResponded`, `vendor_quotes`.`created_at` AS `response_created_at`,
            `vendor_quotes`.`expiry_date`, `vendor_quotes`.`price`,`vendor_quotes`.`photo`,`vendor_quotes`.`document`, `vendors`.*
            FROM `vendor_quotes`
            LEFT JOIN `vendors` ON `vendors`.`id` = `vendor_quotes`.`vendor_id`
            WHERE `quote_id` = '$quote_id' AND `user_id` = '$userid' AND (`isResponded` != 0 OR `vendor_quotes`.`vendor_id` IN(SELECT id FROM vendors WHERE $where AND (`name` LIKE '%$vendor%') )) ORDER BY `vendor_quotes`.`isResponded` DESC LIMIT $offset,10";

            if ($vendor == '') {
                $search_query = "SELECT `vendor_quotes`.`id` AS `vendor_quote_id`, `vendor_quotes`.`isResponded`, `vendor_quotes`.`created_at` AS `response_created_at`,
                `vendor_quotes`.`expiry_date`, `vendor_quotes`.`price`,`vendor_quotes`.`photo`,`vendor_quotes`.`document`, `vendors`.*
                FROM `vendor_quotes`
                LEFT JOIN `vendors` ON `vendors`.`id` = `vendor_quotes`.`vendor_id`
                WHERE `quote_id` = '$quote_id' AND `user_id` = '$userid' AND (`isResponded` != 0 OR `vendor_quotes`.`vendor_id` IN(SELECT id FROM vendors WHERE $where )) ORDER BY `vendor_quotes`.`isResponded` DESC LIMIT $offset,10";
            }
        }
        // if key is 2 then got quote by location
        if ($request->key == 2) {
            $location = isset($request->search_key) ? $request->search_key : '';

            $search_query = "SELECT `vendor_quotes`.`id` AS `vendor_quote_id`, `vendor_quotes`.`isResponded`, `vendor_quotes`.`created_at` AS `response_created_at`,
                `vendor_quotes`.`expiry_date`, `vendor_quotes`.`price`,`vendor_quotes`.`photo`,`vendor_quotes`.`document`, `vendors`.*
            FROM `vendor_quotes`
            LEFT JOIN `vendors` ON `vendors`.`id` = `vendor_quotes`.`vendor_id`
            WHERE `quote_id` = '$quote_id' AND `user_id` = '$userid' AND (`isResponded` != 0 OR `vendor_quotes`.`vendor_id` IN(SELECT id FROM vendors WHERE $where AND (`company_city` LIKE '%$location%' OR `company_address` LIKE '%$location%'))) ORDER BY `vendor_quotes`.`isResponded` DESC LIMIT $offset,10";

            if ($location == '') {
                $search_query = "SELECT `vendor_quotes`.`id` AS `vendor_quote_id`, `vendor_quotes`.`isResponded`, `vendor_quotes`.`created_at` AS `response_created_at`,
                `vendor_quotes`.`expiry_date`, `vendor_quotes`.`price`,`vendor_quotes`.`photo`,`vendor_quotes`.`document`, `vendors`.*
                FROM `vendor_quotes`
                LEFT JOIN `vendors` ON `vendors`.`id` = `vendor_quotes`.`vendor_id`
                WHERE `quote_id` = '$quote_id' AND `user_id` = '$userid' AND (`isResponded` != 0 OR `vendor_quotes`.`vendor_id` IN(SELECT id FROM vendors WHERE $where )) ORDER BY `vendor_quotes`.`isResponded` DESC LIMIT $offset,10";
            }
        }
        $quotequery = DB::select(DB::raw($search_query));

        // dd($quotequery->toSql());
        $total_record_count = count($quotequery);
        // $quoteresponses = $quotequery;
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

        $quote_detail = Quote::where('id', $quote_id)->first();
        if ($quote_detail['item_sample'] != '') {
            $quote_detail['item_sample'] = asset('public/assets/images/quotes/' . $quote_detail['item_sample']);
        } else {
            $quote_detail['item_sample'] = asset('public/assets/images/default.jpeg');
        }

        $vendor_sent_count = VendorQuote::where('quote_id', $quote_id)->where('user_id', $userid)->count();
        $received_price_count = VendorQuote::where('quote_id', $quote_id)->where('user_id', $userid)->where('isResponded', 1)->count();

        if ($total_record_count) {
            $response['error'] = 0;
            $response['vQuotes'] = $quote_result;
            $response['quotedata'] = $quote_detail;
            $response['address_visible'] = $address_visible;
            $response['email_visible'] = $email_visible;
            $response['phone_number_visible'] = $phone_number_visible;
            $response['hide_all'] = $hide_all;
            $response['loggedIn'] = 1;
            $response['loggedIn'] = 'Data Found Successfully..!';
            $response['total_record_count'] = $total_record_count;
            $response['total_sent_count'] = $vendor_sent_count;
            $response['received_price_count'] = $received_price_count;
            echo json_encode($response);
        } else {
            $response['error'] = 0;
            $response['vQuotes'] = array();
            $response['quotedata'] = $quote_detail;
            $response['address_visible'] = $address_visible;
            $response['email_visible'] = $email_visible;
            $response['phone_number_visible'] = $phone_number_visible;
            $response['hide_all'] = $hide_all;
            $response['loggedIn'] = 1;
            $response['loggedIn'] = 'Data Not Found..!';
            $response['total_record_count'] = 0;
            $response['total_sent_count'] = $vendor_sent_count;
            $response['received_price_count'] = $received_price_count;
            echo json_encode($response);
        }
    }

    public function getProfile(Request $request)
    {
        $response['error'] = 1;
        $response['loggedIn'] = '1';
        $response['message'] = 'Profile not found..!';
        $response['vendor_data'] = array();
        if ($user = Vendor::where('id', '=', $request->vendor_id)->first()) {
            $cat_array = explode(',', $user->category);
            $cat_name = '';
            foreach ($cat_array as $key => $value) {
                $category_name = Category::where('id', $value)->pluck('category_name')->first();
                $cat_name .= $category_name . ',';
            }
            $user->category_name = $cat_name;
            $response['error'] = 0;
            $response['loggedIn'] = '1';
            $response['message'] = 'Profile found successfully..!';
            $response['vendor_data'] = $user;
        }
        echo json_encode($response);
    }

    public function updateProfile(Request $request)
    {
        $user_id = $request->vendor_id;
        $response['error'] = 1;
        $response['loggedIn'] = 'Vendor profile not found..!';
        $vendor = Vendor::where('id', $user_id)->get();
        if (count($vendor)) {
            $user = Vendor::find($user_id);
            $user->contact_person = $request->contact_person ? $request->contact_person : '';
            $user->company_email = $request->company_email ? $request->company_email : '';
            $user->email = $request->company_email ? $request->company_email : '';
            $user->company_address = $request->company_address ? $request->company_address : '';
            $user->website = $request->website ? $request->website : '';
            $user->category = $request->categories ? $request->categories : '';
            $user->save();
            $response['error'] = 0;
            $response['loggedIn'] = 'Profile Update Successfully..!';
        }
        echo json_encode($response);
    }

    public function getVendorQuoteResponse(Request $request)
    {
        $response['error'] = 1;
        $response['message'] = 'Record Not Found..!';
        $response['quote'] = $response['quote_details'] = array();
        /* $quotequery = DB::table('vendor_quotes AS v')
        ->select('v.*', 'q.product_name', 'q.product_description', 'q.product_price', 'q.product_discount', 'q.product_expirydate', 'q.product_file')
        ->join('vendor_quotes_products as q', 'v.id', '=', 'q.vendor_quote_id')
        ->where('v.quote_id', '=', $request->quote_id)
        ->where('v.vendor_id', '=', $request->vendor_id)->first(); */

        $quotequery = VendorQuote::where('quote_id', $request->quote_id)->where('vendor_id', $request->vendor_id)->first();
        if ($quotequery) {
            // $quotequery->photo_url = $quotequery->document_url = asset('public/assets/images/default.jpeg');
            $quotequery->photo_url = $quotequery->document_url = '';
            if ($quotequery->photo != '') {
                $quotequery->photo_url = asset('public/assets/images/quote-responses/' . $quotequery->photo);
            }
            if ($quotequery->document != '') {
                $quotequery->document_url = asset('public/assets/documents/quote-responses/' . $quotequery->document);
            }
            if ($additional_product = VendorQuoteProducts::where('vendor_quote_id', $quotequery->id)->get()) {
                $additional_product_details = array();
                foreach ($additional_product as $key => $value) {
                    $additional_product_details[$key] = $value;
                    $additional_product_details[$key]->product_file_url = '';
                    // $additional_product_details[$key]->product_file_url = asset('public/assets/images/default.jpeg');
                    if ($value->product_file != '') {
                        $additional_product_details[$key]->product_file_url = asset('public/assets/images/quote-responses/' . $value->product_file);
                    }
                }
            }
            $response['error'] = 0;
            $response['message'] = 'Record Found..!';
            $response['quote'] = $quotequery;
            $response['quote_details'] = $additional_product_details;
        }
        echo json_encode($response);
    }

    public function updatePaymentStatus(Request $request)
    {
        $response['error'] = 1;
        $response['message'] = 'Something went wrong please try again';
        if ($request->vendor_id) {
            $vendor = Vendor::find($request->vendor_id);
            $vendor->is_premium = 1;
            $vendor->save();

            $response['error'] = 0;
            $response['message'] = 'Congratulations..!,You are a premium vendor..!';
        }
        echo json_encode($response);
    }
}