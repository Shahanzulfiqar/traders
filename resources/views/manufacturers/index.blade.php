@extends('layouts.app')

@section('title', 'Manufacturers')

@section('content')

    <div class="white_card card_height_100 mb_30">
        <div class="white_card_header">
            <div class="box_header m-0">
                <h3 class="m-0">Manufacturers</h3>
                <a href="{{ route('manufacturers.create') }}" class="btn btn-primary">
                    Add Manufacturer
                </a>
            </div>
        </div>

        <div class="white_card_body">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Total Brands</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($manufacturers as $manufacturer)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $manufacturer->name }}</td>
                            <td>{{ $manufacturer->brands_count }}</td>
                            <td>
                                <a href="{{ route('manufacturers.edit', $manufacturer->id) }}"
                                    class="btn btn-sm btn-info">Edit</a>

                                <form action="{{ route('manufacturers.destroy', $manufacturer->id) }}" method="POST"
                                    style="display:inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-danger"
                                        onclick="return confirm('Delete this manufacturer?')">
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
