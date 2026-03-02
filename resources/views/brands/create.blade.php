@extends('layouts.app')
@section('title', 'Add Brand')

@section('content')

    <div class="white_card mb_30">
        <div class="white_card_header">
            <h3>Add Brand</h3>
        </div>

        <div class="white_card_body">
            <form method="POST" action="{{ route('brands.store') }}">
                @csrf

                <div class="form-group">
                    <label>Manufacturer</label>
                    <select name="manufacturer_id" class="form-control" required>
                        @foreach ($manufacturers as $manufacturer)
                            <option value="{{ $manufacturer->id }}">
                                {{ $manufacturer->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group mt-3">
                    <label>Brand Name</label>
                    <input type="text" name="name" class="form-control" required>
                </div>

                <button class="btn btn-primary mt-3">Save</button>
            </form>
        </div>
    </div>

@endsection
