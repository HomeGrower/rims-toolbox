<?php

use App\Http\Controllers\Auth\CodeAuthController;
use App\Http\Controllers\ClientDashboardController;
use App\Http\Controllers\SetupController;
use App\Http\Controllers\GreetingTextController;
use App\Http\Controllers\Api\ProjectDataController;
use App\Http\Controllers\Api\DatastoreController;
use App\Http\Controllers\Api\RoomDataController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/access');
});

// Fallback login route for auth middleware
Route::get('/login', function () {
    return redirect('/access');
})->name('login');



// Code Authentication Routes (Main entry point)
Route::middleware(['App\Http\Middleware\HandleInertiaRequests'])->group(function () {
    Route::get('/access', [CodeAuthController::class, 'showCodeForm'])->name('code.login');
    Route::post('/access', [CodeAuthController::class, 'loginWithCode'])->name('code.submit');
    Route::post('/access/logout', [CodeAuthController::class, 'logout'])->name('code.logout');
});

// Client Routes (require project access)
Route::middleware(['App\Http\Middleware\EnsureProjectAccess', 'App\Http\Middleware\HandleInertiaRequests'])->group(function () {
    Route::get('/dashboard', [ClientDashboardController::class, 'index'])->name('client.dashboard');
    
    // Setup routes
    Route::get('/setup/{team}/{section}', [SetupController::class, 'show'])->name('client.setup.show');
    Route::post('/setup/{team}/{section}', [SetupController::class, 'save'])->name('client.setup.save');
    
    // Greeting Texts
    Route::get('/greeting-texts', [GreetingTextController::class, 'index'])->name('client.greeting-texts.index');
    Route::post('/greeting-texts', [GreetingTextController::class, 'store'])->name('client.greeting-texts.store');
    Route::put('/greeting-texts/{paragraph}', [GreetingTextController::class, 'update'])->name('client.greeting-texts.update');
    Route::delete('/greeting-texts/{paragraph}', [GreetingTextController::class, 'destroy'])->name('client.greeting-texts.destroy');
    Route::post('/greeting-texts/preview', [GreetingTextController::class, 'preview'])->name('client.greeting-texts.preview');
    
    // Room Data API
    Route::get('/api/projects/{project}/check-room-marketing-data/{roomIndex}', [RoomDataController::class, 'checkRoomMarketingData'])
        ->name('api.room.check-marketing-data');
});

// Admin area is handled by Filament at /admin

// API Routes for Datastore Builder
Route::prefix('api')->middleware(['auth'])->group(function () {
    Route::get('/projects/{project}/datastore-info', [DatastoreController::class, 'getProjectDatastoreInfo']);
    Route::get('/projects/{project}/datastore-configuration', [DatastoreController::class, 'getConfiguration']);
    Route::post('/projects/{project}/datastore-configuration', [DatastoreController::class, 'saveConfiguration']);
});

// Datastore Preview Route
Route::middleware(['auth'])->get('/projects/{project}/datastore/preview', [DatastoreController::class, 'preview'])->name('projects.datastore.preview');

// Debug route for middleware
Route::middleware(['App\Http\Middleware\EnsureProjectAccess'])->get('/debug-middleware', function (\Illuminate\Http\Request $request) {
    $project = $request->attributes->get('project');
    
    return [
        'project_exists' => $project !== null,
        'project_id' => $project?->id,
        'brand_loaded' => $project?->relationLoaded('hotelBrand'),
        'brand_exists' => $project?->hotelBrand !== null,
        'brand_id' => $project?->hotelBrand?->id,
        'brand_name' => $project?->hotelBrand?->name,
        'brand_logo' => $project?->hotelBrand?->logo,
        'brand_logo_url' => $project?->hotelBrand?->logo ? \Storage::url($project->hotelBrand->logo) : null,
    ];
});

// Debug route
Route::get('/debug-logo', function () {
    $projectId = session('current_project_id');
    if (!$projectId) {
        return 'No project in session';
    }
    
    $project = \App\Models\Project::with('hotelBrand')->find($projectId);
    if (!$project) {
        return 'Project not found';
    }
    
    $brandLogo = null;
    $rimsLogo = null;
    
    try {
        $rimsLogo = \App\Models\Setting::get('rims_logo');
    } catch (\Exception $e) {
        // Settings table might not exist yet
    }
    
    if ($project->hotelBrand && $project->hotelBrand->logo) {
        $brandLogo = \Storage::url($project->hotelBrand->logo);
    }
    
    return [
        'project_id' => $project->id,
        'project_name' => $project->name,
        'brand_id' => $project->hotelBrand?->id,
        'brand_name' => $project->hotelBrand?->name,
        'brand_logo_field' => $project->hotelBrand?->logo,
        'brand_logo_url' => $brandLogo,
        'rims_logo' => $rimsLogo,
        'storage_url_test' => $project->hotelBrand?->logo ? \Storage::url($project->hotelBrand->logo) : null,
    ];
});

// API Routes for project data access
Route::prefix('api')->group(function () {
    Route::get('/project/{accessCode}/data', [ProjectDataController::class, 'show'])
        ->name('api.project.data');
    Route::get('/project/{accessCode}/export', [ProjectDataController::class, 'export'])
        ->name('api.project.export');
    
    // Datastore Builder API Routes
    Route::middleware('auth')->group(function () {
        Route::get('/projects/{project}/datastore-info', [\App\Http\Controllers\Api\DatastoreController::class, 'getProjectDatastoreInfo'])
            ->name('api.projects.datastore-info');
        Route::get('/projects/{project}/datastore-configuration', [\App\Http\Controllers\Api\DatastoreController::class, 'getConfiguration'])
            ->name('api.projects.datastore-configuration');
        Route::post('/projects/{project}/datastore-configuration', [\App\Http\Controllers\Api\DatastoreController::class, 'saveConfiguration'])
            ->name('api.projects.datastore-configuration.save');
    });
});
