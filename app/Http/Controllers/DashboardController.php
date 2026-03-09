<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct()
    {
        // $this->middleware('auth'); // Protect dashboard
    }

    public function index()
    {
        $user = auth()->user();
        $roles = $user->getRoleNames();
        $permissions = $user->getAllPermissions();
        dd($permissions);
        return view('dashboard');
    }
}
