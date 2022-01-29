<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Quotes;
use App\Vendor;
use App\User;
use App\VendorQuote;
use DB;
use App\QuoteTracking;
use Auth;
use Session;
use Illuminate\Support\Facades\Crypt;

class QuoteController extends Controller{

	public function __construct() {
        //$this->middleware(['auth', 'isAdmin']);
    }

    public function quoteRequests()
    {
        $vendors = Vendor::all();
        //$quotes = Quote::orderby('id', 'desc')->get();
		$quotes = DB::table('quotes')->leftjoin("categories","quotes.category","=","categories.id")
			->select("quotes.*","categories.category_name")
			->get();
		
		
		//$quotesdata = new \stdClass();
 
		if(count($quotes)>0){
			foreach($quotes as $key => $quote){
				$cntquoteresponses = DB::table('vendor_quotes')->select("count('vendor_quotes.id')")->where('vendor_quotes.quote_id', '=', $quote->id)->where('vendor_quotes.isResponded', '=', 1)->groupBy("vendor_quotes.quote_id")->count();
				$quotes[$key]->count_click = $cntquoteresponses; 
			}
		}		
        return view('admin.quotes.quote-requests', compact('quotes','vendors'));
    }
	
	
	/**
     * Display the the Home page.
     *
     * @return Response
     */
    public function quoteResponses($quote_id){
        $userdata = Auth::user();
		//echo $userdata->id; exit;
		
		$quotedata = Quotes::where('id', '=', $quote_id)->first();
		
		$quoterequests = VendorQuote::leftjoin('vendors','vendors.id','=','vendor_quotes.vendor_id')
			->select('vendor_quotes.created_at as response_created_at','vendor_quotes.id as vendor_quote_id','vendor_quotes.quote_response','vendors.*')
			->where('vendor_quotes.isResponded', '=', 1)
			->where('quote_id', '=', $quote_id)
			->orderBy('vendor_quotes.id', 'DESC')
			->get();
			
		//echo "<pre>"; print_r($quotedata); echo "</pre>";	 exit;
			
		return view("admin/quotes/quote-responses",['quoterequests'=>$quoterequests,'quotedata'=>$quotedata]);
    }
	
	
	public function view_sent_quote($vendor_quote_id, Request $request)
    {
		$vendorquote = VendorQuote::join('vendors','vendors.id','=','vendor_quotes.vendor_id')->join('quotes','quotes.id','=','vendor_quotes.quote_id')->select('vendors.*','vendor_quotes.*','quotes.*','vendor_quotes.id as vendor_quote_id','vendor_quotes.created_at as vendorquote_created_at','quotes.created_at as quote_created_at')->where('vendor_quotes.id',$vendor_quote_id)->first();
		//echo "<pre"print_r($vendorquote); exit;
		
		return view('admin/quotes/view-send-quote-response', compact('vendorquote'));
	}
	

    public function create()
    {
        return view('quotes.create');
    }

	//Create New Quote from Admin
    public function store(Request $request)
    {
        $this->validate($request, [
            'furniture_type'=>'required',
			'material_type'=>'required',
			'color'=>'required',
			'minPrice'=>'required',
			'maxPrice'=>'required',
			'additionalDetails'=>'required',
            'photo'=>'required'
        ]);

        if ($request->hasFile('photo')) {
            $image = $request->photo;
            $path = public_path('../public/assets/images/quotes/');
            $filename = time() . '.' . $image->getClientOriginalExtension();
            $image->move($path, $filename);

            $quote = Quote::create([
                'furniture_type' => $request->furniture_type,
                'material_type' => $request->material_type,
                'color' => $request->color,
                'minPrice' => $request->minPrice,
            	'maxPrice' => $request->maxPrice,
            	'additionalDetails' => $request->additionalDetails,
            	'photo' =>  $filename,
                'cat_type' =>  $request->cat_type,
                'status' => 'Quote Raised',
            	'userId' => Auth:: user()->id
            ]);
        }

        //Display a successful message upon save
        return redirect()->route('admin.quotes')
            ->with('flash_message', 'Quote created successfully..!');
    }

    //edit and get quote function
    public function editquote($quote_id)
    {
        //echo "quote id :".$quote_id;die();
        $quote = Quotes::findOrFail($quote_id);
        return view('admin/quotes/edit-quote', compact('quote'));
    }

