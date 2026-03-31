@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')

    <div class="container-fluid">

        <!-- Page Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <h2 class="fw-bold">Dashboard</h2>
                    <span class="text-muted">Welcome back, 👋</span>
                </div>
            </div>
        </div>

        <!-- Cards Section -->
        <div class="row g-4">

            <!-- Traders Card -->
            <div class="col-lg-4 col-md-6">
                <div class="card shadow-sm border-0 rounded-3">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-1">Total Users</h6>
                                <h3 class="fw-bold mb-0">{{ \App\Models\User::count() }}</h3>
                            </div>
                            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center"
                                style="width: 50px; height: 50px;">
                                <i class="fas fa-chart-line"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- User Card -->
            <div class="col-lg-4 col-md-6">
                <div class="card shadow-sm border-0 rounded-3">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-1">Logged In User</h6>
                                <h4 class="fw-bold mb-0">{{ Auth::user()->name }}</h4>
                            </div>
                            <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center"
                                style="width: 50px; height: 50px;">
                                <i class="fas fa-user"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Example Stats Card -->
            <div class="col-lg-4 col-md-6">
                <div class="card shadow-sm border-0 rounded-3">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-1">Login User Email</h6>
                                <h4 class="fw-bold mb-0">{{ Auth::user()->email }}</h4>
                            </div>
                            <div class="bg-warning text-white rounded-circle d-flex align-items-center justify-content-center"
                                style="width: 50px; height: 50px;">
                                <i class="fas fa-dollar-sign"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

    </div>

@endsection
