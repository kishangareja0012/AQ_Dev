<?php

namespace Modules\Admin\Http\Controllers;

use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;
use App\User;
use App\Vendor;
use App\Quotes;
use Auth;
use App\VendorQuote;
use DB;
use Session;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Input;
use Excel;
use Yajra\Datatables\Datatables;

//Importing laravel-permission models
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class VendorController extends Controller{
    public function __construct() {
        ini_set('memory_limit', '-1');
        //$this->middleware(['auth', 'isAdmin']); //isAdmin middleware lets only users with a //specific permission permission to access these resources
    }
    
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $sLocation = '';
        $sCategory = '';
        // $vendors = Vendor::all();
        $vendors = Vendor::paginate(15);
        $categories = DB::select('select * from categories ORDER BY category_name ASC');
        $locations = DB::table('locations')->select('city')->distinct()->orderBy('city','ASC')->get();
        return view('admin.vendors.vendor', compact('categories','vendors','locations', 'sCategory', 'sLocation'));
    }
    
    /**
     * Filter vendors by category and location.
     *
     * @return \Illuminate\Http\Response
     */
    public function filterVendors(Request $request)
    {
        
        $userdata = Auth::user();
		$sLocation = $request->location;
        $sCategory = $request->category;
        $sQuoteId = $request->quote_id;
        if($sLocation != '' && $sCategory != ''){           
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
        echo json_encode(array('success'=>1,$this->data));
        // return view('admin.vendors.vendor', compact('categories','vendors','locations', 'sCategory', 'sLocation'));
    }

    /**
     * Get vendors by category.
     *
     * @return \Illuminate\Http\Response
     */
    public function getVendorsByCategory(Request $request)
    {
        $input = $request->all();
        $sCategoryId = $request->id;
        if($sCategoryId == 0){
            echo "<option>Select Vendor</option>";
        }else{
            $aVendorsResult = DB::table('vendors')->where('category', '=', $sCategoryId)->get();
            //print_r($aVendorsResult);die();
            foreach ($aVendorsResult as $key => $vData) {
                echo '<option value="'.$vData->id.'">'.$vData->name.'</option>';
            }
        }
    }

    public function add_vendor(){
		$categories = DB::select('select * from categories ORDER BY category_name ASC');
        //return view('vendor', compact('categories'));
        return view('admin.vendors.create_vendor', compact('categories'));
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:vendors',
            'mobile' => 'required|numeric|min:10|unique:vendors',
            'password' => 'required|string|min:6',
        ]);
       /*  $sMobileNumber = "91" . $request->mobile;
        $exists = DB::table('vendors')
        ->where('register_by_self', '=', '1')
        ->where(function ($query) use ($request , $sMobileNumber) {
            $query->orWhere('mobile', '=',  $request->mobile)
                  ->orWhere('email', '=', $request->email)
                  ->orWhere('mobile', '=', $sMobileNumber);
        })
        ->first();
        //$exists = \DB::table('vendors')->where('mobile', '=', $request->mobile)->first()
        if ($exists) {
            return back()->with('flash_message', 'EmailID and Phone number is already exists please try to login it..!');
        } else { */
            $vendor_category = implode($request->cat_type,',');
            $data['name']= $request->name;
            $data['email']= $request->email;
            $data['mobile']= $request->mobile;
            $data['password']= Hash::make($request->password);
            $data['address']= $request->address;
            $data['company_name']= $request->company_name;
            $data['company_email']= $request->company_email;
            $data['company_phone']= $request->company_number;
            $data['company_state']= $request->company_state;
            $data['company_city']= $request->company_city;
            $data['company_pin']= $request->company_pin;
            $data['company_address']= $request->company_address;
            $data['website']= $request->website;
            $data['contact_person']= $request->name;
            $data['is_privacy']= (isset($request->isprivacy))?1:0;
            $data['isVerified']= 1;
            $data['status']= 1;
            $data['category']= $vendor_category;
            $vendor = Vendor::create($data); //Retrieving only the email and password data

            return redirect()->route('admin.vendors')->with('flash_message', 'Vendor added successfully..!');
        //}
    }
	
	public function edit_vendor($vendor_id){
		$categories = DB::select('select * from categories ORDER BY category_name ASC');
        //return view('vendor', compact('categories'));
		$vendor = Vendor::findOrFail($vendor_id);
	
        return view('admin.vendors.edit_vendor', compact('categories','vendor','vendor_id'));
    }
	
	public function update_vendor($vendor_id, Request $request)
    {
        $this->validate($request, [
            'name' => 'required|string|max:255',
            'mobile' => 'required|numeric|min:10|unique:vendors,mobile,'.$vendor_id,
			'email'=>'required|email|unique:vendors,email,'.$vendor_id,
            //'password' => 'required|string|min:6',
        ]);
		
		$vendor_category = implode($request->cat_type,',');
		$vendor = Vendor::findOrFail($vendor_id);
        $vendor->name= $request->name;
        $vendor->email= $request->email;
        $vendor->mobile= $request->mobile;
        $vendor->company_name= $request->company_name;
        $vendor->company_email= $request->company_email;
        $vendor->company_phone= $request->company_number;
        $vendor->company_state= $request->company_state;
        $vendor->company_city= $request->company_city;
        $vendor->company_pin= $request->company_pin;
        $vendor->company_address= $request->company_address;
        $vendor->website= $request->website;
        $vendor->contact_person= $request->name;
        $vendor->is_privacy= $request->isprivacy;
        $vendor->category= $vendor_category;
        $vendor->save();

        return redirect()->route('admin.vendors')->with('flash_message', 'Vendor details updated successfully..!');
    }
    
	/**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function downloadData($type)
    {
        ob_end_clean(); 
        ob_start(); 
        $data = Vendor::get()->toArray();
        return Excel::create('excel_data', function($excel) use ($data) {
            $excel->sheet('mySheet', function($sheet) use ($data)
            {
                $sheet->fromArray($data);
            });
        })->download($type);
    }

    //Vendor Quotes tracking page
    public function import_vendors()
    {
        $aVData = Vendor::take(5)->get();
        $categories = DB::select('SELECT * FROM categories ORDER BY category_name ASC');
        return view('admin.import-vendors', compact('categories','aVData'));
    }

    public function importData(Request $request)
    {
        // ini_set('memory_limit', '-1');
        $sCategory = Input::get('category');        
        $request->validate([
            'import_file' => 'required'
        ]);
        try {
            Excel::load(Input::file('import_file'), function ($reader) {
                // echo "<pre>";
                // print_r($reader->toArray()); die();
                foreach ($reader->toArray() as $row) { 
                    $rand = rand(1111111111,9999999999).strtotime("now");
                    // print_r($row);die;   
                    if(empty($row['email']) || $row['email']=='N/A'){
                        $row['email'] = 'N/A'.'-'. $rand;
                    }
                    if(empty($row['whatsappno']) || $row['whatsappno']=='N/A' ){
                        $row['whatsappno'] = 'N/A'.'-'. $rand;
                    }
                    $row['category'] = Input::get('category');
                    $checkExists = Vendor::where('mobile', '=', $row['whatsappno'])->where('email', '=', $row['email'])->where('name', '=', $row['businessname'])->first(); // this returns a true or false
                    if(!$checkExists){
                        // Vendor::firstOrCreate($row);
                        try { 
                            $flight = new Vendor;
                            $flight->category = Input::get('category');
                            $flight->name = $row['businessname'];
                            $flight->email = $row['email'];
                            $flight->mobile = $row['whatsappno'];
                            $flight->password = md5($rand);
                            $flight->company_name = $row['businessname'];
                            $flight->company_email = $row['email'];
                            $flight->company_phone = $row['whatsappno'];
                            $flight->company_address = $row['address'];
                            $flight->company_city = $row['city'];
                            $flight->company_state = isset($row['company_state']) ? $row['company_state'] : '';
                            $flight->company_pin = $row['pincode'];
                            $flight->contact_person = isset($row['contact_person']) ? $row['contact_person'] : '';
                            $flight->website = $row['website'];
                            $flight->isVerified = isset($row['isverified']) ? $row['isverified'] : 1;
                            $flight->status = isset($row['status']) ? $row['status'] : 1;
                            $flight->is_privacy = isset($row['is_privacy']) ? $row['is_privacy'] : 1;
                            $flight->phone1 = $row['phone1'];
                            $flight->save();
                              // Closures include ->first(), ->get(), ->pluck(), etc.
                          } catch(\Illuminate\Database\QueryException $ex){ 
                            // dd($ex->getMessage()); 
                            // Note any method of class PDOException can be called on $ex.
                          }
                        $created = 'true';
                    }
                }
                // die;
            });            
            return back()->with('success', 'Imported successfully.');
        } catch (\Exception $e) {
            \Session::flash('error', $e->getMessage());
            return back()->with('failed', $e->getMessage());
        }
    }

     /**
     * Get coulumn names of vendors table.
     *
     * @return \Illuminate\Http\Response
     */
    public function getColumnNames($sTableName){
        try {
            $aTableColumns = DB::select('SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = "'.$sTableName.'" ');            
            $aOutput = array();
            foreach($aTableColumns as $cData){
                $aOutput[] = $cData->COLUMN_NAME;  
            }
            //print_r($aOutput);            
            return $aOutput; 
        }    
        catch(PDOException $pe) {
            trigger_error('Could not connect to MySQL database. ' . $pe->getMessage() , E_USER_ERROR);
        }
    }
	
	//Vendor Quotes tracking page
    public function save_vendors(Request $request)
    {	
		if ($request->hasFile('vendors')) {
			$csv = $request->vendors;
			$path = public_path('../public/assets/csvs/');
			$filename = time() . '.' . $csv->getClientOriginalExtension();
			$csv->move($path, $filename);
		}
		
		// Import CSV to Database
         $filepath = $path."/".$filename; 
		 //exit;

          // Reading file
        $file = fopen($filepath,"r");
		
		$importData_arr = array();
          $i = 0;

          while (($filedata = fgetcsv($file, 1000, ",")) !== FALSE) {
             $num = count($filedata );             
			 
			 //echo "<pre>"; print_r($filedata); exit;
			 
             // Skip first row (Remove below comment if you want to skip the first row)
             if($i == 0){
                $i++;
                continue; 
             }
             for ($c=0; $c < $num; $c++) {
                $importData_arr[$i][] = $filedata [$c];
             }
             $i++;
          }
		
		//echo "<pre>"; print_r($importData_arr); exit;
		
		$imported = 0;
		foreach($importData_arr as $vendor){
			
			$vendors = Vendor::where('mobile',$vendor[1])->count();
			
			if($vendors == 0){
				Vendor::create(array('company_name'=>$vendor[0],'company_email'=>$vendor[3],'company_phone'=>$vendor[1],'company_address'=>$vendor[2],'name'=>$vendor[0],'email'=>$vendor[3],'mobile'=>$vendor[1],'password'=>Hash::make($vendor[1]),'category'=>$request->cat_type,'status'=>1,'isVerified'=>1));
				$imported++;
			}
		}
        return redirect()->route('admin.import.vendors')
            ->with('flash_message', $imported.' Vendors imported successfully..!');
    }
	

    //Vendor Quotes tracking page
    public function vendor_quotes_tracking()
    {
        $tracking_vendor_quotes = VendorQuote::with(['Vendor','Customer','Quote'])->get();
        return view('admin.vendors.vendors-tracking', compact('tracking_vendor_quotes'));
    }

    //edit and get quote function
    public function editquote($quote_id)
    {
        //echo "quote id :".$quote_id;die();
        $quote = Quotes::findOrFail($quote_id);
        return view('admin/vendors/edit-quote', compact('quote'));
    }
    /*
   AJAX request
   */
   public function getVendorList(Request $request){
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
    $searchByVendor_name = $request->searchByVendor_name; // Search by location

    // Total records
    $totalRecords = Vendor::select('vendors.*','count(*) as allcount')
    ->leftjoin("categories","vendors.category","=","categories.id")
    ->where('vendors.status', '1')
    ->count();    

    // Fetch records
    if ($searchByCategory_id!=''||$searchByLocation!='') {
        $totalRecordswithFilter = Vendor::select('vendors.*', 'count(*) as allcount')
        ->leftjoin("categories", "vendors.category", "=", "categories.id")
        ->where('vendors.status', '=', 1)
        // ->whereRaw('FIND_IN_SET('.$searchByCategory_id.',vendors.category)')
        ->where('vendors.category', '=', $searchByCategory_id)
        ->where('vendors.company_city', 'like', '%' .$searchByLocation . '%')
        ->where('vendors.name', 'like', '%' .$searchByVendor_name . '%')
        ->where(function ($query) use ($searchValue) {
            $query->where('categories.category_name', 'like', '%' .$searchValue . '%')
                  ->orWhere('vendors.mobile', 'like', '%' .$searchValue . '%')
                  ->orWhere('vendors.company_address', 'like', '%' .$searchValue . '%')
                 ->orWhere('vendors.email', 'like', '%' .$searchValue . '%');
        })
        ->count();

        $columnName = ($columnName=='category') ? 'categories.category_name' : $columnName;
        $records = Vendor::orderBy($columnName, $columnSortOrder)
        ->where('vendors.status', '1')
        // ->whereRaw('FIND_IN_SET('.$searchByCategory_id.',vendors.category)')
        ->where('vendors.category', '=', $searchByCategory_id)
        ->where('vendors.company_city', 'like', '%' .$searchByLocation . '%')
        ->where('vendors.name', 'like', '%' .$searchByVendor_name . '%')
        ->where(function ($query) use ($searchValue) {
            $query->where('categories.category_name', 'like', '%' .$searchValue . '%')
                ->orWhere('vendors.mobile', 'like', '%' .$searchValue . '%')
                ->orWhere('vendors.company_address', 'like', '%' .$searchValue . '%')
                ->orWhere('vendors.email', 'like', '%' .$searchValue . '%');
        })
        ->leftjoin("categories", "vendors.category", "=", "categories.id")
        ->select('vendors.*', 'categories.category_name')
        ->skip($start)
        ->take($rowperpage)
        ->get();
        // ->toSql();

    }else if ($searchByVendor_name!='') {
        $totalRecordswithFilter = Vendor::select('vendors.*', 'count(*) as allcount')
        ->leftjoin("categories", "vendors.category", "=", "categories.id")
        ->where('vendors.status', '=', 1)
        ->where('vendors.name', 'like', '%' .$searchByVendor_name . '%')
        ->where(function ($query) use ($searchValue) {
            $query->where('categories.category_name', 'like', '%' .$searchValue . '%')
                ->orWhere('vendors.mobile', 'like', '%' .$searchValue . '%')
                ->orWhere('vendors.company_address', 'like', '%' .$searchValue . '%')
                ->orWhere('vendors.email', 'like', '%' .$searchValue . '%');
        })
        ->count();

        $columnName = ($columnName=='category') ? 'categories.category_name' : $columnName;
        $records = Vendor::orderBy($columnName, $columnSortOrder)
        ->where('vendors.status', '1')
        ->where('vendors.name', 'like', '%' .$searchByVendor_name . '%')
        ->where(function ($query) use ($searchValue) {
            $query->where('categories.category_name', 'like', '%' .$searchValue . '%')
                ->orWhere('vendors.mobile', 'like', '%' .$searchValue . '%')
                ->orWhere('vendors.company_address', 'like', '%' .$searchValue . '%')
                ->orWhere('vendors.email', 'like', '%' .$searchValue . '%');
        })
        ->leftjoin("categories", "vendors.category", "=", "categories.id")
        ->select('vendors.*', 'categories.category_name')
        ->skip($start)
        ->take($rowperpage)
        ->get();
        // ->toSql();
    }else{
        $totalRecordswithFilter = Vendor::select('vendors.*', 'count(*) as allcount')
        ->leftjoin("categories", "vendors.category", "=", "categories.id")
        ->where('vendors.status', '1')
        ->where(function ($query) use ($searchValue) {
            $query->where('vendors.name', 'like', '%' .$searchValue . '%')
            ->orWhere('categories.category_name', 'like', '%' .$searchValue . '%')
            ->orWhere('vendors.mobile', 'like', '%' .$searchValue . '%')
            ->orWhere('vendors.company_address', 'like', '%' .$searchValue . '%')
            ->orWhere('vendors.email', 'like', '%' .$searchValue . '%');
        })
        ->count();

        $columnName = ($columnName=='category') ? 'categories.category_name' : $columnName;
        $records = Vendor::orderBy($columnName, $columnSortOrder)
        ->where('vendors.status', '1')
        ->where(function ($query) use ($searchValue) {
            $query->Where('vendors.name', 'like', '%' .$searchValue . '%')
            ->orWhere('categories.category_name', 'like', '%' .$searchValue . '%')
            ->orWhere('vendors.mobile', 'like', '%' .$searchValue . '%')
            ->orWhere('vendors.company_address', 'like', '%' .$searchValue . '%')
            ->orWhere('vendors.email', 'like', '%' .$searchValue . '%');
        })        
        ->leftjoin("categories", "vendors.category", "=", "categories.id")
        ->select('vendors.*', 'categories.category_name')
        ->skip($start)
        ->take($rowperpage)
        ->get();        
    }

    // dd($records);

    $data_arr = array();
    
    foreach($records as $record){
        if (is_numeric($record->mobile)) {
            $number = $record->mobile;
        }else{
            $number = '';
        }
        $email = str_replace('N/A-','',$record->email);
        if (is_numeric($email)) {
            $email = '';
        }else{
            $email = $record->email;
        }
       $id = $record->id;
       $name = $record->name;
       $category = $record->category_name;
       $mobile = $number;
       $company_address = $record->company_address;
       $email = $email;

       $data_arr[] = array(
         "id" => $id,
         "name" => $name,
         "category" => $category,
         "mobile" => $mobile,
         "company_address" => $company_address,
         "email" => $email,
         "action" => '
         <div class="visible-md visible-lg hidden-sm hidden-xs">
                <a href="'.url('admin/edit-vendor/'.$record->id).'" class="btn btn-sm btn-primary tooltips editQuote" data-placement="top" data-original-title="Edit">
                <i class="fa fa-edit"></i></a>
                <a href="javascript:;" onclick="deleteItem('.$record->id.')"  class="btn btn-sm btn-danger tooltips" data-placement="top" data-original-title="Remove">
                <i class="fa fa-times fa fa-white"></i></a>
            </div>
            <div class="visible-xs visible-sm hidden-md hidden-lg">
                <div class="btn-group">
                    <a class="btn btn-primary dropdown-toggle btn-sm" data-toggle="dropdown" href="#">
                        <i class="fa fa-cog"></i>
                    </a>
                    <ul role="menu" class="dropdown-menu pull-right dropdown-dark">
                        <li>
                        <a href="'.url('admin/view-sent-quote/'.$record->id).'" class="btn btn-sm btn-success">
                                <i class="fa fa-edit"></i> Edit
                            </a>
                        </li>
                        <li>
                            <a href="javascript:;" onclick="deleteItem('.$record->id.')" role="menuitem" tabindex="-1" href="#">
                                <i class="fa fa-times"></i> Remove
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
         ',
       );
    }

    $response = array(
       "draw" => intval($draw),
       "iTotalRecords" => $totalRecords,
       "iTotalDisplayRecords" => $totalRecordswithFilter,
       "aaData" => $data_arr
    );

    echo json_encode($response);
    exit;
  }
  /**
   * AJAX Request End
   */

  public function vendorDeleteRequests(Request $request)
  {
      $aResult = DB::table('vendors')->where('id', '=', $request->id)->delete();
      if($aResult){
          echo 1;
          exit;
      }
  }

   public function getRegisteredVendor(Request $request)
   {    
        $city_name = $category_id = '';
        $vendor_count = 0;
        $vendors = array();
        $categories = DB::select('SELECT * from categories where status="Active" ORDER BY category_name ASC');
        $cities = DB::table('locations')->select('id','city')->groupBy('city')->get();
        if($request->category_id && $request->city_id){
            $city_name = $request->city_id;
            $category_id = $request->category_id;
            $vendors = DB::select("SELECT v.*,c.category_name FROM vendors v LEFT JOIN categories c ON v.category=c.id WHERE v.company_city = '".$request->city_id."' AND FIND_IN_SET('".$request->category_id."', v.category) AND v.status='1' AND v.register_by_self='1'");
            $vendor_count = count($vendors);
        }
        return view('admin.vendors.registered_vendor',compact('cities','categories','vendor_count','vendors','city_name','category_id'));
   }

}
