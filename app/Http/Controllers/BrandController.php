<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Manufacturer;
use Illuminate\Http\Request;

class BrandController extends Controller
{
    public function index()
    {
        $brands = Brand::with(['manufacturer'])
            ->withCount('products')
            ->latest()
            ->get();

        return view('brands.index', compact('brands'));
    }

    public function create()
    {
        $manufacturers = Manufacturer::all();
        return view('brands.create', compact('manufacturers'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'manufacturer_id' => 'required|exists:manufacturers,id',
            'name' => 'required|string|max:255',
        ]);

        Brand::create([
            'manufacturer_id' => $request->manufacturer_id,
            'name' => $request->name,
        ]);

        return redirect()->route('brands.index')
            ->with('success', 'Brand created successfully.');
    }

    public function edit(Brand $brand)
    {
        $manufacturers = Manufacturer::all();
        return view('brands.edit', compact('brand', 'manufacturers'));
    }

    public function update(Request $request, Brand $brand)
    {
        $request->validate([
            'manufacturer_id' => 'required|exists:manufacturers,id',
            'name' => 'required|string|max:255',
        ]);

        $brand->update([
            'manufacturer_id' => $request->manufacturer_id,
            'name' => $request->name,
        ]);

        return redirect()->route('brands.index')
            ->with('success', 'Brand updated successfully.');
    }

    public function destroy(Brand $brand)
    {
        $brand->delete();

        return redirect()->route('brands.index')
            ->with('success', 'Brand deleted successfully.');
    }
}
