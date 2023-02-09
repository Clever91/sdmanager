<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\api\v1\SignInController;
use App\Http\Controllers\api\v1\UserController;
use App\Http\Controllers\api\v1\DashboardController;
use App\Http\Controllers\api\v1\DomainController;
use App\Http\Controllers\api\v1\FeedbackController;

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
        Route::controller(UserController::class)->group(function () {
            Route::post("signUp", 'create');
            Route::post("register", 'register');
            Route::post("exist", 'exist');
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
        Route::controller(DomainController::class)->prefix('domain')->group(function () {
            Route::post("add", 'add');
            Route::get("list", 'list');
            Route::delete("delete", 'delete');
            Route::post("refresh/token", 'refreshToken');
        });
        Route::controller(FeedbackController::class)->prefix('feedback')->group(function () {
            Route::post("create", "create");
            Route::get("list", "list");
        });
    });
});
