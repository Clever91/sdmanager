<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\api\v1\SignUpController;
use App\Http\Controllers\api\v1\UserController;

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
    Route::controller(SignUpController::class)->prefix('signUp')->group(function() {
        Route::post("phone", 'phone');
        Route::get("checkCode", 'checkCode');
    });
    Route::middleware('auth:sanctum')->group(function () {
        Route::post("/user/password", [UserController::class, 'password']);
    });
});
