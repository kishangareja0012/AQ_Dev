<?php

namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Category;

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
		$categories = Category::orderBy('category_name', 'ASC')->get();
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
        
        if ($request->type=='edit') {
             $this->validate($request, [
            'category_name'=>'required|max:120',
            'display_name'=>'required|max:120'
        ]);

            $category = Category::find($request->id);
            $category->category_name = $request->category_name;
            $category->display_name = $request->display_name;
            $category->save();

            $message = ' Updated ';
        }else{
            $this->validate($request, [
            'category_name'=>'required|max:120|unique:categories'
        ]);
            $sCategory = $request->category_name;
            $display_name = $request->display_name;
            $sResult = Category::create([
                'category_name' => $sCategory,
                'display_name' => $display_name
            ]);
            $message = ' Added ';
        }
        //Redirect to the categories.index view and display message
        return redirect()->route('admin.categories')->with('flash_message','Category Successfully '.$message.' .');
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
