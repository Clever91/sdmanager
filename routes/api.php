<?php

use App\Http\Controllers\api\v2\SmsController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\api\v2\FeedbackController;
use App\Http\Controllers\api\v2\DomainController;
use App\Http\Controllers\api\v2\UserController;

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

Route::prefix('v2')->group(function () {
    // guest
    Route::prefix('user')->group(function () {
        Route::controller(UserController::class)->group(function () {
            Route::post("signIn", 'signIn'); // kecyinchalik o'chirib tashlash kerak
            Route::post("checkCode", 'checkCode');
        });
    });
    Route::prefix('sms')->group(function () {
        Route::controller(SmsController::class)->group(function () {
            Route::post("send", 'send');
        });
    });
    // middleware
    Route::middleware('auth:sanctum')->group(function () {
        Route::controller(UserController::class)->prefix('user')->group(function () {
            Route::delete("signOut", 'signOut');
        });
        Route::controller(DomainController::class)->prefix('domain')->group(function () {
            Route::post("add", 'add');
            Route::get("list", 'list');
            Route::delete("delete", 'delete');
            Route::post("jwt/token", 'jwtToken');
        });
        Route::controller(FeedbackController::class)->prefix('feedback')->group(function () {
            Route::post("create", "create");
            Route::get("list", "list");
        });
    });
});
