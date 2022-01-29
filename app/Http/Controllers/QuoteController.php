<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Quotes;
use App\Customer;
use Auth;
use Session;

class QuoteController extends CustomersController {

	public function __construct() {
        $this->middleware(['auth', 'clearance'])->except('index', 'show', 'store');
    }

    public function index()
    {
        $quotes = Quotes::orderby('id', 'desc')->paginate(5);
        return view('quotes.index', compact('quotes'));
    }

    public function create()
    {
       return view('quotes.create');
    }

    public function store(Request $request)
    {

        //Validating title and body field
        $validate =  $this->validate($request, [
            'mobile'=>'required|numeric',
            'email'=>'required|email|unique:customers',
            'category'=>'required',
            'item'=>'required',
            'itemdetails'=>'required',
            'item_sample_upload'=>'required'
        ]);

        $sEmail = $request->email;
        $sCategory = $request->category;
        $sItem = $request->item;
        $sItemDetails = $request->itemdetails;

        if ($request->hasFile('item_sample_upload')) {

            $image = $request->item_sample_upload;
            $path = public_path('../public/assets/images/quotes/');
            $filename = time() . '.' . $image->getClientOriginalExtension();
            $image->move($path, $filename);

            // Attempt to log the user in
            $in_user = Customer::where('email', $request->email)->first();

            if($in_user){
                return redirect()->back()->withInput($request->only('email', 'remember'))->with('flash_message',
                    'Email already exist.');;
            }else{


                $customer = Customer::create([  'mobile' => $request->mobile,
                    'email' =>  $request->email
                ]);

                $sUserId = $customer->id;
                if ($customer) {

                    $quote = Quotes::create([
                        'userId' => $sUserId,
                        'category'=> $sCategory,
                        'item'=> $sItem,
                        'itemdetails'=> $sItemDetails,
                        'photo'=> $filename,
                        'status' => 'Quote Raised'
                    ]);

                    $this->sendSMS($request->mobile, $request->email);
                    // if successful, then redirect to their intended location
                    return redirect()->intended(url('/'))->with('flash_message',
                        'Your account has been successfully created');
                }
                // if unsuccessful, then redirect back to the login with the form data
                return redirect()->back()->withInput($request->only('emailid', 'remember'));
            }
        }else{
            echo "else block";die();
        }

        //Display a successful message upon save
        return redirect()->back()
            ->with('flash_message', 'Quote successfully Created');
    }

    public function sendSMS($sCustomerMobile, $sCustomerEmail){
        //echo $sVendorMobile;die();
        $authentication_key = '265055AdgWc9mN8W0r5c766da0';
        $sMsgToCustomer = "You have successfully registered. Please check your mail for login details. Thanks";
        $sMsgToAdmin = "New User ".$sCustomerEmail." registered";
        $sAdminMobile = "9885344485"; // "9885344485", "9642715020"
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
            \"sms\": [ { \"message\": \"$sMsgToCustomer\", \"to\": [ \"$sCustomerMobile\" ] },
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

    public function show($id)
    {
        $quote = Quotes::findOrFail($id); //Find quote of id = $id
        return view ('quotes.show', compact('quote'));
    }

    public function edit($id)
    {
        $quote = Quotes::findOrFail($id);
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

        $quote = Quotes::findOrFail($id);
		$quote->furniture_type = $request['furniture_type'];
        $quote->material_type = $request['material_type'];
        $quote->color = $request['color'];
        $quote->minPrice = $request['minPrice'];
        $quote->maxPrice = $request['maxPrice'];
        $quote->additionalDetails = $request['additionalDetails'];
        $quote->photo = $request['photo'];
        $quote->userId = $request['userId'];
        $quote->save();

        return redirect()->route('quotes.show',
            $quote->id)->with('flash_message', 'Quote updated');

    }

    public function destroy($id)
    {
        $quote = Quotes::findOrFail($id);
		$quote->delete();

        return redirect()->route('quotes.index')
            ->with('flash_message', 'Quote successfully deleted');
    }

}
