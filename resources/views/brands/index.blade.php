@extends('layouts.app')

@section('title', 'Brands')

@section('content')

    <div class="white_card card_height_100 mb_30">
        <div class="white_card_header">
            <div class="box_header m-0 d-flex justify-content-between align-items-center">
                <h3 class="m-0">Brands</h3>
                <a href="{{ route('brands.create') }}" class="btn btn-primary">
                    Add Brand
                </a>
            </div>
        </div>

        <div class="white_card_body">
            <div class="QA_section">
                <div class="QA_table mb_30">
                    <table id="brandsTable" class="table table-bordered table-striped w-100">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>Manufacturer</th>
                                <th>Total Products</th>
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
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

    <script>
        $(document).ready(function() {

            $('#brandsTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('brands.data') }}",
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
                        data: 'manufacturer',
                        name: 'manufacturer'
                    },
                    {
                        data: 'total_products',
                        name: 'total_products'
                    },
                    {
                        data: 'action',
                        orderable: false,
                        searchable: false
                    }
                ]
            });

        });
    </script>
@endpush
