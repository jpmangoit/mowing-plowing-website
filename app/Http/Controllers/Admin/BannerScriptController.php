<?php

namespace App\Http\Controllers\Admin;

use App\Models\BannerScript;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class BannerScriptController extends AdminBaseController
{
  public function index(){
    $descriprtion= BannerScript::first();
    return view('admin.bannerscript.index',compact('descriprtion'));
  }

  // public function storeBannerScrpt(Request $request){
  //   if($request->id==null){
  //     BannerScript::create(['description'=>$request->editor1]);
  //   }
  //   else{
  //     BannerScript::where('id',$request->id)->update(['description'=>$request->editor1]);
  //   }
    
  //     return redirect()->back()->with('success', 'Banner Script Save Successfully');
  // }

  public function removeBanner($id){
    $banner = BannerScript::find($id);

    if ($banner) {
        $banner->delete();
        return redirect()->back()->with('success', 'Banner has been removed');
    } else {
        return redirect()->back()->with('error', 'Banner not found');
    }
  }

  public function storeBannerScrpt(Request $request){
    $request->validate([
      'image' => 'required|image',
    ]);
  
    if (!in_array($request->file('image')->getClientOriginalExtension(), ['jpg', 'png', 'svg', 'jpeg','gif'])) {
        return redirect()->back()->with('error', 'Allowed image formats are: JPEG, PNG, JPG, GIF, SVG. ');
    }

    $imageName = time() . '.' . $request->image->extension();
    $request->image->move(public_path('images'), $imageName);
    $banner = BannerScript::updateOrCreate([], ['description' => 'images/' . $imageName]);
    
    if ($banner) {
        return redirect()->back()->with('success', 'Image uploaded successfully.');
    }
  }
}
