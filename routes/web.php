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
    Route::get('dashboard/api/data', [DashboardController::class, 'dataIndex'])->name('dashboard.api.data.index');
    Route::get('dashboard/api/data/{data}', [DataViewController::class, 'dataShow'])->name('dashboard.api.data.show');
    Route::get('dashboard/api/data/{data}/doc-page', [DataViewController::class, 'docPage'])->name('dashboard.api.data.doc-page');
    Route::get('dashboard/api/data/{data}/doc-content', [DataViewController::class, 'docContent'])->name('dashboard.api.data.doc-content');
    Route::post('dashboard/api/data/{data}/ask', [DataViewController::class, 'ask'])->name('dashboard.api.data.ask');
    Route::post('dashboard/api/data/{data}/ask/stream', [DataViewController::class, 'askStream'])->name('dashboard.api.data.ask.stream');
    Route::post('dashboard/api/data/{data}/chart-suggestion', [DataViewController::class, 'chartSuggestion'])->name('dashboard.api.data.chart-suggestion');
    Route::get('dashboard/api/data/{data}/rows', [DataTableRowsController::class, 'index'])->name('dashboard.api.data.rows.index');
    Route::patch('dashboard/api/data/{data}/rows/{data_table_row}', [DataTableRowsController::class, 'update'])->name('dashboard.api.data.rows.update');
    Route::delete('dashboard/api/data/{data}/rows/{data_table_row}', [DataTableRowsController::class, 'destroy'])->name('dashboard.api.data.rows.destroy');
    Route::get('dashboard/data/{data}', [DataViewController::class, 'show'])->name('dashboard.data.show');
});

require __DIR__.'/settings.php';
