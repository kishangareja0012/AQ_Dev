<?php
namespace Modules\Admin\Http\Controllers;
use Illuminate\Routing\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\User;
use App\Quotes;
use App\VendorQuote;
use App\Vendor;
use DB;
use Illuminate\Support\Facades\Input;
use Excel;
use Illuminate\Foundation\Validation\ValidatesRequests;


class AdminController extends Controller
{
	use AuthenticatesUsers;

	protected $redirectTo = 'admin/dashboard';

	public function __construct() {
        // $this->middleware(['auth', 'isAdmin']); //isAdmin middleware lets only users with a //specific permission permission to access these resources
    }
	public function index()
	{
		// // Auth::logout();
		$users_count = User::count();
        $vendors_count = Vendor::count();
        $register_vendors_count = Vendor::where('register_by_self',1)->count();
		$quotes_requests_count = Quotes::count();
		$quotes_sent_to_vendors_count = VendorQuote::count();
		//$quotes_sent_to_vendors_count = VendorQuote::where('status', "Quote Raised")->count();
		$quotes_responses_count = VendorQuote::where('isResponded', 1)->count();
		$aMiscCatId = DB::table('categories')->where('category_name', 'Miscellaneous')->value('id');
		//print_r($aMiscCatId);die();//Quotes::where('category', '=', 'Miscellanious')->first();
		$misc_quotes_count = Quotes::where('category', $aMiscCatId)->count();
		
		return view('admin.dashboard',compact('misc_quotes_count', 'users_count','vendors_count','quotes_sent_to_vendors_count', 'quotes_requests_count','quotes_responses_count','register_vendors_count'));
	}
	public function mail()
	{
		return view('mail');
	}
	
	/**
		* Display the the Profile page.
     *
     * @return Response
     */
	public function profile(){
		return view("admin/profile");
    }

    public function importLocation(){
        $locations = DB::select('select * from locations');
        return view('admin.import-location',compact('locations'));           
    } 

    public function addLocation(Request $request)
    {
        if (isset($request) && $request->method=='post') {
            $validatedData = $request->validate([
                'city'=>'required',
                'location'=>'required',
                'area'=>'required',
                'pincode'=>'required',
            ]);


            $data= array(
                'city'=>$request->city,
                'location'=>$request->location,
                'area'=>$request->area,
                'pincode'=>$request->pincode,
                'active'=>$request->active,
            );
            $query_insert = DB::table('locations')->insert($data);

            return redirect()->route('admin.import.location')
            ->with('flash_message', 'Location added successfully..!');
        }
        return view('admin.add_location');
    }
       
    /** 
     * Ajax Location List
     */
    public function get_location(Request $request)
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

        // Total records
        $locations = DB::select('select * from locations');
        $totalRecords = count($locations);

        // dd($totalRecords);
    
        $totalRecordswithFilter = DB::table('locations')
        ->select('*,count(*) AS allcount')
        ->where('city', 'like', '%' .$searchValue . '%')
        ->orWhere('location', 'like', '%' .$searchValue . '%')
        ->orWhere('area', 'like', '%' .$searchValue . '%')
        ->orWhere('pincode', 'like', '%' .$searchValue . '%')
        ->count();

        $records = DB::table('locations')
        ->where('city', 'like', '%' .$searchValue . '%')
        ->orWhere('location', 'like', '%' .$searchValue . '%')
        ->orWhere('area', 'like', '%' .$searchValue . '%')
        ->orWhere('pincode', 'like', '%' .$searchValue . '%')
        ->orderBy($columnName,$columnSortOrder)
        ->skip($start)
        ->take($rowperpage)
        ->get();

        $data_arr = array();

