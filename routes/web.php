<?php

use Inertia\Inertia;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Application;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\SectionsController;
use App\Http\Controllers\DashboardController;


Route::get('/', function () {
    return view('welcome');
});

// Login 
Route::group(['prefix' => 'login', 'as' => 'login.'], function () {
    Route::post('/', [LoginController::class, 'login']);
});

// Logout
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Redirect Login Depending on Account logged in
Route::group(['prefix' => 'dashboard', 'as' => 'dashboard.'], function () {
    Route::get('/', [DashboardController::class, 'index'])->name('index');
    Route::get('/admin', [DashboardController::class, 'adminIndex'])->name('adminIndex');
    Route::get('/room-coordinator', [DashboardController::class, 'roomCoordIndex'])->name('roomCoordIndex');
});

// Admin Route (Subjects handling)
Route::group(['prefix' => 'admin', 'as' => 'admin.'], function () {
    Route::get('/manage-subjects', [SubjectController::class, 'index'])->name('subjects.index');
    Route::post('/upload-subjects', [SubjectController::class, 'upload'])->name('subjects.upload');
    Route::post('/store-subject', [SubjectController::class, 'store'])->name('subjects.store');
    Route::delete('/subjects/delete-all', [SubjectController::class, 'deleteAll'] )->name('subjects.deleteAll');
    Route::delete('/subjects/delete/{id}', [SubjectController::class, 'delete'])->name('subjects.delete');
    Route::get('/subjects/edit/{id}', [SubjectController::class, 'edit'])->name('subjects.edit');
    Route::put('/subjects/update/{id}', [SubjectController::class, 'update'])->name('subjects.update');
});

//Department Headn Route 
Route::group(['namespace' => 'Department', 'prefix' => 'department.'], function () {
    Route::get('sections', [SectionsController::class, 'index'])->name('department.sections');
    Route::post('sections', [SectionsController::class, 'store'])->name('department.store'); 
    Route::delete('sections/{id}', [SectionsController::class, 'destroy'])->name('department.destroy');
    Route::post('sections/delete-all', [SectionsController::class, 'deleteAll'])->name('department.deleteAll');
});


