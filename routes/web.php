<?php

use App\Http\Controllers\CalendarController;
use App\Http\Controllers\DependencyController;
use App\Http\Controllers\HolidayController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ResourceController;
use App\Http\Controllers\ResourceLeaveController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\VisualizationController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('projects.index'));

// Health check endpoint (PRD §12).
Route::get('/health', fn () => response()->json([
    'status' => 'ok',
    'time' => now()->toIso8601String(),
]))->name('health');

// UI language switch (PRD §10): pilihan disimpan di session, default English.
Route::post('/locale', [LocaleController::class, 'store'])->name('locale.store');

Route::get('/projects/import', [ProjectController::class, 'createImport'])->name('projects.import.create');
Route::post('/projects/import', [ProjectController::class, 'import'])->name('projects.import');

Route::resource('projects', ProjectController::class);

Route::get('/projects/{project}/export', [ProjectController::class, 'export'])->name('projects.export');

Route::prefix('projects/{project}')->name('projects.')->group(function () {
    Route::get('/calendar', [CalendarController::class, 'edit'])->name('calendar.edit');
    Route::put('/calendar', [CalendarController::class, 'update'])->name('calendar.update');

    Route::post('/holidays', [HolidayController::class, 'store'])->name('holidays.store');
    Route::post('/holidays/import', [HolidayController::class, 'importTemplate'])->name('holidays.import');
    Route::put('/holidays/{holiday}', [HolidayController::class, 'update'])->name('holidays.update');
    Route::delete('/holidays/{holiday}', [HolidayController::class, 'destroy'])->name('holidays.destroy');

    Route::post('/dependencies', [DependencyController::class, 'store'])->name('dependencies.store');
    Route::delete('/dependencies/{dependency}', [DependencyController::class, 'destroy'])->name('dependencies.destroy');

    Route::post('/team/{resource}/leaves', [ResourceLeaveController::class, 'store'])->name('leaves.store');
    Route::delete('/leaves/{leave}', [ResourceLeaveController::class, 'destroy'])->name('leaves.destroy');

    // Preferensi view salary (RES-5): disimpan di session.
    Route::post('/team/salary-visibility', [ResourceController::class, 'toggleSalaryVisibility'])->name('team.salary-visibility');

    Route::get('/gantt', [VisualizationController::class, 'gantt'])->name('gantt');
    Route::get('/pert', [VisualizationController::class, 'pert'])->name('pert');

    Route::resource('/tasks', TaskController::class)->except(['show']);

    // Pengaturan ulang urutan & level task (TSK-2 / TSK-7).
    Route::post('/tasks/{task}/move', [TaskController::class, 'move'])->name('tasks.move');
    Route::post('/tasks/{task}/indent', [TaskController::class, 'indent'])->name('tasks.indent');
    Route::post('/tasks/{task}/outdent', [TaskController::class, 'outdent'])->name('tasks.outdent');

    Route::resource('/team', ResourceController::class)->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
});
