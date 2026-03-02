@extends('layouts.app')
@section('title', 'Edit Manufacturer')

@section('content')

    <div class="white_card mb_30">
        <div class="white_card_header">
            <h3>Edit Manufacturer</h3>
        </div>

        <div class="white_card_body">
            <form method="POST" action="{{ route('manufacturers.update', $manufacturer->id) }}">
                @csrf
                @method('PUT')

                <div class="form-group">
                    <label>Name</label>
                    <input type="text" name="name" value="{{ $manufacturer->name }}" class="form-control" required>
                </div>

                <button class="btn btn-primary mt-3">Update</button>
            </form>
        </div>
    </div>

@endsection
