<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\OpportunityController;
use App\Http\Controllers\Api\LeadController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\ActivityController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\AttachmentController;

Route::post('/login',[AuthController::class,'login'])->name('auth.login');
Route::post('/register',[AuthController::class,'register'])->name('auth.register');
Route::post('/refresh',[AuthController::class,'refresh'])->name('auth.refresh');

Route::middleware('jwt')->group(function () {
    Route::get('/me',[AuthController::class,'me']);
    Route::post('/logout',[AuthController::class,'logout']);

    Route::apiResource('leads',LeadController::class)->only(['index','store','show','update','destroy']);
    Route::get('/leads/{lead}/activities', [LeadController::class, 'activities']);
    Route::post('/activities',[ActivityController::class,'store']);
    Route::apiResource('tasks',TaskController::class)->only(['index','store','show','update','destroy']);
    Route::apiResource('opportunities',OpportunityController::class)->only(['index','store','show','update','destroy']);
    Route::get('/notifications',[NotificationController::class,'index']);
    Route::get('/notifications/{notification}',[NotificationController::class,'show']);
    Route::put('/notifications/{notification}/read',[NotificationController::class,'markAsRead']);
    Route::post('/attachments',[AttachmentController::class,'store']);
    Route::delete('/attachments/{attachment}',[AttachmentController::class,'destroy']);
});
