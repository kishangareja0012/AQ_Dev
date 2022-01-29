<?php

namespace App\Console\Commands;
use App\User;
use App\VendorQuote;
use App\Quotes;
use DB;

use Illuminate\Console\Command;

class userSendMail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'userSendMail:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send responded quote mail to every user who registered with email id.';

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
        $current_time = date('Y-m-d H:i:s',strtotime(date('Y-m-d H:i:s')));
        $cheking_time = date('Y-m-d H:i:s',strtotime('-4 hour',strtotime(date('Y-m-d H:i:s'))));
        $result = DB::table('vendor_quotes as v')
        ->leftJoin('users as u', 'v.user_id', '=', 'u.id')
        ->select('v.*', 'u.email',DB::raw('min(v.price) as min_price'),DB::raw('max(v.price) as max_price'),DB::raw('count(v.quote_id) as count_responded'))
        ->where('u.login_method','Email')
        ->where('v.isResponded',1)
        ->where('v.responded_at', '>=', $cheking_time)
        ->where('v.responded_at', '<=', $current_time)
        ->groupBy('v.quote_id')
        ->get()
        ->toArray();
        foreach ($result as $key => $value) {
            $data_array = (array) $value;
            Mail::send('emails.quote_responded', $data_array, function ($message) use ($data_array) {
                $message->to($data_array['email'])->from('support@anyquote.in', 'From AnyQuote')->subject("AnyQuote Responded Quote");
            });
        }
        $this->info('Mail sent to all users');
    }
}
