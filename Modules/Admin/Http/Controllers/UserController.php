<?php

namespace Modules\Admin\Http\Controllers;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;

use App\User;
use Auth;
use Hash;
use DB;

//Importing laravel-permission models
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

//Enables us to output flash messaging
use Session;

class UserController extends Controller
{
    public function __construct() {
        //$this->middleware(['auth', 'isAdmin']); //isAdmin middleware lets only users with a //specific permission permission to access these resources
    }
	/**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
		$users = User::all();
        return view('admin.users.user')->with('users', $users);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */

	public function list_users()
    {
        $users = User::all();
        return view('admin.users')->with('users', $users);
    }

    /*
   AJAX request
   */
   public function ajax_user_list(Request $request){
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
    $totalRecords = User::count();
   
    $totalRecordswithFilter = User::select('*','count(*) as allcount')
    ->where('name', 'like', '%' .$searchValue . '%')
    ->orWhere('mobile', 'like', '%' .$searchValue . '%')
    ->orWhere('email', 'like', '%' .$searchValue . '%')
    ->count();

    $records = User::orderBy($columnName,$columnSortOrder)
    ->where('name', 'like', '%' .$searchValue . '%')
    ->orWhere('mobile', 'like', '%' .$searchValue . '%')
    ->orWhere('email', 'like', '%' .$searchValue . '%')
    ->select('*')
    ->skip($start)
    ->take($rowperpage)
    ->get();

    $data_arr = array();
    
    foreach($records as $record){
        if (is_numeric($record->mobile)) {
            $number = $record->mobile;
        }else{
            $number = '';
        }
       $id = $record->id;
       $name = $record->name;
       $mobile = $number;
       $email = $record->email;

       $data_arr[] = array(
        //  "id" => $id,
         "name" => $name,
         "mobile" => $mobile,
         "email" => $email,
         "action" => '
         <div class="visible-md visible-lg hidden-sm hidden-xs">
            <a href="'.url('admin/edit-user/'.$record->id).'" class="btn btn-sm btn-primary tooltips editQuote" data-placement="top" data-original-title="Edit"><i class="fa fa-edit"></i></a>
            <a href="javascript:;" onclick="deleteItem('.$record->id.')" class="btn btn-sm btn-danger tooltips" data-placement="top" data-original-title="Remove"><i class="fa fa-times fa fa-white"></i></a>
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

    public function add_users(Request $request)
    {
        $record = User::where('email',$request->email)->orWhere('mobile',$request->mobile)->get();
        if(count($record)){
            echo "Mobile Number or Emailid Already exists..!";
            exit;
            echo json_encode(array("success"=>0,'message'=>"Mobile Number or Emailid Already exists..!"));
        }
        $user = array( 
            ['name' => $request->name,
            'email' => $request->email,
            'mobile' => $request->mobile,
            'password' => Hash::make('123456'),]
        );
        if($user = DB::table('users')->insert(['name' => $request->name,'email' => $request->email,'mobile' => $request->mobile,'password' => Hash::make('123456')])){
            // echo json_encode(array("success"=>1,'message'=>"User insert successfully..!"));
            echo 1;
            exit;
        }
    }

    public function edit_user($user_id,Request $request)
    {
      $user = User::findOrFail($user_id); //Get user with specified id
      $roles = Role::get(); //Get all roles
      if($request->all()){
        $user = User::find($user_id);
        $user->name = $request->name;
        $user->email = $request->email;
        $user->mobile = $request->mobile;
        $user->save();
        return redirect()->route('admin.usersn')->with('flash_message','User updated successfully.');
      }
      return view('admin.users.edit_user', compact('user', 'roles')); 
    }

  public function userDeleteRequests(Request $request)
    {
        $aResult=User::where('id',$request->id)->delete();
        // $aResult = DB::table('users')->where('id', '=', $request->id)->delete();
        if($aResult){
            echo 1;
            exit;
        }
    }

  /**
   * AJAX Request End
   */

    public function create()
    {
        $roles = Role::get();
        return view('users.create', ['roles'=>$roles]);
    }

    

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
		$this->validate($request, [
            'name'=>'required|max:120',
            'email'=>'required|email|unique:users',
            'password'=>'required|min:6|confirmed'
			]);
		$user = User::create($request->only('email', 'name', 'password')); //Retrieving only the email and password data

        $roles = $request['roles']; //Retrieving the roles field
		//Checking if a role was selected
        if (isset($roles)) {

            foreach ($roles as $role) {
            $role_r = Role::where('id', '=', $role)->firstOrFail();
            $user->assignRole($role_r); //Assigning role to user
            }
        }
		//Redirect to the users.index view and display message
        return redirect()->route('users.index')
            ->with('flash_message',
             'User successfully added.');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
          return redirect('users');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
      $user = User::findOrFail($id); //Get user with specified id
      $roles = Role::get(); //Get all roles
      return view('users.edit', compact('user', 'roles')); //pass user and roles data to view
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
         $user = User::findOrFail($id); //Get role specified by id
		//Validate name, email and password fields
        $this->validate($request, [
            'name'=>'required|max:120',
            'email'=>'required|email|unique:users,email,'.$id,
            'password'=>'required|min:6|confirmed'
        ]);
        $input = $request->only(['name', 'email', 'password']); //Retreive the name, email and password fields
        $roles = $request['roles']; //Retreive all roles
        $user->fill($input)->save();

        if (isset($roles)) {
            $user->roles()->sync($roles);  //If one or more role is selected associate user to roles
        }
        else {
            $user->roles()->detach(); //If no role is selected remove exisiting role associated to a user
        }
        return redirect()->route('users.index')->with('flash_message','User successfully edited.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->delete();
        return redirect()->route('users.index')->with('flash_message','User successfully deleted.');
    }
}
