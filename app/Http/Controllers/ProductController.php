<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Brand;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;


class ProductController extends Controller
{
    public function index()
    {
        $products = Product::with(['brand.manufacturer'])
            ->latest()
            ->get();

        return view('products.index', compact('products'));
    }

    public function create()
    {
        $brands = Brand::with('manufacturer')->get();
        return view('products.create', compact('brands'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'brand_id' => 'required|exists:brands,id',
            'name' => 'required|string|max:255',
        ]);

        Product::create([
            'brand_id' => $request->brand_id,
            'name' => $request->name,
        ]);

        return redirect()->route('products.index')
            ->with('success', 'Product created successfully.');
    }

    public function edit(Product $product)
    {
        $brands = Brand::with('manufacturer')->get();
        return view('products.edit', compact('product', 'brands'));
    }

    public function update(Request $request, Product $product)
    {
        $request->validate([
            'brand_id' => 'required|exists:brands,id',
            'name' => 'required|string|max:255',
        ]);

        $product->update([
            'brand_id' => $request->brand_id,
            'name' => $request->name,
        ]);

        return redirect()->route('products.index')
            ->with('success', 'Product updated successfully.');
    }

    public function destroy(Product $product)
    {
        $product->delete();

        return redirect()->route('products.index')
            ->with('success', 'Product deleted successfully.');
    }
    public function data()
{
    $products = Product::with('brand.manufacturer');

    return DataTables::of($products)
        ->addIndexColumn()
        ->addColumn('brand', function ($row) {
            return $row->brand->name ?? '-';
        })
        ->addColumn('manufacturer', function ($row) {
            return $row->brand->manufacturer->name ?? '-';
        })
        ->addColumn('action', function ($row) {
            $editUrl = route('products.edit', $row->id);
            $deleteUrl = route('products.destroy', $row->id);

            return '
                <a href="'.$editUrl.'" class="btn btn-sm btn-info">Edit</a>
                <form action="'.$deleteUrl.'" method="POST" style="display:inline;">
                    '.csrf_field().'
                    '.method_field("DELETE").'
                    <button class="btn btn-sm btn-danger"
                        onclick="return confirm(\'Delete this product?\')">
                        Delete
                    </button>
                </form>
            ';
        })
        ->rawColumns(['action'])
        ->make(true);
}
}
