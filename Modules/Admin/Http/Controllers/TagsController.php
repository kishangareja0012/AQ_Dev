<?php

namespace Modules\Admin\Http\Controllers;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Category;
use App\Tag;
use DB;
use Excel;

//Importing laravel-permission models
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

//Enables us to output flash messaging
use Session;

class TagsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index($category){

        $categorydata = Category::find($category);
        $tags = Tag::where('category_id',$category)->orderBy('tag_name')->get();
        return view('admin.tags.index', compact("tags","categorydata"));
    }

    /**
     * Display a listing of the category tags.
     *
     * @return \Illuminate\Http\Response
     */
    public function manageTags(){
        $searchByTag = '';
        $searchByCategory = '';
        $aCategories = Category::orderBy('category_name','ASC')->get();
        $aTags = Tag::all();
        $aTagsData = DB::table('tags')->leftjoin("categories","tags.category_id","=","categories.id")
            ->select('tags.id as tag_id','tags.tag_name as tag_name',"categories.*")
            ->groupBy('tags.tag_name')
            ->get();
        return view('admin.tags.manage', compact("aCategories", "aTagsData", 'searchByTag', 'searchByCategory'));

    }

    public function manageTagsBkp()
    {
        ## Read value
$draw = $_POST['draw'];
$row = $_POST['start'];
$rowperpage = $_POST['length']; // Rows display per page
$columnIndex = $_POST['order'][0]['column']; // Column index
$columnName = $_POST['columns'][$columnIndex]['data']; // Column name
$columnSortOrder = $_POST['order'][0]['dir']; // asc or desc
$searchValue = $_POST['search']['value']; // Search value

## Custom Field value
$searchByTag = $_POST['searchByTag'];
$searchByCategory = $_POST['searchByCategory'];

## Search
$searchQuery = " ";
if($searchByTag != ''){
   $searchQuery .= " and (tag_name like '%".$searchByTag."%' ) ";
}
if($searchByCategory != ''){
   $searchQuery .= " and (gender='".$searchByCategory."') ";
}
if($searchValue != ''){
   $searchQuery .= " and (emp_name like '%".$searchValue."%' or
      email like '%".$searchValue."%' or
      city like'%".$searchValue."%' ) ";
}

## Total number of records without filtering
$sel = mysqli_query($con,"select count(*) as allcount from employee");
$records = mysqli_fetch_assoc($sel);
$totalRecords = $records['allcount'];

## Total number of records with filtering
$sel = mysqli_query($con,"select count(*) as allcount from employee WHERE 1 ".$searchQuery);
$records = mysqli_fetch_assoc($sel);
$totalRecordwithFilter = $records['allcount'];

## Fetch records
$empQuery = "select * from employee WHERE 1 ".$searchQuery." order by ".$columnName." ".$columnSortOrder." limit ".$row.",".$rowperpage;
$empRecords = mysqli_query($con, $empQuery);
$data = array();

