<?php
namespace app;

/**
 * class MSG91 to send SMS on Mobile Numbers.
 * @author Shashank Tiwari
 */
class MSG91
{

    public function __construct()
    {}

    private $API_KEY = '265055AdgWc9mN8W0r5c766da0';
    // private $SENDER_ID = "VERIFY";
    private $SENDER_ID = "aQuote";
    private $COUNTRY = 91;
    private $ROUTE_NO = 4;
    private $RESPONSE_TYPE = 'json';
    private $MESSAGE_TO_CUSTOMER = "You have successfully registered. Please check your mail for login details. Thanks";
    private $MESSAGE_TO_ADMIN = "";
    private $MESSAGE_TO_VENDOR = "";
    private $ADMIN_MOBILE = "9885344485"; // "9502110912"

    public function sendOTP($OTP, $sMobileNumber)
    {
        $isError = 0;
        $errorMessage = true;
        //Your message to send, Adding URL encoding.
        $message = urlencode("Welcome to Interior Quotes. Your OPT is : $OTP");

        //Preparing post parameters
        $postData = array(
            'authkey' => $this->API_KEY,
            'mobile' => $sMobileNumber,
            'message' => $message,
            'sender' => $this->SENDER_ID,
            'country' => $this->COUNTRY,
            'route' => $this->ROUTE_NO,
            'response' => $this->RESPONSE_TYPE,
        );

        $sCurlURL = "https://control.msg91.com/api/sendotp.php?authkey=$this->API_KEY&mobile=$sMobileNumber&sender=$this->SENDER_ID&country=91";

        $ch = curl_init();

        curl_setopt_array($ch, array(
            CURLOPT_URL => $sCurlURL,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => "",
        ));

        //Ignore SSL certificate verification
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

        //get response
        $output = curl_exec($ch);

        //Print error if any
        if (curl_errno($ch)) {
            $isError = true;
            $errorMessage = curl_error($ch);
        }
        curl_close($ch);
        if ($isError) {
            return array('error' => 1, 'message' => $errorMessage);
        } else {
            return array('error' => 0);
        }
    }

    public function verifyOTP($sOTP, $sMobileNumber)
    {

        $sCurlURL = "https://control.msg91.com/api/verifyRequestOTP.php?authkey=$this->API_KEY&mobile=$sMobileNumber&otp=$sOTP&country=91";

        $curl = curl_init();

        // Old Version
        curl_setopt_array($curl, array(
            CURLOPT_URL => $sCurlURL,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => "",
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_HTTPHEADER => array(
                "content-type: application/x-www-form-urlencoded",
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);
        // dd($response);
        if ($err) {
            echo "cURL Error #:" . $err;
        } else {
            return $response;
        }
    }

    public function sendSMS($sUEmail, $sUMobile, $sVEmail, $sVMobile, $sConfig, $sUserType)
    {

        $sUMessage = '';
        $sVMessage = '';
        $sMsgToAdmin = "New quote posted from user with email: " . $sUEmail . ", mobile: " . $sUMobile;

        $curl = curl_init();
        if ($sUserType == "Vendor" && $sConfig->customer_info_visible == 'public') {
            $sVMessage = "New quote posted from user with email: " . $sUEmail . ", mobile: " . $sUMobile;
        } else {
            $sVMessage = "Hi, you got new quote.";
        }

        if ($sUserType == "Customer") {
            $sUMessage = "You have successfully registered. Please check your mail for login details. Thanks,\n Interior Quotes";
        }

        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://api.msg91.com/api/v2/sendsms",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => "{ \"sender\": \"SOCKET\", \"route\": \"4\", \"country\": \"91\",
            \"sms\": [ { \"message\": \"$sUMessage\", \"to\": [ \"$sUMobile\" ] },
            { \"message\": \"$sVMessage\", \"to\": [ \"$sVMobile\" ] },
            { \"message\": \"$sMsgToAdmin\", \"to\": [ \"$this->ADMIN_MOBILE\" ] } ] }",
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_HTTPHEADER => array(
                "authkey: $this->API_KEY",
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

    public function sendQuoteSMSOld($sUEmail, $sUMobile, $sVEmail, $sVMobile, $sConfig, $sUserType, $message_body)
    {
        // dd($message_body);
        //Your authentication key
        $authKey = $this->API_KEY;
        //Multiple mobiles numbers separated by comma
        // $mobileNumber = '9687311505';
        $mobileNumber = $sVMobile;
        //Sender ID,While using route4 sender id should be 6 characters long.
        $senderId = 'aQuote';
        //Your message to send, Add URL encoding here.
        $message = urlencode($message_body);
        // dd($message);
        //Define route
        $route = $this->ROUTE_NO;
        //Prepare you post parameters
        $postData = array(
            'authkey' => $authKey,
            'mobiles' => $mobileNumber,
            'message' => $message,
            'sender' => $senderId,
            'route' => $route,
        );

        //API URL
        $url = "https://api.msg91.com/api/v2/sendsms";

        // init the resource
        $ch = curl_init();
        curl_setopt_array($ch, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $postData,
            //,CURLOPT_FOLLOWLOCATION => true
        ));

        //Ignore SSL certificate verification
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

        //get response
        $output = curl_exec($ch);
        // dd($output);
        //Print error if any
        if (curl_errno($ch)) {
            $isError = true;
            $errorMessage = curl_error($ch);
        }
        curl_close($ch);
        if (isset($isError)) {
            return array('error' => 1, 'message' => $errorMessage);
        } else {
            return array('error' => 0);
        }
    }

    public function sendQuoteSMS($vendor_json)
    {
// "flow_id":"5f8a68fa15fb4b72da0ad09e",
        $post_fields = '{
            "flow_id":"VendorQuoteRequestNew",
             "recipients" : ' . $vendor_json . '
        }';

        // dd($post_fields);
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://api.msg91.com/api/v5/flow/",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $post_fields,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_HTTPHEADER => array(
                "authkey: " . $this->API_KEY,
                "content-type: application/json",
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);
        return true;
        /* if ($err) {
        echo "cURL Error #:" . $err;
        } else {
        echo $response;
        }  */
        // dd($response);
    }

    public function sendQuoteOtp($OTP, $sMobileNumber)
    {
        $message_body = "Welcome to AnyQuote. Your OTP is : $OTP";
        // dd($message_body);
        //Your authentication key
        $authKey = $this->API_KEY;
        //Multiple mobiles numbers separated by comma
        // $mobileNumber = '9687311505';
        $mobileNumber = $sMobileNumber;
        //Sender ID,While using route4 sender id should be 6 characters long.
        // $senderId = 'aQuote';
        $senderId = $this->SENDER_ID;
        //Your message to send, Add URL encoding here.
        $message = urlencode($message_body);
        // dd($message);
        //Define route
        $route = $this->ROUTE_NO;
        //Prepare you post parameters
        $postData = array(
            'authkey' => $authKey,
            'mobiles' => $mobileNumber,
            'message' => $message,
            'sender' => $senderId,
            'route' => $route,
        );

        //API URL
        $url = "https://api.msg91.com/api/v2/sendsms";

        // init the resource
        $ch = curl_init();
        curl_setopt_array($ch, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $postData,
            //,CURLOPT_FOLLOWLOCATION => true
        ));

        //Ignore SSL certificate verification
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

        //get response
        $output = curl_exec($ch);
        //Print error if any
        if (curl_errno($ch)) {
            $isError = true;
            $errorMessage = curl_error($ch);
        }
        curl_close($ch);
        if (isset($isError)) {
            return array('error' => 1, 'message' => $errorMessage);
        } else {
            return array('error' => 0);
        }
    }
}