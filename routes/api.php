<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MahasiswaController;
use App\Http\Controllers\FaceController;



Route::post('/face/identified', [FaceController::class, 'identified']);


Route::apiResource('mahasiswa', MahasiswaController::class);
Route::post('/face/identify', [FaceController::class, 'identify']);
Route::get('/face/student/{npm}', [FaceController::class, 'getStudentDescriptor']);
Route::post('/mahasiswa/descriptor/{id}', [MahasiswaController::class, 'saveDescriptor']);
Route::get('/face/references', [FaceController::class, 'references']);
