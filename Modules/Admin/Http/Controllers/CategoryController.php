<?php

namespace Modules\Admin\Http\Controllers;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Category;
use App\Tag;
use App\Words;

//Importing laravel-permission models
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

//Enables us to output flash messaging
use Session;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
		$categories = Category::all();
        return view('admin.categories.index', compact("categories"));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $image_file = $request->old_image;
        if ($request->hasFile('category_image')) {
            $image = $request->category_image;
            $path = public_path('assets/images/category/');
            $image_file = 'cat-'.time() . '.' . $image->getClientOriginalExtension();
            $image->move($path, $image_file);
        }

       if ($request->type=='edit') {
             $this->validate($request, [
            'category_name'=>'required|max:120',
            'display_name'=>'required|max:120'
            ]);

            $category = Category::find($request->id);
            $category->category_name = $request->category_name;
            $category->display_name = $request->display_name;
            $category->category_image = $image_file;
            $category->save();
            $message = ' Updated ';
        }else{
            $this->validate($request, [
            'category_name'=>'required|max:120|unique:categories',
            'display_name'=>'required|max:120'
            ]);

            $category = new Category;
            $category->category_name = $request->category_name;
            $category->display_name = $request->display_name;    
            $category->category_image = $image_file;
            $category->save();
            $message = ' Added ';
        }
		//Redirect to the categories.index view and display message
        return redirect()->route('admin.categories')->with('flash_message','Category Successfully '.$message.' .');
    }

       /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */

    public function editCategory(Request $request)
    {
        $this->data['category'] = Category::find($request->id);
        $this->data['page_title'] = 'Edit Category';
        $returnHTML = view('admin.categories.edit')->with($this->data)->render();
        return response()->json(array('success' => true, 'popup_html' => $returnHTML));
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

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function findcategory()
    {
        $sentence = "";
        return view('admin.categories.findcategory', compact("sentence"));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function submitFindCategory(Request $request)
    {
        $this->validate($request, [
            'sentence'=>'required',
		]);
        
        // Remove spaces from sentence
        $sentence = trim($request->sentence); 

        // Break sentence into words
        $sentence_words = explode(" ", $sentence); 

        // Check for any empty words
        $sentence_words_better = array();
        foreach($sentence_words as $word){
            $word = strtolower(trim($word));
            if(!empty($word)){
                $sentence_words_better[$word] = $word;
            }
        }

        // Check words fall under neutral(0), banned words(-1)
        $checkwords = Words::whereIn('word',$sentence_words_better)->get(); 
        if(count($checkwords) > 0 ){

            foreach($checkwords as $checkword){
                $key = strtolower($checkword->word);
                
                // If negative words found, redirect with message.
                if($checkword->type == -1){
                    return redirect('admin/find-category')->withErrors(['We found some blocked words']);
                }

                // Ignore neutral word. Remove from array.
                unset($sentence_words_better[$key]); 
            }
        }
        
        // Words after removing neutral(0), banned words(-1)
        //echo "<h1>Entered Data</h1><pre>"; echo $sentence."<br>"; 
        //echo "<h1>Final Words</h1><pre>"; print_r($sentence_words_better); 

        $category_wise_tags = array();

        // Check final words with category keywords
        if(count($sentence_words_better) > 0 ){
            $final_words = array_values($sentence_words_better);
            
            $checktags = Tag::join('categories','tags.category_id','=','categories.id')->whereIn('tag_name',$final_words)->select('tags.*','categories.category_name')->get(); 
            if(count($checktags) > 0 ){
                foreach($checktags as $checktag){
                    $category = $checktag->category_name;
                    $category_wise_tags[$category][] = array('id'=>$checktag->id,'tag_name'=>$checktag->tag_name,'category_id'=>$checktag->category_id,'category_name'=>$checktag->category_name);
                }    
            }
        }     
        // Redirect to the categories.index view and display message
        return view('admin.categories.findcategory', compact("category_wise_tags","sentence"));
    }
}
