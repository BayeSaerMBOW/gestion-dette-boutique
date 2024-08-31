<?php

use App\Http\Controllers\ClientController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

// Route pour obtenir les informations de l'utilisateur authentifié
Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

// Groupe de routes protégées par authentification Passport
Route::middleware('auth:api')->prefix('v1')->group(function () {
    Route::get('/articles/{id}', [ArticleController::class, 'get']);
    Route::post('/articles', [ArticleController::class, 'store']);
    Route::get('/users', [ClientController::class, 'users']);
    Route::apiResource('/clients', ClientController::class)->only(['index', 'store','show']);
    Route::delete('/articles/{id}', [ArticleController::class, 'delete'])->name('api.articles.delete');
    Route::post('/articles/update-stock', [ArticleController::class, 'updateStock']);
    // Autres routes...
});

// Route de connexion accessible sans authentification
Route::post('/loginuser', [AuthController::class, 'login']);

// Exemple de route protégée (authentification requise)
Route::middleware('auth:api')->get('/usernew', function (Request $request) {
    return response()->json('success');
});
