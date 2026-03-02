@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')

    <div class="row">
        <div class="col-lg-6 offset-lg-3">
            <div class="white_card shadow-sm">
                <div class="white_card_body">
                    <h4 class="mb-4">Add New Product</h4>

                    <form action="{{ route('products.store') }}" method="POST">
                        @csrf

                        {{-- Product Name --}}
                        <div class="mb-3">
                            <label for="name" class="form-label">Product Name</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name"
                                name="name" value="{{ old('name') }}" placeholder="Enter product name" required>
                            @error('name')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        {{-- Brand Selection --}}
                        <div class="mb-3">
                            <label for="brand_id" class="form-label">Brand</label>
                            <select class="form-select @error('brand_id') is-invalid @enderror" id="brand_id"
                                name="brand_id" required>
                                <option value="">-- Select Brand --</option>
                                @foreach ($brands as $brand)
                                    <option value="{{ $brand->id }}"
                                        {{ old('brand_id') == $brand->id ? 'selected' : '' }}>
                                        {{ $brand->name }} ({{ $brand->manufacturer->name }})
                                    </option>
                                @endforeach
                            </select>
                            @error('brand_id')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        {{-- Submit Button --}}
                        <div class="mb-3 text-end">
                            <a href="{{ route('products.index') }}" class="btn btn-secondary me-2">Cancel</a>
                            <button type="submit" class="btn btn-primary">Add Product</button>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>


@endsection
