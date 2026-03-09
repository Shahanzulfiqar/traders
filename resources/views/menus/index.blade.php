@extends('layouts.app')

@section('content')
    <div class="container-fluid p-0">

        <div class="row">
            <div class="col-12">
                <div class="white_card card_height_100 mb_30 pt-4">
                    <div class="white_card_body">
                        <div class="QA_section">
                            <div class="white_box_tittle list_header d-flex justify-content-between align-items-center">
                                <h4>Menu Management</h4>
                                <a href="#" data-bs-toggle="modal" data-bs-target="#addMenuModal" class="btn_1">
                                    Add Menu
                                </a>
                            </div>

                            @if (session('success'))
                                <div class="alert alert-success mt-2">
                                    {{ session('success') }}
                                </div>
                            @endif

                            <div class="QA_table mb_30 mt-3">
                                <table class="table table-bordered lms_table_active">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Menu Name</th>
                                            <th>Parent Menu</th>
                                            <th>Route</th>
                                            <th>Icon</th>
                                            <th width="100">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($menus as $menu)
                                            <tr>
                                                <td>{{ $menu->id }}</td>
                                                <td>{{ $menu->name }}</td>
                                                <td>{{ $menu->parent ? $menu->parent->name : 'Main Menu' }}</td>
                                                <td>{{ $menu->route }}</td>
                                                <td>{{ $menu->icon }}</td>
                                                <td>
                                                    <div class="action_btns d-flex">
                                                        <form action="{{ route('menus.destroy', $menu->id) }}"
                                                            method="POST">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit"
                                                                class="action_btn border-0 bg-transparent"
                                                                onclick="return confirm('Delete this menu?')">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add Menu Modal -->
        <div class="modal fade" id="addMenuModal">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form action="{{ route('menus.store') }}" method="POST">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title">Add Menu</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>

                        <div class="modal-body">
                            <div class="mb-3">
                                <label>Menu Name</label>
                                <input type="text" name="name" class="form-control" required>
                            </div>

                            <div class="mb-3">
                                <label>Route Name</label>
                                <input type="text" name="route" class="form-control" placeholder="dashboard.index">
                            </div>

                            <div class="mb-3">
                                <label>Icon</label>
                                <input type="text" name="icon" class="form-control" placeholder="fa fa-home">
                            </div>

                            <div class="mb-3">
                                <label>Parent Menu</label>
                                <select name="parent_id" class="form-control">
                                    <option value="">Main Menu</option>
                                    @foreach ($parentMenus as $parent)
                                        <option value="{{ $parent->id }}">{{ $parent->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="submit" class="btn btn-primary">Save Menu</button>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>

                    </form>
                </div>
            </div>
        </div>

    </div>
@endsection