while ($row = mysqli_fetch_assoc($empRecords)) {
   $data[] = array(
     "emp_name"=>$row['emp_name'],
     "email"=>$row['email'],
     "gender"=>$row['gender'],
     "salary"=>$row['salary'],
     "city"=>$row['city']
   );
}

    ## Response
    $response = array(
    "draw" => intval($draw),
    "iTotalRecords" => $totalRecords,
    "iTotalDisplayRecords" => $totalRecordwithFilter,
    "aaData" => $data
    );

    echo json_encode($response);

}


    /**
     * Search tags.
     *
     * @return \Illuminate\Http\Response
     */
    public function searchTags(Request $request){
        ## Custom Field value
        $searchByTag = $request->searchByTag;
        $searchByCategory = $request->searchByCategory;
        $aCategories = Category::all();
        if($searchByTag != '' && $searchByCategory != ''){
            $aTagsData = DB::table('tags')->leftjoin("categories","tags.category_id","=","categories.id")
                ->select('tags.id as tag_id','tags.tag_name as tag_name',"categories.*")
                ->where('categories.id', '=', $searchByCategory)
                ->where('tags.tag_name', 'like', '%' . $searchByTag . '%')
                ->get();
        }else if($searchByTag != ''&& $searchByCategory == ''){
            $aTagsData = DB::table('tags')->leftjoin("categories","tags.category_id","=","categories.id")
                ->select('tags.id as tag_id','tags.tag_name as tag_name',"categories.*")
                //->where('categories.id', '=', $searchByCategory)
                ->where('tags.tag_name', 'like', '%' . $searchByTag . '%')
                ->get();
        }else if($searchByCategory != ''&& $searchByTag == ''){
            $aTagsData = DB::table('tags')->leftjoin("categories","tags.category_id","=","categories.id")
                ->select('tags.id as tag_id','tags.tag_name as tag_name',"categories.*")
                ->where('categories.id', '=', $searchByCategory)
                //->where('tags.tag_name', 'like', '%' . $searchByTag . '%')
                ->get();
        }else if($searchByTag == '' && $searchByCategory == ''){
            $aTagsData = DB::table('tags')->leftjoin("categories","tags.category_id","=","categories.id")
                ->select('tags.id as tag_id','tags.tag_name as tag_name',"categories.*")
                ->get();
        }
        return view('admin.tags.manage', compact("aCategories", "aTagsData", 'searchByTag', 'searchByCategory'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function addTagByCategory(Request $request){

        $aCategories = Category::all();
        //echo "<pre>";print_r($aCategories);die();
        $aTags = Tag::all();
        //echo "<pre>";print_r($aTags);die();
        $sTagName = $request->tag_name;
        $sCategory = $request->add_category;
        $this->validate($request, [
            'tag_name'=>'required|max:120',
		]);

        $sResult = Tag::create([
            'tag_name' => $sTagName,
            'category_id' => $sCategory
        ]);
        //Redirect to the categories.index view and display message
        return redirect()->route('admin.manage.tags')->with('flash_message','Tag Added Successfully.');
    }

    /**
     * Delete category tag.
     *
     * @return \Illuminate\Http\Response
     */
    public function delCategoryTag(Request $request)
    {
        $sCatTagId = $request->id;
        $aResult = DB::table('tags')->where('id', '=', $sCatTagId)->delete();
        //DB::table('users')->where('votes', '>', 100)->delete();
        if($aResult){
            echo 1;
            exit;
        }

    }

    /**
     * Import tags.
     *
     * @return \Illuminate\Http\Response
     */
    public function importData(Request $request){

        $request->validate([
            'import_cat_tags' => 'required'
        ]);
        $sCategory = $request->bulk_category;
        $path = $request->file('import_cat_tags')->getRealPath();
        $data = Excel::load($path)->get();
        // echo "<pre>";print_r($data);die();

        if($data->count()){
            foreach ($data as $key => $value) {
                $aCatTags[] = ['tag_name' => $value->tag_name,
                    'category_id' => $sCategory
                    ];

            }
            //echo "<pre>";print_r($aCatTags);die();
            if(!empty($aCatTags)){
                foreach($aCatTags as $tData){
                    $checkExists = DB::table('tags')
                            ->where('tag_name', '=', $tData['tag_name'])
                            ->where('category_id', '=', $sCategory)
                            ->exists(); // this returns a true or false
                    if(!$checkExists){
                        Tag::insert($aCatTags);
                    }

                }
            }

        }

        //Redirect to the view and display message
        return redirect()->route('admin.manage.tags')->with('flash_message','Category Tags Imported Successfully.');
    }
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function bulkUpload(Request $request, $category)
    {
            //$tag_name = "dfdsfs223rs";
           // echo $count = Tag::where('category_id',$category)->where('tag_name',$tag_name)->count();
            //exit;

			// file upload
			$tags_file = $request->tags;
            //$path = public_path('../public/assets/images/quotes/');
            //$filename = time() . '.' . $image->getClientOriginalExtension();
            //$image->move($path, $filename);

            $file = fopen($tags_file,"r");

            $cnt = 1;
            while(! feof($file))
            {
                echo $cnt." ".fgets($file). "<br />";
                $tag_name = trim(fgets($file));
                if(!empty($tag_name)){
                    $tags = Tag::where('category_id',$category)->where('tag_name',$tag_name)->count();

                    if($tags == 0){
                        $sResult = Tag::create([
                            'tag_name' => $tag_name,
                            'category_id' => $category
                        ]);
                        $cnt++;
                    }
                }

            }
            //exit;


		//Redirect to the categories.index view and display message
        return redirect()->route('admin.category-tags',['category_id'=>$category])->with('flash_message','Tags Added Successfully.');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        return redirect('categories');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
		$sCategory = Category::findOrFail($id); //Get category with specified id
        return view('categories.edit');
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
        $category = Category::findOrFail($id);

		//Validate category_name field
        $this->validate($request, [
            'category_name'=>'required|max:120|unique:categories,category_name,'.$id,
        ]);
        $input = $request->only(['category_name']); //Retreive the name, email and password fields
        $category->fill($input)->save();

        return redirect()->route('categories.index')->with('flash_message','Category Successfully Updated.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
		$category = Category::findOrFail($id);
        $category->delete();

        return redirect()->route('categories.index')->with('flash_message','Category Successfully Deleted.');
    }
}
