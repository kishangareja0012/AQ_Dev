<?php

namespace App\Console\Commands;
use App\User;
use App\VendorQuote;
use App\Quotes;
use DB;

use Illuminate\Console\Command;

class userNotification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'userNotification:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send Pushnotification to every user';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        // \Log::info("Cron is working fine!");
        $current_time = date('Y-m-d H:i:s',strtotime(date('Y-m-d H:i:s')));
        $cheking_time = date('Y-m-d H:i:s',strtotime('-4 hour',strtotime(date('Y-m-d H:i:s'))));
        $users = User::leftJoin('quotes','users.id','=','quotes.user_id')
        ->select('users.id as u_id','users.device_token','quotes.*')
        ->where('users.device_token','!=','')
        ->where('quotes.user_id','!=','')
        ->get();
        // dd($users);
        $quotes = $quote_data = array();
        if (count($users)) {
            foreach ($users as $key => $value) {
                // $quotes[$key] = $quotes[$key]->quote_data = $quote_data;
                /* echo  "SELECT * from `vendor_quotes` where `quote_id` = ".$value->id." AND `user_id` = ".$value->user_id." AND `status` = `Responded` AND `isResponded` = 1 AND `responded_at` >= $cheking_time AND `responded_at`<= $cheking_time";
                echo  "<br>"; */
                $quote_data = VendorQuote::where('quote_id','=',$value->id)
                ->where('user_id',$value->user_id)
                ->where('status','Responded')
                ->where('isResponded',1)
                ->where('responded_at', '>=', $cheking_time)
                ->where('responded_at', '<=', $current_time)
                ->get();
                // ->toSql();
                foreach ($quote_data as $qkey => $quote_value) {
                    $quotes[$key] = $value;
                    $quotes[$key]->quote_data = $quote_value;
                    $quotes[$key]->count_responded = $count_responded = DB::table('vendor_quotes')->select("count('vendor_quotes.id')")->where('vendor_quotes.quote_id', '=', $value->id)->where('vendor_quotes.isResponded', '=', 1)->groupBy("vendor_quotes.quote_id")->count();
                    $quotes[$key]->min_price = $min_price = DB::table('vendor_quotes')->where('vendor_quotes.quote_id', '=', $value->id)->groupBy("vendor_quotes.quote_id")->min('vendor_quotes.price');
                    $quotes[$key]->max_price = $max_price = DB::table('vendor_quotes')->where('vendor_quotes.quote_id', '=', $value->id)->groupBy("vendor_quotes.quote_id")->max('vendor_quotes.price');
                    $this->sendPushNotification("AnyQuote", "Dear user you got total ".$count_responded." respond from vendor and minimu price is ".$min_price." and maximum price is ".$max_price.".", $value->device_token,'');
                }                
            }
        }
        $this->info('Notification Sent Successfully.');
        // return $quotes;
    }

    /**
     * Date : 27-11-2020, Friday
     */
    public function sendPushNotification($title, $description, $device_token, $image = '')
    {
        $url = 'https://fcm.googleapis.com/fcm/send';
        $registration_ids = $device_token;
        $message = array(
            "title" => $title,
            "description" => $description,
            "image" => "",
        );
        $extraNotificationData = ["message" => $message,"moredata" =>''];
        $fields = array(
            'to' => $registration_ids,
            'notification' => $message,
            // 'data' => $extraNotificationData,
        );
        
        $headers = array(
            'Authorization:key=' . GOOGLE_API_KEY,
            'Content-Type: application/json',
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));

        $result = curl_exec($ch);
        if ($result === false) {
            die('Curl failed ' . curl_error());
        }

        curl_close($ch);
        // dd($result);
        return $result;
        ob_flush();
    }
}
