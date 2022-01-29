<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\User;
use App\Vendor;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Spatie\Permission\Traits\HasRoles;

class UsersController extends Controller
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
      if($request->type == 'M')
      {
        $this->validate($request, [
       'mobile' => 'required',
       'password' => 'required'
        ]);
      }else if($request->type == 'E')
      {
        $this->validate($request, [
          'email' => 'required',
         'password' => 'required'
       ]);
      }
      $apikey = base64_encode(str_random(40));
      if($request->type == 'M')
      {
        $user = User::where('mobile', $request->mobile)->where('password',bcrypt($request->password))->first();
        if(!empty($user))
        {
          User::where('mobile', $request->mobile)->update(['api_key' => "$apikey"]);;
          return response()->json(array('status'=>true,'api_key' => $apikey, 'message'=>'Login Successful', 'status_code'=> 200));
        }else
        {
          return response()->json(array('status'=>false, 'message'=>'Login Failed', 'status_code'=> 400));
        }
      }else if($request->type == 'E')
      {
        $user = User::where('email', $request->email)->first();
        if(Hash::check($request->password, $user->password)){
          $apikey = base64_encode(str_random(40));
          User::where('email', $request->email)->update(['api_key' => "$apikey"]);;
         return response()->json(array('status'=>true, 'user_id' => $user->id, 'api_key' => $apikey, 'message'=>'Login Successful', 'status_code'=> 200));
      }else{
          return response()->json(array('status'=>false, 'message'=>'Login Failed', 'status_code'=> 400));
      }
      }
      
   }
  public function logout(Request $request)
  {
    $mobile_no = $request->mobile_no;
    $is_vendor = $request->is_vendor;
    if($is_vendor == 1) {
      $vendors = Vendor::where('mobile',$mobile_no)->get();
    } else {
      $vendors = User::where('mobile',$mobile_no)->get();
    }
    foreach($vendors as $vendor) {
      $vendor->device_token = null;
      $vendor->save();
    }
    return response()->json(array('status'=>true, 'message'=>'Logout Successfully', 'status_code'=> 200));
  }



  public function register(Request $request)
   {
    
        $validMsg = $this->validate($request, [
        'username' => 'required|string|max:255',
        'email' => 'required|string|email|max:255|unique:users',
        'mobile' => 'required|numeric|min:10|unique:users',
        'password' => 'required|string|min:6',
        ]);
        $user = User::create([
            'username' => $request->username,
            'email' => $request->email,
            'mobile' => $request->mobile,
            'password' => bcrypt($request->password),
        ]);
         if($user)
         {
          return response()->json(array('status'=>true, 'message'=>'Registration Successfully', 'status_code'=> 200));
         }else
         {
          return response()->json(array('status'=>false, 'message'=>'Registration failed', 'status_code'=> 400));
         }
 
   }

   public function sendOtp(Request $request)
   {
      if($request->mobile == '')
      {
        return response()->json(array('status'=>false, 'message'=>'Please provied mobile number', 'status_code'=> 400));
      }
      $otp =  (string)rand(1000,9999);
      $senderid = 'IQSMS';
      $message = 'Your Otp is '.$otp;
      $authkey = '265055AdgWc9mN8W0r5c766da0';
      $checkMobile = User::where('mobile',$request->mobile)->first();
      if($request->mobile != '' && !empty($checkMobile))
      {
        $mobile = $request->mobile;
        $result = json_decode($this->getOtp($otp, $senderid, $message, $mobile, $authkey));
        if($result->type == 'success')
        {
          $user = User::where('id',$checkMobile->id)->first();
          $user->update(['otp'=>$otp]);
          return response()->json(array('status'=>true, 'message'=>'Otp message send Successfully', 'status_code'=> 200));
        }
        

      }else
      {
        return response()->json(array('status'=>false, 'message'=>'Mobile Number is Not register', 'status_code'=> 400));
      }
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
      return  $err;
    } else {
      return  $response;
    }
   }
}    
?>