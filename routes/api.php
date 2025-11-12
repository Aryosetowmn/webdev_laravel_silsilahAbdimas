<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\SpouseController;


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
Route::post('/users', [UserController::class, 'store']);
Route::post('/users/no-tree', [UserController::class, 'storeWithoutTree']);
Route::post('/spouse', [SpouseController::class, 'store']);
// Route::get('/export-users', [UserController::class, 'exportExcel']);

Route::post('/users/add-child', [UserController::class, 'addChild']);
Route::get('/users/tree/{family_tree_id}', [UserController::class, 'getByTree']);
Route::post('/users/login', [UserController::class, 'login']);

Route::get('/export-users', [UserController::class, 'exportExcel']);

