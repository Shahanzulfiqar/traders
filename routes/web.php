<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ManufacturerController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RolePermissionController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Redirect homepage to login
Route::get('/', fn() => redirect('/login'));

// Authentication Routes
Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
Route::post('/register', [RegisteredUserController::class, 'store']);
Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

Route::get('/forgot-password', [PasswordResetController::class, 'create'])->name('password.request');
Route::post('/forgot-password', [PasswordResetController::class, 'store'])->name('password.email');

Route::get('/reset-password/{token}', [PasswordResetController::class, 'edit'])->name('password.reset');
Route::post('/reset-password', [PasswordResetController::class, 'update'])->name('password.update');

// Redirect /home to dashboard
Route::redirect('/home', '/dashboard');

/*
|--------------------------------------------------------------------------
| Protected Routes (Auth)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->group(function () {

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

    /*
    |----------------------------------------------------------------------
    | Manufacturers, Brands, Products
    |----------------------------------------------------------------------
    */
    Route::get('manufacturers/data', [ManufacturerController::class, 'data'])->name('manufacturers.data');
    Route::get('brands/data', [BrandController::class, 'data'])->name('brands.data');
    Route::get('products/data', [ProductController::class, 'data'])->name('products.data');

    Route::resource('manufacturers', ManufacturerController::class);
    Route::resource('brands', BrandController::class);
    Route::resource('products', ProductController::class);

    /*
    |----------------------------------------------------------------------
    | Menus & Roles
    |----------------------------------------------------------------------
    */
    Route::resource('menus', MenuController::class);
    Route::resource('roles', RoleController::class);

    /*
    |----------------------------------------------------------------------
    | Role Permissions
    |----------------------------------------------------------------------
    */
    Route::prefix('role-permissions')->group(function () {
        Route::get('/', [RolePermissionController::class, 'index'])->name('rolepermissions.index'); // for sidebar link
        Route::post('/store', [RolePermissionController::class, 'store'])->name('role.permissions.store'); // form submission
    });

    /*
    |----------------------------------------------------------------------
    | Users
    |----------------------------------------------------------------------
    */
    Route::resource('users', UserController::class);
});


