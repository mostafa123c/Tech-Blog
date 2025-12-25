<?php

use App\Http\Controllers\Authentication\AuthenticationController;
use App\Http\Controllers\Comment\CommentController;
use App\Http\Controllers\Post\PostController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('register', [AuthenticationController::class, 'register']);
Route::post('login', [AuthenticationController::class, 'login']);


Route::group(['middleware' => 'auth:api'], function () {
    Route::post('logout', [AuthenticationController::class, 'logout']);
    Route::get('me', [AuthenticationController::class, 'me']);

    Route::apiResource('posts', PostController::class);
    Route::get('/posts/{post}/comments', [CommentController::class, 'index']);
    Route::post('/posts/{post}/comments', [CommentController::class, 'store']);
    Route::put('/comments/{comment}', [CommentController::class, 'update']);
    Route::delete('/comments/{comment}', [CommentController::class, 'destroy']);
});
