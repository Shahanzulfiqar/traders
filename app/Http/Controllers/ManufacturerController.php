<?php

namespace App\Http\Controllers;

use App\Models\Manufacturer;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use App\Repository\Users\UserAccessRepository as UAR;
use App\Repository\Users\UserRepository as UserRepo;

class ManufacturerController extends Controller implements HasMiddleware
{
    public function __construct() {

    }

    #[\Override]
    public static function middleware(): array {

        return [
            'ManufacturersAuth',
        ];
    }

    public function index()
    {
        $app_user_id = UserRepo::get_user_id();
        $permissions = UAR::permissions("ManufacturerController", "index", $app_user_id);
        
        $data = [];
        foreach ($permissions as $key => $value) {
            $data[$key] = $value;
        }

        $data['manufacturers'] = Manufacturer::withCount('brands')->latest()->get();
        return view('manufacturers.index', $data);
    }

    public function create()
    {
        return view('manufacturers.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        Manufacturer::create([
            'name' => $request->name,
        ]);

        return redirect()->route('manufacturers.index')
            ->with('success', 'Manufacturer created successfully.');
    }

    public function edit(Manufacturer $manufacturer)
    {
        return view('manufacturers.edit', compact('manufacturer'));
    }

    public function update(Request $request, Manufacturer $manufacturer)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $manufacturer->update([
            'name' => $request->name,
        ]);

        return redirect()->route('manufacturers.index')
            ->with('success', 'Manufacturer updated successfully.');
    }

    public function destroy(Manufacturer $manufacturer)
    {
        $manufacturer->delete();

        return redirect()->route('manufacturers.index')
            ->with('success', 'Manufacturer deleted successfully.');
    }

    public function getData()
    {
        $manufacturers = Manufacturer::withCount('brands')->select(['id', 'name']); // Eager load brand count

        return DataTables::of($manufacturers)
            ->addColumn('total_brands', function($row) {
                return $row->brands_count;
            })
            ->addColumn('action', function($row) {
                $edit = '<a href="'.route('manufacturers.edit', $row->id).'" class="btn btn-sm btn-info">Edit</a>';
                $delete = '<form action="'.route('manufacturers.destroy', $row->id).'" method="POST" style="display:inline;">
                            '.csrf_field().'
                            '.method_field('DELETE').'
                            <button class="btn btn-sm btn-danger" onclick="return confirm(\'Delete this manufacturer?\')">Delete</button>
                        </form>';
                return $edit . ' ' . $delete;
            })
            ->rawColumns(['action'])
            ->make(true);
    }
    public function show($id)
    {
        $manufacturer = Manufacturer::findOrFail($id);
        return view('manufacturers.show', compact('manufacturer'));
    }
    public function data()
    {
        $manufacturers = Manufacturer::withCount('brands');

        return DataTables::of($manufacturers)
            ->addIndexColumn()
            ->addColumn('total_brands', function ($row) {
                return $row->brands_count;
            })
            ->addColumn('action', function ($row) {
                $editUrl = route('manufacturers.edit', $row->id);
                $deleteUrl = route('manufacturers.destroy', $row->id);

                return '
                    <a href="'.$editUrl.'" class="btn btn-sm btn-info">Edit</a>
                    <form action="'.$deleteUrl.'" method="POST" style="display:inline;">
                        '.csrf_field().'
                        '.method_field("DELETE").'
                        <button class="btn btn-sm btn-danger"
                            onclick="return confirm(\'Delete this manufacturer?\')">
                            Delete
                        </button>
                    </form>
                ';
            })
            ->rawColumns(['action'])
            ->make(true);
    }
}