    //update quote function
    public function updatequote(Request $request)
    {
        $this->validate($request, [
            'furniture_type'=>'required',
            'material_type'=>'required',
            'color'=>'required',
            'minPrice'=>'required',
            'maxPrice'=>'required',
            'additionalDetails'=>'required'
        ]);

        if ($request->hasFile('photo')) {
            $image = $request->photo;
            $path = public_path('../public/assets/images/quotes/');
            $filename2 = time() . '.' . $image->getClientOriginalExtension();
            $image->move($path, $filename2);
            $filename = $filename2;
        }else{
            $filename = $request['photo'];
        }

        $id = $request['quote_id'];
        $quote = Quote::findOrFail($id);
        $quote->furniture_type = $request['furniture_type'];
        $quote->material_type = $request['material_type'];
        $quote->color = $request['color'];
        $quote->minPrice = $request['minPrice'];
        $quote->maxPrice = $request['maxPrice'];
        $quote->additionalDetails = $request['additionalDetails'];
        $quote->cat_type = $request['cat_type'];
        $quote->photo =  $filename;
        $quote->update();

        return redirect()->route('admin.quotes')
            ->with('flash_message', 'Quote Updated successfully..!');
    }

    public function show($id)
    {
        $quote = Quote::findOrFail($id);
        return view ('quotes.show', compact('quote'));
    }

    public function edit($id)
    {
        $quote = Quote::findOrFail($id);
        return view('quotes.edit', compact('quote'));
    }

    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'furniture_type'=>'required',
			'material_type'=>'required',
			'color'=>'required',
			'minPrice'=>'required',
			'maxPrice'=>'required',
			'additionalDetails'=>'required',
			'photo'=>'required',
			'userId'=>'required'
        ]);

        $quote = Quote::findOrFail($id);
		$quote->furniture_type = $request['furniture_type'];
        $quote->material_type = $request['material_type'];
        $quote->color = $request['color'];
        $quote->minPrice = $request['minPrice'];
        $quote->maxPrice = $request['maxPrice'];
        $quote->additionalDetails = $request['additionalDetails'];
        $quote->photo = $request['photo'];
        $quote->userId = $request['userId'];
        $quote->save();

        return redirect()->route('quotes.show', $quote->id)
            ->with('flash_message', 'Quote updated');
    }

    public function destroy($id)
    {
        $quote = Quote::findOrFail($id);
		$quote->delete();

        return redirect()->route('quotes.index')
            ->with('flash_message', 'Quote successfully deleted');
    }

    //send quote page
    public function send_quote($quote_id)
    {
        $quote = Quote::findOrFail($quote_id);
        $vendors = Vendor::all();
        $filtervendors = array();

        return view('admin.vendors.send-quote-vendor', compact('quote', 'vendors', 'filtervendors'));
    }

    //send quote information
    public function send_quote_information(Request $request)
    {
        $quote_id =  $request->quote_id;
        $quote = Quote::findOrFail($quote_id);

        $filtervendors = Vendor::Where('furniture_type', $request->furniture_type)
                        ->orWhere('material_type', $request->material_type)
                        ->orWhere('cat_type', $request->cat_type)
                        ->orWhere('city', $request->city)
                        ->get();

        return view('admin.vendors.send-quote-vendor', compact('quote', 'filtervendors'));
    }

    //send quote information to vendor
    public function send_vendor_mesg(Request $request)
    {
        $vendor_ids = $request['vendor_id'];
        $quote_id = $request->input('quote_id');
        $quote = Quote::findOrFail($quote_id);
        $customer_id = $quote->userId;
        $data['quote_id'] = $quote_id;
        $data['status'] = "Quote Requested";
        $data['customer_id'] = $customer_id;
        $senderid = 'IQRSMS';
        $authkey = '265055AdgWc9mN8W0r5c766da0';

		foreach($vendor_ids as $vendor_id){
            $data['vendor_id'] = $vendor_id;
            $Vendor = Vendor::findOrFail($vendor_id);
            $cparameter= Crypt::encrypt($Vendor->user_id);
            // $message = "You Have Recived New Quote Request URL: http://avedemos.uk/interiorquotesv2/public/";
            $message = "Hi $Vendor->username, You Have Recived New Quote Request URL: http://avedemos.uk/interiorquotesv2/public/vendor_quote_form/$quote_id/$cparameter";
            $mobile = $Vendor->mobile;
            $result = $this->getMsg( $senderid, $message, $mobile, $authkey);

            if(isset($result))
            {
                QuoteTracking::create($data);
                $quote->status = 'Quote Requested';
                $quote->update();
            }
        }

        return redirect()->route('admin.quotes.tracking')
            ->with('flash_message', 'Quote requested successfully');
    }

    //Quotes tracking page
    public function quotes_tracking()
    {
        $tracking_quotes = QuoteTracking::with(['Vendor','Customer','Quote'])->get();
        return view('admin.quotes.quotes-tracking', compact('tracking_quotes'));
    }

    public function getMsg($senderid, $message, $mobile, $authkey)
    {
        $url =  "https://api.msg91.com/api/sendhttp.php?mobiles=$mobile&authkey=$authkey&route=4&sender=$senderid&message=$message&country=91";
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

}
