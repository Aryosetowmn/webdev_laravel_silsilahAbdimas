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
// Route::get('/export-users', [UserController::class, 'exportExcel']);

//EDIT PROFILE KELUARGA - HALAMAN 11
Route::put('/family/update-children', [UserController::class, 'updateChildrenByFamilyTree']);

//EDIT PROFILE - HALAMAN 10
Route::put('/user/update/{id}', [UserController::class, 'updateProfile']);

//POST ADD CHILD AND SPOUSE - HALAMAN 9
Route::post('/spouse', [SpouseController::class, 'store']);
Route::post('/users/add-child', [UserController::class, 'addChild']);

//GET PROFILE BY NIT - HALAMAN 7, HALAMAN 8
Route::get('/family/search', [UserController::class, 'searchByFamilyTreeId']);

//GET DETAIL PROFILE BY ID - HALAMAN 5, HALAMAN 6
Route::get('/user/{id}', [UserController::class, 'getById']);

//GET FAMILY CREDENTIAL - HALAMAN 4, HALAMAN 5, HALAMAN 12
Route::get('/users/tree/{family_tree_id}', [UserController::class, 'getByTree']);

//GET FAMILY CREDENTIAL - HALAMAN 2, HALAMAN 3, 
Route::get('/family/count/{id}', [UserController::class, 'countFamilyMembers']);

//HALAMAN UNTUK LOGIN - HALAMAN 1
Route::post('/users/login', [UserController::class, 'login']);


//TESTING
Route::get('/export-users', [UserController::class, 'exportExcel']);
Route::put('/family/update-profile', [UserController::class, 'updateProfileWithCredential']);


