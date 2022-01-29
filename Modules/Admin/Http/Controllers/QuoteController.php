<?php

namespace Modules\Admin\Http\Controllers;

use App\Category;
use App\Http\Controllers\Controller;
use App\Quotes;
use App\QuoteTracking;
use App\User;
use App\Vendor;
use App\VendorQuote;
use App\VendorQuoteProducts;
use Auth;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class QuoteController extends Controller
{
    public function __construct()
    {
        //$this->middleware(['auth', 'isAdmin']);
    }

    public function quoteRequests(Request $request)
    {
        /* if ($request->ajax()) {
        $quotes = DB::table('quotes')->leftjoin("categories", "quotes.category", "=", "categories.id")
        ->leftjoin('users', 'users.id', '=', 'quotes.user_id')
        ->select('users.name as customer_name', 'users.mobile as customer_mobile', 'users.email as customer_email', "quotes.*", "categories.category_name")
        ->orderBy('quotes.created_at', 'DESC')
        ->get();

        if (count($quotes)>0) {
        foreach ($quotes as $key => $quote) {
        $quotes[$key]->count_sent = DB::table('vendor_quotes')->select("count('vendor_quotes.id')")->where('vendor_quotes.quote_id', '=', $quote->id)->groupBy("vendor_quotes.quote_id")->count();
        $quotes[$key]->count_responded = DB::table('vendor_quotes')->select("count('vendor_quotes.id')")->where('vendor_quotes.quote_id', '=', $quote->id)->where('vendor_quotes.isResponded', '=', 1)->groupBy("vendor_quotes.quote_id")->count();
        }
        }
        // dd($quotes);
        return Datatables::of($quotes)
        ->addColumn('photo', function ($quotes) {
        return '<img class="myImg" height= "50" width = "50" src="'.asset("public/assets/images/quotes/".$quotes->item_sample).'" alt="image"/>';
        })->rawColumns(['photo'])
        ->make(true);
        }

        return view('admin.quotes.quote-requests');
         */

        // $vendors = Vendor::all();
        // $quotes = DB::table('quotes')->leftjoin("categories", "quotes.category", "=", "categories.id")
        // ->leftjoin('users', 'users.id', '=', 'quotes.user_id')
        // ->select('users.name as customer_name', 'users.mobile as customer_mobile', 'users.email as customer_email', "quotes.*", "categories.category_name")
        // ->orderBy('quotes.created_at', 'DESC')
        // ->get();

        // if (count($quotes)>0) {
        //     foreach ($quotes as $key => $quote) {
        //         $quotes[$key]->count_sent = DB::table('vendor_quotes')->select("count('vendor_quotes.id')")->where('vendor_quotes.quote_id', '=', $quote->id)->groupBy("vendor_quotes.quote_id")->count();
        //         $quotes[$key]->count_responded = DB::table('vendor_quotes')->select("count('vendor_quotes.id')")->where('vendor_quotes.quote_id', '=', $quote->id)->where('vendor_quotes.isResponded', '=', 1)->groupBy("vendor_quotes.quote_id")->count();
        //     }
        // }
        return view('admin.quotes.quote-requests');
    }

    public function ajaxQuoteRequests(Request $request)
    {

        ## Read value
        $draw = $request->get('draw');
        $start = $request->get("start");
        $rowperpage = $request->get("length"); // Rows display per page

        $columnIndex_arr = $request->get('order');
        $columnName_arr = $request->get('columns');
        $order_arr = $request->get('order');
        $search_arr = $request->get('search');

        $columnIndex = $columnIndex_arr[0]['column']; // Column index
        $columnName = $columnName_arr[$columnIndex]['data']; // Column name
        $columnSortOrder = $order_arr[0]['dir']; // asc or desc
        $searchValue = $search_arr['value']; // Search value

        $from_date = $request->get('searchByFromdate');
        $to_date = $request->get('searchByTodate');
       
        // Total records
        $quotes = DB::table('quotes')->leftjoin("categories", "quotes.category", "=", "categories.id")
            ->leftjoin('users', 'users.id', '=', 'quotes.user_id')
            ->select('users.name as customer_name', 'users.mobile as customer_mobile', 'users.email as customer_email', "quotes.*", "categories.category_name")
            ->orderBy('quotes.created_at', 'DESC')
            ->where('quotes.category','!=','1')
            ->whereDate('quotes.created_at','>=' ,$from_date)
            ->whereDate('quotes.created_at','<=' , $to_date)
            ->get();

        /* if(count($quotes)>0){
        foreach($quotes as $key => $quote){
        $quotes[$key]->count_sent = DB::table('vendor_quotes')->select("count('vendor_quotes.id')")->where('vendor_quotes.quote_id', '=', $quote->id)->groupBy("vendor_quotes.quote_id")->count();
        $quotes[$key]->count_responded = DB::table('vendor_quotes')->select("count('vendor_quotes.id')")->where('vendor_quotes.quote_id', '=', $quote->id)->where('vendor_quotes.isResponded', '=', 1)->groupBy("vendor_quotes.quote_id")->count();
        }
        }*/
        $totalRecords = count($quotes);

        //filtered record
        $quotes = DB::table('quotes')->leftjoin("categories", "quotes.category", "=", "categories.id")
            ->leftjoin('users', 'users.id', '=', 'quotes.user_id')
            ->select('users.name as customer_name', 'users.mobile as customer_mobile', 'users.email as customer_email', "quotes.*", "categories.category_name")
            ->where('quotes.category','!=','1')
            ->whereDate('quotes.created_at','>=' ,$from_date)
                ->whereDate('quotes.created_at','<=' , $to_date)
            ->where(function ($query) use ($searchValue) {
                $query->where('quotes.id', '=', $searchValue)
                    ->orWhere('quotes.item', 'like', '%' . $searchValue . '%')
                    ->orWhere('quotes.location', 'like', '%' . $searchValue . '%')
                    ->orWhere('users.name', 'like', '%' . $searchValue . '%')
                    ->orWhere('users.mobile', 'like', '%' . $searchValue . '%')
                    ->orWhere('users.email', 'like', '%' . $searchValue . '%')
                    ->orWhere('categories.category_name', 'like', '%' . $searchValue . '%');
            })
            ->get();
            // ->toSql();
        /*if(count($quotes)>0){
        foreach($quotes as $key => $quote){
        $quotes[$key]->count_sent = DB::table('vendor_quotes')->select("count('vendor_quotes.id')")->where('vendor_quotes.quote_id', '=', $quote->id)->groupBy("vendor_quotes.quote_id")->count();
        $quotes[$key]->count_responded = DB::table('vendor_quotes')->select("count('vendor_quotes.id')")->where('vendor_quotes.quote_id', '=', $quote->id)->where('vendor_quotes.isResponded', '=', 1)->groupBy("vendor_quotes.quote_id")->count();
        }
        }*/
        $totalRecordswithFilter = count($quotes);

        // Fetch records
        if ($columnName == "quote_request") {
            $columnName = "quotes.item";
        } elseif ($columnName == "customer_info") {
            $columnName = "users.name";
        } elseif ($columnName == "category_name") {
            $columnName = "categories.category_name";
        } elseif ($columnName == "location") {
            $columnName = "quotes.location";
        } elseif ($columnName == "created_at") {
            $columnName = "quotes.created_at";
        } else {
            $columnName = "quotes.created_at";
        }
            $quotes = DB::table('quotes')->leftjoin("categories", "quotes.category", "=", "categories.id")
                ->leftjoin('users', 'users.id', '=', 'quotes.user_id')
                ->select('users.name as customer_name', 'users.mobile as customer_mobile', 'users.email as customer_email', "quotes.*", "categories.category_name")
                ->where('quotes.category','!=','1')
                ->whereDate('quotes.created_at','>=' ,$from_date)
                ->whereDate('quotes.created_at','<=' , $to_date)
                ->where(function($query) use ($searchValue){
                    $query->where('quotes.item', 'like', '%' . $searchValue . '%')
                    ->orWhere('quotes.id', '=', $searchValue)
                    ->orWhere('quotes.location', 'like', '%' . $searchValue . '%')
                    ->orWhere('users.name', 'like', '%' . $searchValue . '%')
                    ->orWhere('users.mobile', 'like', '%' . $searchValue . '%')
                    ->orWhere('users.email', 'like', '%' . $searchValue . '%')
                    ->orWhere('categories.category_name', 'like', '%' . $searchValue . '%');
                })            
                ->orderBy($columnName, $columnSortOrder)
                ->skip($start)
                ->take($rowperpage)
                ->get();
       


        if (count($quotes) > 0) {
            foreach ($quotes as $key => $quote) {
                $quotes[$key]->count_sent = DB::table('vendor_quotes')->select("count('vendor_quotes.id')")->where('vendor_quotes.quote_id', '=', $quote->id)->groupBy("vendor_quotes.quote_id")->count();
                $quotes[$key]->count_responded = DB::table('vendor_quotes')->select("count('vendor_quotes.id')")->where('vendor_quotes.quote_id', '=', $quote->id)->where('vendor_quotes.isResponded', '=', 1)->groupBy("vendor_quotes.quote_id")->count();
            }
        }
        $records = $quotes;
        $data_arr = array();
        foreach ($records as $record) {
                $id = $record->id;
                $photo = '<img class="myImg" height= "50" width = "50" src="' . asset("public/assets/images/quotes/" . $record->item_sample) . '" alt="image"/>';
                $quote_request = '<b>' . $record->item . '</b> <br>' . $record->item_description . '<br><i class="fa fa-calendar"></i> ' . date('d-M-Y h:i a', strtotime($record->created_at));
                $customer_info = '<strong> ' . ucfirst($record->customer_name) . '</strong><br><strong>Phone :</strong> ' . $record->customer_mobile . ' <br><strong>Email :</strong> ' . $record->customer_email . ' <br>';
                $category_name = $record->category_name;
                $location = $record->location;
                $created_at = date('d-M-Y h:i a', strtotime($record->created_at));
                $response = '<a href="' . url('admin/quote-sent-vendors/' . $record->id) . '" class="btn btn-sm btn-info"><span class="badge">' . $record->count_sent . '</span>&nbsp;Sent&nbsp;<i class="fa fa-sign-in" aria-hidden="true"></i></a><a href="' . url('admin/quote-responses/' . $record->id) . '" class="btn btn-sm btn-success"><span class="badge">' . $record->count_responded . '</span>&nbsp;<i class="fa fa-reply-all" aria-hidden="true"></i>&nbsp;Responded</a>';
                $action = '<a href="javascript:;" onclick="editItem(' . $record->id . ')" class="btn btn-sm btn-primary tooltips editQuote" data-placement="top" data-original-title="Edit"><i class="fa fa-edit"></i></a>
       <a href="javascript:;" onclick="deleteItem(' . $record->id . ')" class="delete btn btn-sm btn-danger" ><i class="fa fa-times fa fa-white"></i></a>';

                $data_arr[] = array(
                    "id" => $id,
                    "photo" => $photo,
                    "quote_request" => $quote_request,
                    "customer_info" => $customer_info,
                    "category_name" => $category_name,
                    "location" => $location,
                    "created_at" => $created_at,
                    "response" => $response,
                    "action" => $action,
                );
        }

        $response = array(
            "draw" => intval($draw),
            "iTotalRecords" => $totalRecords,
            "iTotalDisplayRecords" => $totalRecordswithFilter,
            "aaData" => $data_arr,
        );

        echo json_encode($response);
        exit;
    }

    public function miscQuoteRequests()
    {

        // $vendors = Vendor::all();
        $vendors = '';
        $quotes = DB::table('quotes')->leftjoin("categories", "quotes.category", "=", "categories.id")
            ->leftjoin('users', 'users.id', '=', 'quotes.user_id')
            ->select('users.name as customer_name', 'users.mobile as customer_mobile', 'users.email as customer_email', "quotes.*", "categories.category_name")
            ->where('categories.category_name', '=', "Miscellaneous")
        // ->orderBy('quotes.id', 'DESC')
            ->orderBy('quotes.created_at', 'DESC')
            ->get();

        if (count($quotes) > 0) {
            foreach ($quotes as $key => $quote) {
                $quotes[$key]->count_sent = DB::table('vendor_quotes')->select("count('vendor_quotes.id')")->where('vendor_quotes.quote_id', '=', $quote->id)->groupBy("vendor_quotes.quote_id")->count();
                $quotes[$key]->count_responded = DB::table('vendor_quotes')->select("count('vendor_quotes.id')")->where('vendor_quotes.quote_id', '=', $quote->id)->where('vendor_quotes.isResponded', '=', 1)->groupBy("vendor_quotes.quote_id")->count();
            }
        }
        // dd($quotes);
        return view('admin.quotes.misc-quote-requests');
    }

    /*
    AJAX request
     */
    public function get_misc_quote_list(Request $request)
    {
        ## Read value
        $draw = $request->get('draw');
        $start = $request->get("start");
        $rowperpage = $request->get("length"); // Rows display per page

        $columnIndex_arr = $request->get('order');
        $columnName_arr = $request->get('columns');
        $order_arr = $request->get('order');
        $search_arr = $request->get('search');

        $columnIndex = $columnIndex_arr[0]['column']; // Column index
        $columnName = $columnName_arr[$columnIndex]['data']; // Column name
        $columnSortOrder = $order_arr[0]['dir']; // asc or desc
        $searchValue = $search_arr['value']; // Search value
        $from_date = $request->get('searchByFromdate');
        $to_date = $request->get('searchByTodate');

        // dd($request);
        // Total records
        $quotes = DB::table('quotes')->leftjoin("categories", "quotes.category", "=", "categories.id")
            ->leftjoin('users', 'users.id', '=', 'quotes.user_id')
            ->select('users.name as customer_name', 'users.mobile as customer_mobile', 'users.email as customer_email', "quotes.*", "categories.category_name")
            ->where('categories.category_name', '=', "Miscellaneous")
            ->whereDate('quotes.created_at','>=' ,$from_date)
            ->whereDate('quotes.created_at','<=' , $to_date)
            ->get();

       /*  if (count($quotes) > 0) {
            foreach ($quotes as $key => $quote) {
                $quotes[$key]->count_sent = DB::table('vendor_quotes')->select("count('vendor_quotes.id')")->where('vendor_quotes.quote_id', '=', $quote->id)->groupBy("vendor_quotes.quote_id")->count();
                $quotes[$key]->count_responded = DB::table('vendor_quotes')->select("count('vendor_quotes.id')")->where('vendor_quotes.quote_id', '=', $quote->id)->where('vendor_quotes.isResponded', '=', 1)->groupBy("vendor_quotes.quote_id")->count();
            }
        } */
        $totalRecords = count($quotes);

        //filtered record
        $quotes = DB::table('quotes')->leftjoin("categories", "quotes.category", "=", "categories.id")
            ->leftjoin('users', 'users.id', '=', 'quotes.user_id')
            ->select('users.name as customer_name', 'users.mobile as customer_mobile', 'users.email as customer_email', "quotes.*", "categories.category_name")
            ->where('categories.category_name', '=', "Miscellaneous")
            ->whereDate('quotes.created_at','>=' ,$from_date)
            ->whereDate('quotes.created_at','<=' , $to_date)
            ->where(function ($q) use ($searchValue) {
                $q->Where('quotes.item', 'like', '%' . $searchValue . '%')
                    ->orWhere('quotes.location', 'like', '%' . $searchValue . '%')
                    ->orWhere('users.name', 'like', '%' . $searchValue . '%')
                    ->orWhere('users.mobile', 'like', '%' . $searchValue . '%')
                    ->orWhere('users.email', 'like', '%' . $searchValue . '%')
                    ->orWhere('categories.category_name', 'like', '%' . $searchValue . '%');
            })
            ->get();

      /*   if (count($quotes) > 0) {
            foreach ($quotes as $key => $quote) {
                $quotes[$key]->count_sent = DB::table('vendor_quotes')->select("count('vendor_quotes.id')")->where('vendor_quotes.quote_id', '=', $quote->id)->groupBy("vendor_quotes.quote_id")->count();
                $quotes[$key]->count_responded = DB::table('vendor_quotes')->select("count('vendor_quotes.id')")->where('vendor_quotes.quote_id', '=', $quote->id)->where('vendor_quotes.isResponded', '=', 1)->groupBy("vendor_quotes.quote_id")->count();
            }
        } */
        $totalRecordswithFilter = count($quotes);

        if ($columnName == "quote_request") {
            $columnName = "quotes.item";
        } elseif ($columnName == "customer_info") {
            $columnName = "users.name";
        } elseif ($columnName == "category_name") {
            $columnName = "categories.category_name";
        } elseif ($columnName == "location") {
            $columnName = "quotes.location";
        } elseif ($columnName == "created_at") {
            $columnName = "quotes.created_at";
        } else {
            $columnName = "quotes.created_at";
        }

        // dd($columnName);
        // Fetch records
        
            $quotes = DB::table('quotes')->leftjoin("categories", "quotes.category", "=", "categories.id")
                ->leftjoin('users', 'users.id', '=', 'quotes.user_id')
                ->select('users.name as customer_name', 'users.mobile as customer_mobile', 'users.email as customer_email', "quotes.*", "categories.category_name")
                ->where('categories.category_name', '=', "Miscellaneous")
                ->whereDate('quotes.created_at','>=' ,$from_date)
                ->whereDate('quotes.created_at','<=' , $to_date)
                ->where(function ($q) use ($searchValue) {
                    $q->Where('quotes.item', 'like', '%' . $searchValue . '%')
                        ->orWhere('quotes.location', 'like', '%' . $searchValue . '%')
                        ->orWhere('users.name', 'like', '%' . $searchValue . '%')
                        ->orWhere('users.mobile', 'like', '%' . $searchValue . '%')
                        ->orWhere('users.email', 'like', '%' . $searchValue . '%')
                        ->orWhere('categories.category_name', 'like', '%' . $searchValue . '%');
                })
                ->orderBy($columnName, $columnSortOrder)
                ->skip($start)
                ->take($rowperpage)
                ->get();
       

        if (count($quotes) > 0) {
            foreach ($quotes as $key => $quote) {
                $quotes[$key]->count_sent = DB::table('vendor_quotes')->select("count('vendor_quotes.id')")->where('vendor_quotes.quote_id', '=', $quote->id)->groupBy("vendor_quotes.quote_id")->count();
                $quotes[$key]->count_responded = DB::table('vendor_quotes')->select("count('vendor_quotes.id')")->where('vendor_quotes.quote_id', '=', $quote->id)->where('vendor_quotes.isResponded', '=', 1)->groupBy("vendor_quotes.quote_id")->count();
            }
        }
        $records = $quotes;

        $data_arr = array();
        foreach ($records as $record) {
            $id = $record->id;
            $photo = '<img class="myImg" height= "50" width = "50" src="' . asset("public/assets/images/quotes/" . $record->item_sample) . '" alt="image"/>';
            $quote_request = '<b>' . $record->item . '</b> <br>' . $record->item_description . '<br><i class="fa fa-calendar"></i> ' . date('d-M-Y h:i a', strtotime($record->created_at));
            $customer_info = '<strong> ' . ucfirst($record->customer_name) . '</strong><br><strong>Phone :</strong> ' . $record->customer_mobile . ' <br><strong>Email :</strong> ' . $record->customer_email . ' <br>';
            $category_name = $record->category_name;
            $location = $record->location;
            $created_at = date('d-M-Y h:i a', strtotime($record->created_at));
            $action = '<a href="' . url("admin/view-misc-quote-requests/" . $record->id) . '" class="btn btn-sm btn-success"><span class="badge"></span>View Quote</a>';

            $data_arr[] = array(
                //  "id" => $id,
                "photo" => $photo,
                "quote_request" => $quote_request,
                "customer_info" => $customer_info,
                "category_name" => $category_name,
                "location" => $location,
                "created_at" => $created_at,
                "action" => $action,
            );
        }

        $response = array(
            "draw" => intval($draw),
            "iTotalRecords" => $totalRecords,
            "iTotalDisplayRecords" => $totalRecordswithFilter,
            "aaData" => $data_arr,
        );

        echo json_encode($response);
        exit;
    }

    /**
     * AJAX Request End
     */

    public function viewMiscQuoteRequests($quote_id, Request $request)
    {

        // $vendors = Vendor::all();
        // $vendors = $this->getVendorList($request);
        //   dd($vendors);
        $userdata = Auth::user();
        $sLocation = '';
        $sCategory = '';
        $categories = DB::select('select * from categories ORDER BY category_name ASC');
        $locations = DB::table('locations')->select('city')->distinct()->get();
        $quotedata = DB::table('quotes')->leftjoin("categories", "quotes.category", "=", "categories.id")
            ->leftjoin('users', 'users.id', '=', 'quotes.user_id')
            ->select('users.name as customer_name', 'users.mobile as customer_mobile', 'users.email as customer_email', "quotes.*", "categories.category_name")
            ->where('quotes.id', '=', $quote_id)
            ->orderBy('quotes.created_at', 'DESC')
            ->get();
        //echo "<pre>";print_r($quotedata);die();
        return view('admin.quotes.view-misc-quote-requests', compact(
            'quotedata',
            'categories',
            'locations',
            'sCategory',
            'sLocation'
        ));
    }

    /*
    AJAX request
     */
    public function getVendorList(Request $request)
    {
        ## Read value
        $draw = $request->get('draw');
        $start = $request->get("start");
        $rowperpage = $request->get("length"); // Rows display per page

        $columnIndex_arr = $request->get('order');
        $columnName_arr = $request->get('columns');
        $order_arr = $request->get('order');
        $search_arr = $request->get('search');

        $columnIndex = $columnIndex_arr[0]['column']; // Column index
        $columnName = $columnName_arr[$columnIndex]['data']; // Column name
        $columnSortOrder = $order_arr[0]['dir']; // asc or desc
        $searchValue = $search_arr['value']; // Search value
        $searchByCategory_id = $request->searchByCategory_id; // Search by category
        $searchByLocation = $request->searchByLocation; // Search by location

        // Total records
        $totalRecords = Vendor::select('vendors.*', 'count(*) as allcount')
            ->leftjoin("categories", "vendors.category", "=", "categories.id")
            ->count();

        // Fetch records
        if ($searchByCategory_id != '' || $searchByLocation != '') {
            $totalRecordswithFilter = Vendor::select('vendors.*', 'count(*) as allcount')
                ->leftjoin("categories", "vendors.category", "=", "categories.id")
                ->where('vendors.category', '=', $searchByCategory_id)
                ->where('vendors.company_city', 'like', '%' . $searchByLocation . '%')
                ->where(function ($query) use ($searchValue) {
                    $query->where('vendors.name', 'like', '%' . $searchValue . '%')
                        ->orWhere('vendors.mobile', 'like', '%' . $searchValue . '%')
                        ->orWhere('vendors.company_address', 'like', '%' . $searchValue . '%')
                        ->orWhere('vendors.email', 'like', '%' . $searchValue . '%')
                        ->orWhere('vendors.contact_person', 'like', '%' . $searchValue . '%');
                })
                ->count();

            $records = Vendor::orderBy($columnName, $columnSortOrder)
                ->where('vendors.category', '=', $searchByCategory_id)
                ->where('vendors.company_city', 'like', '%' . $searchByLocation . '%')
                ->where(function ($query) use ($searchValue) {
                    $query->where('vendors.name', 'like', '%' . $searchValue . '%')
                        ->orWhere('vendors.mobile', 'like', '%' . $searchValue . '%')
                        ->orWhere('vendors.company_address', 'like', '%' . $searchValue . '%')
                        ->orWhere('vendors.email', 'like', '%' . $searchValue . '%')
                        ->orWhere('vendors.contact_person', 'like', '%' . $searchValue . '%');
                })
                ->leftjoin("categories", "vendors.category", "=", "categories.id")
                ->select('vendors.*', 'categories.category_name')
                ->skip($start)
                ->take($rowperpage)
                ->get();
        } else {
            $totalRecordswithFilter = Vendor::select('vendors.*', 'count(*) as allcount')
                ->leftjoin("categories", "vendors.category", "=", "categories.id")
                ->where('vendors.name', 'like', '%' . $searchValue . '%')
                ->orWhere('categories.category_name', 'like', '%' . $searchValue . '%')
                ->orWhere('vendors.mobile', 'like', '%' . $searchValue . '%')
                ->orWhere('vendors.company_address', 'like', '%' . $searchValue . '%')
                ->orWhere('vendors.email', 'like', '%' . $searchValue . '%')
                ->count();

            $records = Vendor::orderBy($columnName, $columnSortOrder)
                ->where('vendors.name', 'like', '%' . $searchValue . '%')
                ->orWhere('categories.category_name', 'like', '%' . $searchValue . '%')
                ->orWhere('vendors.mobile', 'like', '%' . $searchValue . '%')
                ->orWhere('vendors.company_address', 'like', '%' . $searchValue . '%')
                ->orWhere('vendors.email', 'like', '%' . $searchValue . '%')
                ->leftjoin("categories", "vendors.category", "=", "categories.id")
                ->select('vendors.*', 'categories.category_name')
                ->skip($start)
                ->take($rowperpage)
                ->get();
        }

        $data_arr = array();

        foreach ($records as $record) {
            if (is_numeric($record->mobile)) {
                $number = $record->mobile;
            } else {
                $number = '';
            }
            $email = str_replace('N/A-', '', $record->email);
            if (is_numeric($email)) {
                $email = '';
            } else {
                $email = $record->email;
            }
            $id = $record->id;
            $name = $record->name;
            $email = $email;
            $mobile = $number;
            $contact_person = $record->contact_person;
            $company_address = $record->company_address;

            $data_arr[] = array(
                "id" => $id,
                "name" => $name,
                "email" => $email,
                "mobile" => $mobile,
                "contact_person" => $contact_person,
                "company_address" => $company_address,
            );
        }

        $response = array(
            "draw" => intval($draw),
            "iTotalRecords" => $totalRecords,
            "iTotalDisplayRecords" => $totalRecordswithFilter,
            "aaData" => $data_arr,
        );

        echo json_encode($response);
        exit;
    }

    public function filterMiscQuoteVendors(Request $request)
    {
        //echo "<pre>";print_r($request->all());//die();
        $vendors = Vendor::all();
        $userdata = Auth::user();
        $sLocation = $request->location;
        $sCategory = $request->category;
        $sQuoteId = $request->quote_id;
        if ($sLocation != '' && $sCategory != '') {
            $vendors = DB::table('vendors')
                ->select('vendors.*')
                ->where('category', '=', $sCategory)
                ->where('company_city', '=', $sLocation)
                ->get();
        }
        /*if($searchByTag != ''){
        $aTagsData = DB::table('tags')->leftjoin("categories","tags.category_id","=","categories.id")
        ->select('tags.*',"categories.*")
        //->where('categories.id', '=', $searchByCategory)
        ->where('tags.tag_name', 'like', '%' . $searchByTag . '%')
        ->get();

        }
        if($searchByCategory != ''){
        $aTagsData = DB::table('tags')->leftjoin("categories","tags.category_id","=","categories.id")
        ->select('tags.*',"categories.*")
        ->where('categories.id', '=', $searchByCategory)
        //->where('tags.tag_name', 'like', '%' . $searchByTag . '%')
        ->get();

        }*/

        $categories = DB::select('select * from categories ORDER BY category_name ASC');
        $locations = DB::table('locations')->select('city')->distinct()->get();
        $quotedata = DB::table('quotes')->leftjoin("categories", "quotes.category", "=", "categories.id")
            ->leftjoin('users', 'users.id', '=', 'quotes.user_id')
            ->select('users.name as customer_name', 'users.mobile as customer_mobile', 'users.email as customer_email', "quotes.*", "categories.category_name")
            ->where('quotes.id', '=', $sQuoteId)
            ->get();
        //echo "<pre>";print_r($quotedata);die();
        return view('admin.quotes.view-misc-quote-requests', compact(
            'quotedata',
            'vendors',
            'categories',
            'locations',
            'sCategory',
            'sLocation'
        ));
    }

    public function sendMiscQuoteRequests(Request $request)
    {
        $sQuoteId = $request->quote_id;
        $sUserId = $request->user_id;
        $sLocation = $request->location;
        $sCategory = $request->category;
        // $vendors = Vendor::all();
        $userdata = Auth::user();
        $quotedata = Quotes::where('id', '=', $sQuoteId)->first();
        if ($request->vendor_id != null) {
            for ($i = 0; $i < count($request->vendor_id); $i++) {
                $sVendorId = $request->vendor_id[$i];
                $sStatus = "New";
                $vendors = Vendor::findOrFail($sVendorId);
                $vendor_category = $vendors['category'];
                VendorQuote::create(['user_id' => $sUserId, 'quote_id' => $sQuoteId, 'vendor_id' => $sVendorId, 'status' => $sStatus]);
                $quote = Quotes::findOrFail($sQuoteId);
                $quote->category = $vendor_category;
                $quote->update();
            }
        } else {
            return redirect('admin/misc-quote-requests')->with('flash_error_message', 'Please select vendor..!');
        }
        //Display a successful message upon save
        return redirect()->route('admin.quotes')
            ->with('flash_message', 'Quote sent successfully..!');
    }

    /**
     * Display the the Home page.
     *
     * @return Response
     */
    public function quoteResponses($quote_id)
    {
        $userdata = Auth::user();
        $quotedata = Quotes::where('id', '=', $quote_id)->first();
        $quoterequests = VendorQuote::leftjoin('vendors', 'vendors.id', '=', 'vendor_quotes.vendor_id')
            ->select('vendor_quotes.created_at as response_created_at', 'vendor_quotes.id as vendor_quote_id', 'vendors.*')
        // ->select('vendor_quotes.created_at as response_created_at','vendor_quotes.id as vendor_quote_id','vendor_quotes.quote_response','vendors.*') //old query
            ->where('vendor_quotes.isResponded', '=', 1)
            ->where('quote_id', '=', $quote_id)
            ->orderBy('vendor_quotes.id', 'DESC')
            ->get();

        //echo "<pre>"; print_r($quotedata); echo "</pre>";  exit;
        return view("admin/quotes/quote-responses", ['quote_id' => $quote_id, 'quoterequests' => $quoterequests, 'quotedata' => $quotedata]);
    }

    /**
     * Display all quotes responded
     *
     * @return Response
     */
    public function getAllQuoteResponses()
    {
        $userdata = Auth::user();
        $categories = DB::select('select * from categories ORDER BY category_name ASC');
        $locations = DB::table('locations')->select('city')->distinct()->get();
        $quotedata = DB::table('quotes')->leftjoin("vendor_quotes", "vendor_quotes.quote_id", "=", "quotes.id")
            ->leftjoin("categories", "quotes.category", "=", "categories.id")
            ->leftjoin('users', 'users.id', '=', 'quotes.user_id')
            ->select('users.name as customer_name', 'users.mobile as customer_mobile', 'users.email as customer_email', "quotes.*", "categories.category_name")
            ->where('vendor_quotes.isResponded', '=', 1)
            ->get();

        // echo "<pre>"; print_r($quotedata); echo "</pre>";  exit;

        return view("admin/quotes/all-quote-responses", compact('quotedata'));
    }

    /**
     * Display the the Home page.
     *
     * @return Response
     */
    public function quotesSentToVendors()
    {

        //SELECT COUNT(quote_id) AS count FROM vendor_quotes HAVING count >1
        //SELECT quote_id FROM vendor_quotes where status="new" group by quote_id
        $userdata = Auth::user();
        $categories = DB::select('select * from categories ORDER BY category_name ASC');
        $locations = DB::table('locations')->select('city')->distinct()->get();
        $aQuotesData = DB::table('quotes')->leftjoin("vendor_quotes", "vendor_quotes.quote_id", "=", "quotes.id")
            ->leftjoin("categories", "quotes.category", "=", "categories.id")
            ->leftjoin('users', 'users.id', '=', 'quotes.user_id')
            ->select('users.name as customer_name', 'users.mobile as customer_mobile', 'users.email as customer_email', "quotes.*", "categories.category_name")
            ->where('vendor_quotes.status', '=', "Quote Raised")
            ->orderBy('quotes.id', 'DESC')
            ->get();
        //echo "<pre>"; print_r($quotedata); echo "</pre>";  exit;
        return view("admin/quotes/quotes-sent-to-vendors", compact('aQuotesData'));
    }

    //Route::get('quote-sent-vendors/{quote_id}', ['uses'=>'QuoteController@quoteSentVendors']);
    /**
     * Display the the Home page.
     *
     * @return Response
     */
    public function quoteSentVendors($quote_id)
    {
        $userdata = Auth::user();
        $quotedata = Quotes::where('id', '=', $quote_id)->first();
        $quoterequests = VendorQuote::leftjoin('vendors', 'vendors.id', '=', 'vendor_quotes.vendor_id')
            ->select('vendor_quotes.created_at as response_created_at', 'vendor_quotes.isResponded', 'vendor_quotes.id as vendor_quote_id', 'vendors.*')
        // old query
        // ->select('vendor_quotes.created_at as response_created_at','vendor_quotes.isResponded','vendor_quotes.id as vendor_quote_id','vendor_quotes.quote_response','vendor_quotes.quote_response','vendors.*')
            ->where('quote_id', '=', $quote_id)
            ->orderBy('vendor_quotes.id', 'DESC')
            ->get();
        //echo "<pre>"; print_r($quotedata); echo "</pre>";  exit;
        return view("admin/quotes/quote-sent-vendors", ['quote_id' => $quote_id, 'quoterequests' => $quoterequests, 'quotedata' => $quotedata]);
    }

    public function view_sent_quote($vendor_quote_id, Request $request)
    {
        $vendorquote = VendorQuote::join('vendors', 'vendors.id', '=', 'vendor_quotes.vendor_id')
            ->join('quotes', 'quotes.id', '=', 'vendor_quotes.quote_id')
            ->leftjoin('users', 'users.id', '=', 'quotes.user_id')
            ->select('users.name as customer_name', 'users.mobile as customer_mobile', 'users.email as customer_email', 'vendors.*', 'vendor_quotes.*', 'quotes.*', 'vendor_quotes.id as vendor_quote_id', 'vendor_quotes.created_at as vendorquote_created_at', 'quotes.created_at as quote_created_at')->where('vendor_quotes.id', $vendor_quote_id)->first();
        //echo "<pre"print_r($vendorquote); exit;
        return view('admin/quotes/view-send-quote-response', compact('vendorquote'));
    }
    public function ajax_view_sent_quote(Request $request)
    {
        $this->data['vendor_quote_id'] = $request->quote_id;
        $this->data['vendorquote'] = VendorQuote::join('vendors', 'vendors.id', '=', 'vendor_quotes.vendor_id')
            ->join('quotes', 'quotes.id', '=', 'vendor_quotes.quote_id')
            ->leftjoin('users', 'users.id', '=', 'quotes.user_id')
            ->select('users.name as customer_name', 'users.mobile as customer_mobile', 'users.email as customer_email', 'vendors.*', 'vendor_quotes.*', 'quotes.*', 'vendor_quotes.id as vendor_quote_id', 'vendor_quotes.created_at as vendorquote_created_at', 'quotes.created_at as quote_created_at')->where('vendor_quotes.id', $request->quote_id)->first();
        $this->data['quote_id'] = $request->quote_id;
        $returnHTML = view('admin.quotes.ajax-view-send-quote-response')->with($this->data)->render();
        return response()->json(array('success' => true, 'popup_html' => $returnHTML));
    }

    public function update_sent_quote_price(Request $request)
    {
        $photo1 = '';
        if ($request->hasFile('photo')) {
            $image = $request->photo;
            $path = public_path('assets/images/quote-responses/');
            $photo1 = 'photo-' . time() . '.' . $image->getClientOriginalExtension();
            $image->move($path, $photo1);
        }

        $document1 = '';
        if ($request->hasFile('document')) {
            $image = $request->document;
            $path = public_path('assets/documents/quote-responses/');
            $document1 = 'doc-' . time() . '.' . $image->getClientOriginalExtension();
            $image->move($path, $document1);
        }

        $vendor_quote = VendorQuote::find($request->increment_id);
        $vendor_quote->price = $request->price;
        $vendor_quote->discount = $request->discount;
        $vendor_quote->photo = $photo1;
        $vendor_quote->document = $document1;
        $vendor_quote->additional_details = $request->additional_details;
        $vendor_quote->expiry_date = date('Y-m-d', strtotime(date('Y-m-d') . ' + 10 days'));
        $vendor_quote->status = 'Responded';
        $vendor_quote->isResponded = '1';
        $vendor_quote->responded_at = date('Y-m-d H:i:s');
        $vendor_quote->save();

        foreach ($request->product_name as $num => $product_name) {
            if ($request->product_name[$num] != '') {
                $product_file = '';
                if (isset($request->myfile[$num]) && $request->myfile[$num] != '' && $_FILES['myfile']['error'][$num] == 0) {
                    $image = $request->myfile[$num];
                    $path = public_path('assets/images/quote-responses/');
                    $product_file = 'photo-' . time() . rand() . '.' . $image->getClientOriginalExtension();
                    $image->move($path, $product_file);
                }

                $similarproduct = array(
                    'vendor_quote_id' => $request->increment_id,
                    'quote_id' => $request->quote_id,
                    'product_name' => $product_name,
                    'product_description' => $request->product_description[$num],
                    'product_price' => $request->product_price[$num],
                    'product_discount' => $request->product_discount[$num],
                    'product_expirydate' => $request->product_expirydate[$num],
                    'product_file' => $product_file,
                );
                VendorQuoteProducts::create($similarproduct);
            }
        }

        return redirect(url('admin/view-sent-quote/' . $request->increment_id))->with('flash_message', 'Price Update successfully..!');
    }

    public function create()
    {
        return view('quotes.create');
    }

    //Create New Quote from Admin
    public function store(Request $request)
    {
        $this->validate($request, [
            'furniture_type' => 'required',
            'material_type' => 'required',
            'color' => 'required',
            'minPrice' => 'required',
            'maxPrice' => 'required',
            'additionalDetails' => 'required',
            'photo' => 'required',
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
                'photo' => $filename,
                'cat_type' => $request->cat_type,
                'status' => 'Quote Raised',
                'userId' => Auth::user()->id,
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
            'furniture_type' => 'required',
            'material_type' => 'required',
            'color' => 'required',
            'minPrice' => 'required',
            'maxPrice' => 'required',
            'additionalDetails' => 'required',
        ]);

        if ($request->hasFile('photo')) {
            $image = $request->photo;
            $path = public_path('../public/assets/images/quotes/');
            $filename2 = time() . '.' . $image->getClientOriginalExtension();
            $image->move($path, $filename2);
            $filename = $filename2;
        } else {
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
        $quote->photo = $filename;
        $quote->update();

        return redirect()->route('admin.quotes')
            ->with('flash_message', 'Quote Updated successfully..!');
    }

    public function show($id)
    {
        $quote = Quote::findOrFail($id);
        return view('quotes.show', compact('quote'));
    }

    public function edit($id)
    {
        $quote = Quote::findOrFail($id);
        return view('quotes.edit', compact('quote'));
    }

    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'furniture_type' => 'required',
            'material_type' => 'required',
            'color' => 'required',
            'minPrice' => 'required',
            'maxPrice' => 'required',
            'additionalDetails' => 'required',
            'photo' => 'required',
            'userId' => 'required',
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
        $quote_id = $request->quote_id;
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

        foreach ($vendor_ids as $vendor_id) {
            $data['vendor_id'] = $vendor_id;
            $Vendor = Vendor::findOrFail($vendor_id);
            $cparameter = Crypt::encrypt($Vendor->user_id);
            // $message = "You Have Recived New Quote Request URL: http://avedemos.uk/interiorquotesv2/public/";
            $message = "Hi $Vendor->username, You Have Recived New Quote Request URL: http://avedemos.uk/interiorquotesv2/public/vendor_quote_form/$quote_id/$cparameter";
            $mobile = $Vendor->mobile;
            $result = $this->getMsg($senderid, $message, $mobile, $authkey);

            if (isset($result)) {
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
        $tracking_quotes = QuoteTracking::with(['Vendor', 'Customer', 'Quote'])->get();
        return view('admin.quotes.quotes-tracking', compact('tracking_quotes'));
    }

    public function getMsg($senderid, $message, $mobile, $authkey)
    {
        $url = "https://api.msg91.com/api/sendhttp.php?mobiles=$mobile&authkey=$authkey&route=4&sender=$senderid&message=$message&country=91";
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

    public function quoteEditRequests(Request $request)
    {
        $this->data['categories'] = Category::all();
        $this->data['quotes'] = Quotes::find($request->id);
        $this->data['page_title'] = 'Edit Quotes';
        $returnHTML = view('admin.quotes.edit_request')->with($this->data)->render();
        return response()->json(array('success' => true, 'popup_html' => $returnHTML));
    }

    public function quoteDeleteRequests(Request $request)
    {
        // dd($request->all());
        $aResult = DB::table('quotes')->where('id', '=', $request->id)->delete();
        if ($aResult) {
            echo 1;
            exit;
        }
    }

    public function updateRequest(Request $request)
    {
        $this->validate($request, [
            'item' => 'required|max:120',
        ]);

        $quote_req = Quotes::find($request->id);
        $quote_req->item = $request->item;
        $quote_req->category = $request->category_id;
        $quote_req->save();

        $message = ' Updated ';
        //Redirect to the categories.index view and display message
        return redirect()->route('admin.quotes')->with('flash_message', 'Request Successfully ' . $message . ' .');
    }

    public function raisedQuote()
    {
        return view('admin.quotes.raised_quote');
    }
}
