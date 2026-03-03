<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Manufacturer;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;


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
    public function data()
{
    $brands = Brand::with('manufacturer')->withCount('products');

    return DataTables::of($brands)
        ->addIndexColumn()
        ->addColumn('manufacturer', function ($row) {
            return $row->manufacturer->name ?? '-';
        })
        ->addColumn('total_products', function ($row) {
            return $row->products_count;
        })
        ->addColumn('action', function ($row) {
            $editUrl = route('brands.edit', $row->id);
            $deleteUrl = route('brands.destroy', $row->id);

            return '
                <a href="'.$editUrl.'" class="btn btn-sm btn-info">Edit</a>
                <form action="'.$deleteUrl.'" method="POST" style="display:inline;">
                    '.csrf_field().'
                    '.method_field("DELETE").'
                    <button class="btn btn-sm btn-danger"
                        onclick="return confirm(\'Delete this brand?\')">
                        Delete
                    </button>
                </form>
            ';
        })
        ->rawColumns(['action'])
        ->make(true);
}
}
