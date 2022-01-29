<?php

namespace Modules\Admin\Http\Controllers;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Words;
use DB;
use Excel;

//Importing laravel-permission models
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

//Enables us to output flash messaging
use Session;

class NeutralwordsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $words = Words::where('type',0)->orderBy('word')->get();
        return view('admin.neutralwords.index', compact("words"));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //echo "HEllp"; exit;
        //echo $request->category; exit; 
        $this->validate($request, [
            'word'=>'required|max:120',
		]);
        //$sCategory = $request->tag_name;
        
        $sResult = Words::create([
            'word' => $request->word,
            'type' => 0
        ]);
		//Redirect to the categories.index view and display message
        return redirect()->route('admin.neutralwords')->with('flash_message','Neutral Word Added Successfully.');
    }

    /**
     * Delete neutral word.
     *
     * @return \Illuminate\Http\Response
     */
    public function delNeutralWord(Request $request)
    {
        $sNeutralId = $request->id;
        $aResult = DB::table('words')->where('id', '=', $sNeutralId)->delete();
        //DB::table('users')->where('votes', '>', 100)->delete();
        if($aResult){
            echo 1;
            exit;
        }
       
    }

    /**
     * Import neutral words.
     *
     * @return \Illuminate\Http\Response
     */
    public function importData(Request $request)
    {
        //echo "Hai";die();        
        $request->validate([
            'import_neutralwords' => 'required'
        ]);
        //$sCategory = $request->category;
        $path = $request->file('import_neutralwords')->getRealPath();
        
        $data = Excel::load($path)->get();
        //echo "<pre>";print_r($data);die();
        
        if($data->count()){
            foreach ($data as $key => $value) {
                $sType = 0;
                $aNWords[] = ['word' => $value->word,
                            'type' => $sType                            
                            ];
                
            }
            if(!empty($aNWords)){
                foreach($aNWords as $wData){
                    $checkExists = Words::where('word', $wData['word'])->exists(); // this returns a true or false
                    if(!$checkExists){
                        Words::insert($aBWords);                        
                    }
                    
                }
            }
            
        }
        //Redirect to the view and display message
        return redirect()->route('admin.neutralwords')->with('flash_message','Neutral Words Added Successfully.');
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function bulkUpload(Request $request)
    {
            //$tag_name = "dfdsfs223rs";
           // echo $count = Tag::where('category_id',$category)->where('tag_name',$tag_name)->count();
            //exit;

			// file upload
			$words_file = $request->words;
            //$path = public_path('../public/assets/images/quotes/');
            //$filename = time() . '.' . $image->getClientOriginalExtension();
            //$image->move($path, $filename);

            $file = fopen($words_file,"r");

            $cnt = 1;
            while(! feof($file))
            {
                //echo $cnt." ".fgets($file). "<br />";
                $word = trim(fgets($file));
                if(!empty($word)){
                    $words = Words::where('type',0)->where('word',$word)->count();                       
                    if($words == 0){
                        $sResult = Words::create([
                            'word' => $word,
                            'type' => 0
                        ]);
                        $cnt++;
                    }
                }

            }
            //exit;
        
        
		//Redirect to the categories.index view and display message
        return redirect()->route('admin.neutralwords')->with('flash_message','Neutral Words Added Successfully.');
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
