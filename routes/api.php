<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PasanganController;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::get('/user', function () {
    return \App\Models\User::all();
});
Route::post('/user', [UserController::class, 'store']);
Route::post('/users/add-child', [UserController::class, 'addChild']);
Route::get('/user/silsilah/{id_silsilah}', [UserController::class, 'getBySilsilah']);
Route::post('/user/login', [UserController::class, 'login']);
