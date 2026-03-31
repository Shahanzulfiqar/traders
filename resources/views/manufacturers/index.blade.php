@extends('layouts.app')

@section('title', 'Manufacturers')

@section('content')

    <div class="white_card card_height_100 mb_30">
        <div class="white_card_header">
            <div class="box_header m-0 d-flex justify-content-between align-items-center">
                <h3 class="m-0">Manufacturers</h3>
                @if($can_add)
                <a href="{{ route('manufacturers.create') }}" class="btn btn-primary">
                    Add Manufacturer
                </a>
                @endif
            </div>
        </div>

        <div class="white_card_body">
            <div class="QA_section">
                <div class="QA_table mb_30">
                    <table id="manufacturersTable" class="table table-bordered table-striped w-100">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>Total Brands</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

@endsection


@push('js')
    <script>
        $(document).ready(function() {
            if (!$.fn.DataTable.isDataTable('#manufacturersTable')) {
                $('#manufacturersTable').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: "{{ route('manufacturers.data') }}",
                    columns: [{
                            data: 'DT_RowIndex',
                            orderable: false,
                            searchable: false
                        },
                        {
                            data: 'name',
                            name: 'name'
                        },
                        {
                            data: 'total_brands',
                            name: 'total_brands'
                        },
                        {
                            data: 'action',
                            orderable: false,
                            searchable: false
                        }
                    ]
                });
            }
        });
    </script>
@endpush
