<?php

use Inertia\Inertia;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Application;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\FacultyController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\SectionsController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\RoomCoordinatorController;

//Reset Password Route
// Auth::routes(['verify' => true]);

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
    Route::get('/subjects/view/{id}', [SubjectController::class, 'view'])->name('subjects.view');

});

//Department Headn Route 
Route::group(['namespace' => 'Department', 'prefix' => 'department'], function () {

    //Section Routes
    Route::get('sections', [SectionsController::class, 'index'])->name('department.sections');
    Route::post('sections', [SectionsController::class, 'store'])->name('department.store'); 
    Route::delete('sections/{id}', [SectionsController::class, 'destroy'])->name('department.destroy');
    Route::post('sections/delete-all', [SectionsController::class, 'deleteAll'])->name('department.deleteAll');
    Route::get('sections/{id}/edit', [SectionsController::class, 'editSection'])->name('department.editSection'); 
    Route::put('sections/{id}', [SectionsController::class, 'updateSection'])->name('department.updateSection'); 

    //Faculty Routes
    Route::get('faculty', [FacultyController::class, 'index'])->name('department.faculty');
    Route::post('/faculty', [FacultyController::class, 'store'])->name('faculty.store');
    Route::delete('/faculty/{id}', [FacultyController::class, 'destroy'])->name('faculty.destroy');
    Route::get('/faculty/{id}/edit', [FacultyController::class, 'edit'])->name('faculty.edit');
    Route::put('/faculty/{id}', [FacultyController::class, 'update'])->name('faculty.update');

    //Subject Routes
    Route::get('subjects', [SubjectController::class, 'departmentIndex'])->name('department.subjects');
    Route::post('subjects/{subject}/assign-faculty', [SubjectController::class, 'assignFaculty'])->name('department.assignFaculty');
    Route::delete('subjects/{subject}/remove-faculty/{faculty}', [SubjectController::class, 'removeFaculty'])->name('department.removeFaculty');
    Route::get('/assign-subjects', [SubjectController::class, 'assignSubjects'])->name('department.assignSubjects');
    Route::post('/assign-subjects', [SubjectController::class, 'assignSectionToSubject'])->name('department.assign.subjects');
    Route::delete('/unassign-subject/{subject}', [SubjectController::class, 'unassignSubject'])->name('department.unassign.subject');

    //Schedule Routes
    Route::get('schedule', [ScheduleController::class, 'index'])->name('department.schedule');
    Route::get('automatic_schedule', [ScheduleController::class, 'showAutomaticSchedule'])->name('department.show_automatic_schedule'); 
    Route::post('schedule', [ScheduleController::class, 'store'])->name('department.schedule.store');
    Route::get('section_schedule', [ScheduleController::class, 'ScheduleIndex'])->name('department.section_schedule');
    Route::delete('schedule/{schedule}', [ScheduleController::class, 'destroy'])->name('department.schedule.destroy');
    Route::get('faculty_schedule', [ScheduleController::class, 'FacultySchedule'])->name('department.faculty_schedule');
    Route::get('schedule/{schedule}/edit', [ScheduleController::class, 'EditSchedule'])->name('department.schedule.edit');
    Route::put('schedule/{schedule}', [ScheduleController::class, 'UpdateSchedule'])->name('department.schedule.update');
    Route::post('automatic_schedule', [ScheduleController::class, 'automaticSchedule'])->name('department.automatic_schedule');
    Route::post('pair_schedule', [ScheduleController::class, 'storePairSchedule'])->name('department.pair_schedule.store');

    //Room Routes
    Route::get('rooms', [RoomController::class, 'index'])->name('department.rooms');
    Route::get('/rooms/{roomId}/schedule', [RoomController::class, 'roomSchedule'])->name('department.roomSchedule');
});

//Room Coordinator Routes
Route::group(['prefix' => 'roomCoordinator', 'as' => 'roomCoordinator.'], function () {
    //Room Routes
    Route::delete('/rooms/{id}', [RoomCoordinatorController::class, 'deleteRoom'])->name('deleteRoom');
    Route::post('/subjects/add', [RoomCoordinatorController::class, 'addRoom'])->name('addRoom');
    Route::get('/rooms/{id}/edit', [RoomCoordinatorController::class, 'editRoom'])->name('editRoom');
    Route::put('/rooms/{id}', [RoomCoordinatorController::class, 'updateRoom'])->name('updateRoom');
    Route::get('/rooms/{roomId}/schedule', [RoomCoordinatorController::class, 'roomSchedule'])->name('roomSchedule');
    Route::post('/assignRoom', [RoomCoordinatorController::class, 'assignRoom'])->name('assignRoom');
    Route::get('/assign-rooms-to-faculty', [RoomCoordinatorController::class, 'showAssignRoomsToFaculty'])->name('assignroomstofaculty');
    Route::delete('/unassignRoom', [RoomCoordinatorController::class, 'unassignRoom'])->name('unassignRoom'); 


    //Faculty Routes
    Route::get('/faculty', [RoomCoordinatorController::class, 'facultySchedIndex'])->name('facultySchedIndex');
    Route::get('/faculty/{id}/schedule', [RoomCoordinatorController::class, 'viewFacultySchedule'])->name('viewFacultySchedule');

    //Schedule Routes
    Route::get('/section', [RoomCoordinatorController::class, 'sectionScheduleIndex'])->name('sectionScheduleIndex');
    Route::get('/section/{id}/schedule', [RoomCoordinatorController::class, 'viewSectionSchedule'])->name('viewSectionSchedule');
    Route::delete('/section/{schedule}', [RoomCoordinatorController::class, 'destroySchedule'])->name('destroySchedule');
    Route::put('/schedule/{schedule}', [RoomCoordinatorController::class, 'updateSchedule'])->name('updateSchedule');
    Route::get('/schedule/{schedule}/edit', [RoomCoordinatorController::class, 'editSchedule'])->name('editSchedule');
    Route::get('/section/add-schedule', [RoomCoordinatorController::class, 'addSchedule'])->name('addSchedule');
    Route::post('/section/store-schedule', [RoomCoordinatorController::class, 'storeSchedule'])->name('storeSchedule');
    Route::post('/section/automatic-schedule', [RoomCoordinatorController::class, 'automaticSchedule'])->name('automaticSchedule');
    Route::get('/section/automatic_schedule', [RoomCoordinatorController::class, 'showAutomaticSchedule'])->name('automatic_schedule');

     // Schedule Pairing Routes
     Route::get('/manage-schedule-pairing', [RoomCoordinatorController::class, 'manageSchedulePairing'])->name('manageSchedulePairing');
     Route::post('/store-schedule-pairing', [RoomCoordinatorController::class, 'storeSchedulePairing'])->name('storeSchedulePairing');
     Route::get('/edit-schedule-pairing/{id}', [RoomCoordinatorController::class, 'editSchedulePairing'])->name('editSchedulePairing');
     Route::put('/update-schedule-pairing/{id}', [RoomCoordinatorController::class, 'updateSchedulePairing'])->name('updateSchedulePairing');
     Route::delete('/destroy-schedule-pairing/{id}', [RoomCoordinatorController::class, 'destroySchedulePairing'])->name('destroySchedulePairing');
});