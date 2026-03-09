<?php

namespace App\Http\Controllers;

use App\Models\Menu;
use Illuminate\Http\Request;

class MenuController extends Controller
{
    public function index()
    {
        $menus = Menu::with('parent')->orderBy('order')->get();
        $parentMenus = Menu::whereNull('parent_id')->get();


        return view('menus.index', compact('menus', 'parentMenus'));

    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required'
        ]);

        Menu::create($request->all());

        return redirect()->back()
            ->with('success', 'Menu Created Successfully');
    }

    public function edit($id)
    {
        $menu = Menu::findOrFail($id);
        return response()->json($menu);
    }

    public function update(Request $request, $id)
    {
        $menu = Menu::findOrFail($id);
        $menu->update($request->all());

        return redirect()->back()
            ->with('success', 'Menu Updated Successfully');
    }

    public function destroy($id)
    {
        Menu::findOrFail($id)->delete();

        return redirect()->back()
            ->with('success', 'Menu Deleted Successfully');
    }
}
