@extends('layouts.app')
@section('title', 'Products')

@section('content')

    <div class="white_card mb_30">
        <div class="white_card_header">
            <h3>Products</h3>
            <a href="{{ route('products.create') }}" class="btn btn-primary">Add Product</a>
        </div>

        <div class="white_card_body">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Product</th>
                        <th>Brand</th>
                        <th>Manufacturer</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($products as $product)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $product->name }}</td>
                            <td>{{ $product->brand->name }}</td>
                            <td>{{ $product->brand->manufacturer->name }}</td>
                            <td>
                                <a href="{{ route('products.edit', $product->id) }}" class="btn btn-info btn-sm">Edit</a>

                                <form action="{{ route('products.destroy', $product->id) }}" method="POST"
                                    style="display:inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-danger btn-sm" onclick="return confirm('Delete this product?')">
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
