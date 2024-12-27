<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

use Illuminate\Support\Facades\Route;
use Wave\Facades\Wave;
use App\Models\Subdomain;


// Wave routes
Wave::routes();


use App\Http\Controllers\BuilderController;



// for builder open
Route::get('/builder/{project_id}/{project_name}', [BuilderController::class, 'show'])->name('builder');

// for laoding and saving
Route::get('/builder/{project_id}', [BuilderController::class, 'loadProjectData']);
Route::post('/builder/{project_id}', [BuilderController::class, 'saveProjectData']);


// for single project details
Route::get('/projects/{project_id}', [BuilderController::class, 'showProjectDetails'])->name('project.details')->middleware('auth');

// for deleting project 
Route::delete('/websites/{project_id}', [BuilderController::class, 'deleteProject'])->name('projects.delete');


// for creating new project
Route::post('/projects', [BuilderController::class, 'store'])->name('projects.store'); // Save to DB


// for showing all porjects page
Route::get('/projects', [BuilderController::class, 'allShow'])->name('projects.allShow')->middleware('auth');

// for renaming project
Route::post('/projects/{project_id}/rename', [BuilderController::class, 'rename'])->name('projects.rename');

// for duplicating project
Route::post('/projects/{project_id}/duplicate', [BuilderController::class, 'duplicate'])->name('projects.duplicate');


// for domain chnage
// not implemented yet



Route::post('/deploy/{project_id}', [BuilderController::class, 'deploy']);


Route::post('/pages', [BuilderController::class, 'pageSave'])->name('pages.store');

Route::delete('/pages/{id}', [BuilderController::class, 'pageDelete']);

Route::post('/pages/rename/{id}', [BuilderController::class, 'pageRename']);


Route::post('/pages/data', [BuilderController::class, 'pageHtmlCss']);


// Route for editing header
Route::get('/header/{project_id}', [BuilderController::class, 'headerEdit'])->name('header');
// Route for editing footer
Route::get('/footer/{project_id}', [BuilderController::class, 'footerEdit'])->name('footer');

// Route to save header data
Route::post('/header/{project_id}/save', [BuilderController::class, 'saveHeader']);

// Route to save footer data
Route::post('/footer/{project_id}/save', [BuilderController::class, 'saveFooter']);


// Route to load header data
Route::get('/header/{project_id}/load', [BuilderController::class, 'loadHeader']);

// Route to load footer data
Route::get('/footer/{project_id}/load', [BuilderController::class, 'loadFooter']);

Route::post('/header/data', [BuilderController::class, 'headerData']);

Route::post('/footer/data', [BuilderController::class, 'footerData']);


Route::get('/check-subdomain', function () {
    $subdomain = request('subdomain');
    $isAvailable = !Subdomain::where('subdomain', $subdomain)->exists();

    return response()->json(['available' => $isAvailable]);
});