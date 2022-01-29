<?php

namespace App\Http\Controllers\Api;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Products;
use App\Quotes;
use App\Customer;
use App\VendorQuote;


class QuoteController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
      public function postQuote(Request $request)
    {
		
		$image = $request->photo;
		$quote_photo = time().'.jpeg';
		$path = "assets/images/quotes/".$quote_photo;
		file_put_contents($path,base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $image)));
		$quote = Quotes::create([
            'furniture_type' => $request->furnitureType,
            'material_type' => $request->materialType,
            'color' => $request->color,
            'minPrice' => $request->minPrice,
			'maxPrice' => $request->maxPrice,
			'additionalDetails' => $request->additionalDetails,
			'photo' => $quote_photo,
			'cat_type' => $request->cat_type,
			'userId' => $request->user_id,
            'status' => 'Quote Raised'
        ]);
		if(!empty($quote))
        {
          return response()->json(array('status'=>true, 
          'message'=> 'Quote Request placed successfully',
		  'status_code'=> 200));
        }else
        {
          return response()->json(array('status'=>false, 'message'=>'Unable to place Quote Request. Try again', 'status_code'=> 400));
        }
       
    }
   public function getQuotes(Request $request)
    {
	   /*echo "<pre>";
	   print_r($request->user_id);
	   die;*/
       // $quotes = Quotes::all()->where('userId',$request->user_id);
	   DB::enableQueryLog();
	   
	   $quotes = Quotes::where('userId', $request->user_id)->get();
	   $query = DB::getQueryLog();
	  /* print_r($query);
		exit;*/
        $response = [
            'Quotes' => $quotes
        ];
        return response()->json(array('status'=>true, 
		  'data' => $quotes,
		  'status_code'=> 200));
    }
	public function myvendorQuotes(Request $request)
	{
		$quote_id = $request->quote_id;
		$user_id = $request->user_id;
		$quote = Quotes::where('id',$quote_id)->where('userId',$user_id)->first();
		$customer = Customer::where('user_id',$user_id)->first();
		if(isset($quote)){
		   $vendor_quotes = VendorQuote::with('Vendor')->where('quote_id',$quote_id)->get();
		   if(isset($vendor_quotes)&&count($vendor_quotes)>0){
			   $customer = Customer::where('user_id',$quote->userId)->first();
		   $data = array('customer'=>$customer,'quote'=>$quote,'vendor_quotes'=> $vendor_quotes,);
			   	return response()->json(array('status'=>true, 'data'=>$vendor_quotes,'status_code'=> 200));
		   }else{
			   return response()->json(array('status'=>false, 'message'=>'Unable to find vendor Quotes', 'status_code'=> 400));
			}
        }else
        {
          return response()->json(array('status'=>false, 'message'=>'Unable to find Quote', 'status_code'=> 400));
        }
	}
   
}
