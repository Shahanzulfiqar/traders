@extends('layouts.app')
@section('title', 'Add Manufacturer')

@section('content')

    <div class="white_card mb_30">
        <div class="white_card_header">
            <h3>Add Manufacturer</h3>
        </div>

        <div class="white_card_body">
            <form method="POST" action="{{ route('manufacturers.store') }}">
                @csrf

                <div class="form-group">
                    <label>Name</label>
                    <input type="text" name="name" class="form-control" required>
                </div>

                <button class="btn btn-primary mt-3">Save</button>
            </form>
        </div>
    </div>

@endsection