        foreach($records as $record){
        // $id = $record->id;
        $city = $record->city;
        $location = $record->location;
        $area = $record->area;
        $pincode = $record->pincode;
        $active = $record->active;

        $data_arr[] = array(
            //  "id" => $id,
            "city" => $city,
            "location" => $location,
            "area" => $area,
            "pincode" => $pincode,
            "active" => $active,
            "action" => '
            <div class="visible-md visible-lg hidden-sm hidden-xs">
                <a href="'.url('admin/edit-location/'.$record->id).'" class="btn btn-sm btn-primary tooltips editQuote" data-placement="top" data-original-title="Edit"><i class="fa fa-edit"></i></a>
                <a href="'.url('admin/remove-location/'.$record->id).'" class="btn btn-sm btn-danger tooltips" data-placement="top" data-original-title="Remove"><i class="fa fa-times fa fa-white"></i></a>
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

    public function importLocationData(Request $request)
    {
        $request->validate([
            'import_file' => 'required'
        ]);
        try {
            // dd(Input::file('import_file'));
            Excel::load(Input::file('import_file'), function ($reader) {
                /* echo "<pre>";print_r($reader->toArray()); // die();
                echo "<pre>"; */
                foreach ($reader->toArray() as $row) {
                    
                    $area = substr($row['area'], 0, strpos($row['area'], "_x00"));
                    $city = trim($row['city']);                   
                    $locations = DB::table('locations')->where('city','LIKE',"%{$city}%")->where('area','LIKE',"%{$area}%")->first();
                    if ($locations) {
                        $update_row = array(
                            'latitude'=>$row['latitude'] ? $row['latitude'] : '',
                            'longitude'=>$row['longitude'] ? $row['longitude'] : '',
                        );
                        DB::table('locations')->where('id',$locations->id)->update($update_row);
                    }else{
                        $insert_row = array(
                            'city'=>$row['city'] ? $row['city'] : '',
                            'location'=>$row['area'] ? $row['area'] : '',
                            'area'=>$row['area'] ? $row['area'] : '',
                            'circle'=>'',
                            'pincode'=>'',
                            'latitude'=>$row['lattitude'] ? $row['lattitude'] : '',
                            'longitude'=>$row['longitude'] ? $row['longitude'] : '',
                        );
                        DB::table('locations')->insert($insert_row);
                    }
                }
            });
            return back()->with('success', 'Imported successfully.');
        } catch (\Exception $e) {
            \Session::flash('error', $e->getMessage());
            return back()->with('failed', $e->getMessage());
        }
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function downloadData($type)
    {
        ob_end_clean(); // this
        ob_start(); // and this
        $data = \DB::table('locations')->get()->toArray();
        $data = array_map(function ($value) {
				    return (array)$value;
				}, $data);
        return Excel::create('exportLocations', function($excel) use ($data) {
            $excel->sheet('exportLocations', function($sheet) use ($data)
            {
                $sheet->fromArray($data, null, 'A1', true);
            });
        })->export($type);
    }

    public function edit_location($id){
		$location_id = $id;
		$location = \DB::table('locations')->where('id',$id)->first();
        return view('admin.edit_location', compact('location','location_id'));
    }

    public function update_location($location_id, Request $request)
    {
    	$values = $request->all();
        $city = array(
        	"city"=> $values['city'],
        	"location"=> $values['location'],
        	"area"=> $values['area'],
        	"pincode"=> $values['pincode'],
        );
		
		$affected = DB::table('locations')
              ->where('id',$location_id )
              ->update($city);

        return redirect()->route('admin.import.location')
            ->with('flash_message', 'Location details updated successfully..!');
    }

    public function remove_location($id)
    {
        $aResult = DB::table('locations')->where('id', '=', $id)->delete();
        if($aResult){
           return redirect()->route('admin.import.location')
            ->with('flash_message', 'Location deleted successfully..!');
        }
    }

    public function getCities(Request $request)
    {
        return view('admin.cities-list');
    }

    public function ajaxGetCity(Request $request)
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

        // Total records
        $cities = DB::table('locations')->groupBy('city')->get();
        $totalRecords = count($cities);
        // dd($totalRecords);
    
        $totalRecordswithFilter = DB::table('locations')
        // ->select('*,count(*) AS allcount')
        ->groupBy('city')
        ->where('city', 'like', '%' .$searchValue . '%')
        ->get();


        $records = DB::table('locations')
        // ->select('id,city')
        ->groupBy('city')
        ->where('city', 'like', '%' .$searchValue . '%')
        ->orderBy($columnName,$columnSortOrder)
        ->skip($start)
        ->take($rowperpage)
        ->get();

        $data_arr = array();

        foreach($records as $record){
        // $id = $record->id;
        $city = $record->city;
        $status = $record->active;

        $data_arr[] = array(
            // "id" => $id,
            "city" => $city,
            "status" => $status,
            "action" => '
            <div class="visible-md visible-lg hidden-sm hidden-xs">
                <a href="javascript:;" onclick="editItem('.$record->id.')" class="btn btn-sm btn-primary tooltips editQuote" data-placement="top" data-original-title="Edit"><i class="fa fa-edit"></i></a>
            </div>
            ',
        );
        }

        $response = array(
        "draw" => intval($draw),
        "iTotalRecords" => $totalRecords,
        "iTotalDisplayRecords" => count($totalRecordswithFilter),
        "aaData" => $data_arr
        );

        echo json_encode($response);
        exit; 
    }

    public function editCity(Request $request)
    {   
        $this->data['city'] = DB::table('locations')->where('id',$request->id)->first();
        $this->data['page_title'] = 'Edit City';
        $returnHTML = view('admin.edit_city')->with($this->data)->render();
        return response()->json(array('success' => true, 'popup_html' => $returnHTML));
    }

    public function updateCity(Request $request)
    {
        $city = array(
        	"active"=> $request->active,
        );
		
        DB::table('locations')->where('city', 'like', $request->city)->update($city);
        return redirect()->route('admin.cities')
            ->with('flash_message', 'City update successfully..!');

    }
    

}
