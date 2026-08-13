<?php

use EloquentWorks\Sentinel\Http\Controllers\AssignmentController;
use EloquentWorks\Sentinel\Http\Controllers\CaseController;
use EloquentWorks\Sentinel\Http\Controllers\DashboardController;
use EloquentWorks\Sentinel\Http\Controllers\EnforcementController;
use EloquentWorks\Sentinel\Http\Controllers\ReportController;
use EloquentWorks\Sentinel\Http\Controllers\UserModerationController;
use EloquentWorks\Sentinel\Http\Controllers\WatchlistController;
use Illuminate\Support\Facades\Route;

Route::get('/', DashboardController::class)->name('dashboard');
Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
Route::get('/reports/{report}', [ReportController::class, 'show'])->name('reports.show');
Route::post('/reports/{report}/triage', [ReportController::class, 'triage'])->middleware('sentinel.can:sentinel.reports.manage')->name('reports.triage');
Route::post('/reports/{report}/dismiss', [ReportController::class, 'dismiss'])->middleware('sentinel.can:sentinel.reports.manage')->name('reports.dismiss');
Route::post('/reports/{report}/case', [ReportController::class, 'openCase'])->middleware('sentinel.can:sentinel.cases.manage')->name('reports.case');
Route::get('/cases', [CaseController::class, 'index'])->name('cases.index');
Route::get('/cases/{case}', [CaseController::class, 'show'])->name('cases.show');
Route::post('/cases/{case}/notes', [CaseController::class, 'note'])->middleware('sentinel.can:sentinel.cases.manage')->name('cases.notes');
Route::post('/cases/{case}/resolve', [CaseController::class, 'resolve'])->middleware('sentinel.can:sentinel.cases.resolve')->name('cases.resolve');
Route::post('/cases/{case}/escalate', [CaseController::class, 'escalate'])->middleware('sentinel.can:sentinel.cases.manage')->name('cases.escalate');
Route::post('/cases/{case}/assign', [AssignmentController::class, 'store'])->middleware('sentinel.can:sentinel.cases.assign')->name('cases.assign');
Route::get('/users/{user}', [UserModerationController::class, 'show'])->name('users.show');
Route::post('/users/{user}/watch', [WatchlistController::class, 'store'])->middleware('sentinel.can:sentinel.watchlist.manage')->name('users.watch');
Route::middleware('sentinel.not-masquerading')->group(function (): void {
    Route::post('/users/{user}/warn', [EnforcementController::class, 'warn'])->middleware('sentinel.can:sentinel.enforcement.warn')->name('users.warn');
    Route::post('/users/{user}/strike', [EnforcementController::class, 'strike'])->middleware('sentinel.can:sentinel.enforcement.strike')->name('users.strike');
    Route::post('/users/{user}/ban', [EnforcementController::class, 'ban'])->middleware('sentinel.can:sentinel.enforcement.ban')->name('users.ban');
    Route::post('/users/{user}/restrict', [EnforcementController::class, 'restrict'])->middleware('sentinel.can:sentinel.enforcement.restrict')->name('users.restrict');
    Route::post('/users/{user}/masquerade', [EnforcementController::class, 'masquerade'])->middleware('sentinel.can:sentinel.masquerade')->name('users.masquerade');
});
