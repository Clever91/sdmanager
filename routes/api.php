<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\api\v1\SignInController;
use App\Http\Controllers\api\v1\UserController;
use App\Http\Controllers\api\v1\DashboardController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::prefix('v1')->group(function () {
    // guest
    Route::prefix('user')->group(function () {
        Route::controller(UserController::class)->group(function() {
            Route::post("signUp", 'create');
        });
        Route::controller(SignInController::class)->group(function () {
            Route::post("signIn", 'index');
        });
    });
    // middleware
    Route::middleware('auth:sanctum')->group(function () {
        Route::controller(UserController::class)->prefix('user')->group(function () {
            Route::post("password", 'password');
            Route::delete("signOut", 'signOut');
        });
        Route::controller(DashboardController::class)->prefix('dashboard')->group(function () {
            Route::get("", 'index');
        });
    });
});
