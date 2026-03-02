@extends('layouts.app')
@section('title', 'Brands')

@section('content')

    <div class="white_card mb_30">
        <div class="white_card_header">
            <h3>Brands</h3>
            <a href="{{ route('brands.create') }}" class="btn btn-primary">Add Brand</a>
        </div>

        <div class="white_card_body">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Brand</th>
                        <th>Manufacturer</th>
                        <th>Total Products</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($brands as $brand)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $brand->name }}</td>
                            <td>{{ $brand->manufacturer->name }}</td>
                            <td>{{ $brand->products_count }}</td>
                            <td>
                                <a href="{{ route('brands.edit', $brand->id) }}" class="btn btn-info btn-sm">Edit</a>

                                <form action="{{ route('brands.destroy', $brand->id) }}" method="POST"
                                    style="display:inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-danger btn-sm" onclick="return confirm('Delete this brand?')">
                                        Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

@endsection
