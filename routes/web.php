<?php

use App\Http\Controllers\Api\DigitalizeController as ApiDigitalizeController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DataTableRowsController;
use App\Http\Controllers\DataViewController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::inertia('/', 'Welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('dashboard/digitalize', [ApiDigitalizeController::class, 'store'])->name('dashboard.digitalize');
    Route::post('dashboard/api/data/{data}/append-rows', [ApiDigitalizeController::class, 'appendToTable'])->name('dashboard.api.data.append-rows');
    Route::get('dashboard/api/data', [DashboardController::class, 'dataIndex'])->name('dashboard.api.data.index');
    Route::delete('dashboard/api/data/{data}', [DashboardController::class, 'destroyData'])->name('dashboard.api.data.destroy');
    Route::get('dashboard/api/data/{data}', [DataViewController::class, 'dataShow'])->name('dashboard.api.data.show');
    Route::patch('dashboard/api/data/{data}', [DataViewController::class, 'update'])->name('dashboard.api.data.update');
    Route::get('dashboard/api/data/{data}/doc-page', [DataViewController::class, 'docPage'])->name('dashboard.api.data.doc-page');
    Route::get('dashboard/api/data/{data}/doc-content', [DataViewController::class, 'docContent'])->name('dashboard.api.data.doc-content');
    Route::post('dashboard/api/data/{data}/ask', [DataViewController::class, 'ask'])->name('dashboard.api.data.ask');
    Route::post('dashboard/api/data/{data}/ask/stream', [DataViewController::class, 'askStream'])->name('dashboard.api.data.ask.stream');
    Route::post('dashboard/api/data/{data}/chart-suggestion', [DataViewController::class, 'chartSuggestion'])->name('dashboard.api.data.chart-suggestion');
    Route::get('dashboard/api/data/{data}/rows', [DataTableRowsController::class, 'index'])->name('dashboard.api.data.rows.index');
    Route::post('dashboard/api/data/{data}/rows', [DataTableRowsController::class, 'store'])->name('dashboard.api.data.rows.store');
    Route::patch('dashboard/api/data/{data}/rows/{data_table_row}', [DataTableRowsController::class, 'update'])->name('dashboard.api.data.rows.update');
    Route::delete('dashboard/api/data/{data}/rows/{data_table_row}', [DataTableRowsController::class, 'destroy'])->name('dashboard.api.data.rows.destroy');
    Route::patch('dashboard/api/data/{data}/doc-content', [DataViewController::class, 'updateDocContent'])->name('dashboard.api.data.doc-content.update');
    Route::get('dashboard/data/{data}', [DataViewController::class, 'show'])->name('dashboard.data.show');
    Route::get('data', [DashboardController::class, 'dataPage'])->name('data.index');
});

require __DIR__.'/settings.php';
