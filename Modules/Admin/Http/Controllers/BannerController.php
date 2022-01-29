<?php

namespace Modules\Admin\Http\Controllers;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Category;
use App\Banner;
use DB;
use Excel;

//Importing laravel-permission models
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

//Enables us to output flash messaging
use Session;

class BannerController extends Controller
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
    public function manageBanner(){        
        $cities = DB::table('locations')->select('id','city')->groupBy('city')->get();
        // $banners = Banner::all();

        $banners = DB::table('city_banner AS c')
            ->join('locations AS l', 'c.city_id', '=', 'l.id')
            ->select('c.*', 'l.city')
            ->get();
        return view('admin.city_banner.manage', compact("cities", "banners"));
    }    


     /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
     public function submit_banner(Request $request){
        
        $this->validate($request, [
            'city_id'=>'required|max:120',
            'status'=>'required',
        ]);
        // file upload
        if ($request->hasFile('banner')) {
            $image = $request->banner;
            $path = public_path('../public/assets/images/banners/');
            $filename = time() . '.' . $image->getClientOriginalExtension();
            $image->move($path, $filename);
        }
        if ($request->hasFile('mobile_banner')) {
            $image = $request->mobile_banner;
            $path = public_path('../public/assets/images/banners/');
            $mobile_banner = time() . '.' . $image->getClientOriginalExtension();
            $image->move($path, $mobile_banner);
        }
        // dd($mobile_banner);
        $city_id = $request->city_id;
        $sResult = Banner::create([
            'city_id' => $city_id,
            'banner_image' => $filename,
            'mobile_banner' => $mobile_banner,
            'status' => $request->status
        ]);
        //Redirect to the categories.index view and display message        
        return redirect()->route('admin.banner')->with('flash_message','Banner Added Successfully.');
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
    public function update(Request $request)
    {
        $filename = $request->old_banner;
        $mobile_banner = $request->old_mobile_banner;
        if ($request->hasFile('banner')) {
            $image = $request->banner;
            $path = public_path('../public/assets/images/banners/');
            $filename = time() . '.' . $image->getClientOriginalExtension();
            $image->move($path, $filename);
        }
        if ($request->hasFile('mobile_banner')) {
            $image = $request->mobile_banner;
            $path = public_path('../public/assets/images/banners/');
            $mobile_banner = time() . '.' . $image->getClientOriginalExtension();
            $image->move($path, $mobile_banner);
        }
        
        $category = Banner::find($request->id);
        $category->city_id = $request->city_id;
        $category->banner_image = $filename;
        $category->mobile_banner = $mobile_banner;
        $category->status = $request->status;
        $category->save();
        return redirect()->route('admin.banner')->with('flash_message','Banner Successfully Updated.');
    }

     /**
     * Delete category tag.
     *
     * @return \Illuminate\Http\Response
     */
    public function deleteBanner(Request $request)
    {
        $id = $request->id;
        $aResult = DB::table('city_banner')->where('id', '=', $id)->delete();
        if($aResult){
            echo 1;
            exit;
        }
       
    }

     public function editBanner(Request $request)
    {
        $this->data['cities'] = DB::table('locations')->select('id','city')->groupBy('city')->get();
        $this->data['banner'] = Banner::find($request->id);
        $this->data['page_title'] = 'Edit Banner';
        $returnHTML = view('admin.city_banner.edit')->with($this->data)->render();
        return response()->json(array('success' => true, 'popup_html' => $returnHTML));
    }

    /** 
     * Default Banner Management
     */
    public function defaultBanners()
    {
        $banners = DB::table('default_banners')->orderBy('id','DESC')->get();
        return view('admin.default_banners.manage',compact('banners'));
    }

    public function storeDefaultBanners(Request $request)
    {
        // dd($request->all());
        // file upload
        if ($request->hasFile('banner')) {
            $image = $request->banner;
            $path = public_path('../public/assets/images/banners/');
            $filename = rand().time() . '.' . $image->getClientOriginalExtension();
            $image->move($path, $filename);
        }
        if ($request->hasFile('mobile_banner')) {
            $image = $request->mobile_banner;
            $path = public_path('../public/assets/images/banners/');
            $mobile_banner = rand().time() . '.' . $image->getClientOriginalExtension();
            $image->move($path, $mobile_banner);
        }
        $result = DB::table('default_banners')->insert([
            'banner_image' => $filename,
            'mobile_banner' => $mobile_banner,
            'status' => $request->status ? $request->status : 'N',
        ]);       
        return redirect()->route('admin.default_banners')->with('flash_message','Banner Added Successfully.');
    }

    public function editDefaultBanner(Request $request)
    {
        $this->data['banner'] = DB::table('default_banners')->where('id',$request->id)->first();
        $this->data['page_title'] = 'Edit Banner';
        $returnHTML = view('admin.default_banners.edit')->with($this->data)->render();
        return response()->json(array('success' => true, 'popup_html' => $returnHTML));
    }

    public function updateDefaultBanner(Request $request)
    {
        $filename = $request->old_banner;
        $mobile_banner = $request->old_mobile_banner;
        if ($request->hasFile('banner')) {
            $image = $request->banner;
            $path = public_path('../public/assets/images/banners/');
            $filename = time() . '.' . $image->getClientOriginalExtension();
            $image->move($path, $filename);
        }
        if ($request->hasFile('mobile_banner')) {
            $image = $request->mobile_banner;
            $path = public_path('../public/assets/images/banners/');
            $mobile_banner = time() . '.' . $image->getClientOriginalExtension();
            $image->move($path, $mobile_banner);
        }

        $affected = DB::table('default_banners')
              ->where('id', $request->id)
              ->update([
                'banner_image' => $filename,
                'mobile_banner' => $mobile_banner,
                'status' => $request->status ? $request->status : 'N'
            ]);

        return redirect()->route('admin.default_banners')->with('flash_message','Banner Successfully Updated.');
    }

    public function deleteDefaultBanner(Request $request)
    {
        $id = $request->id;
        $aResult = DB::table('default_banners')->where('id', '=', $id)->delete();
        if($aResult){
            echo 1;
            exit;
        }
    }
}
