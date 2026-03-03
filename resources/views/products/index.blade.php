@extends('layouts.app')

@section('title', 'Products')

@section('content')

    <div class="white_card card_height_100 mb_30">
        <div class="white_card_header">
            <div class="box_header m-0 d-flex justify-content-between align-items-center">
                <h3 class="m-0">Products</h3>
                <a href="{{ route('products.create') }}" class="btn btn-primary">
                    Add Product
                </a>
            </div>
        </div>

        <div class="white_card_body">
            <div class="QA_section">
                <div class="QA_table mb_30">
                    <table id="productsTable" class="table table-bordered table-striped w-100">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>Brand</th>
                                <th>Manufacturer</th>
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

            $('#productsTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('products.data') }}",
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
                        data: 'brand',
                        name: 'brand'
                    },
                    {
                        data: 'manufacturer',
                        name: 'manufacturer'
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
