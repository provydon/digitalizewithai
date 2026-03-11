<?php

use App\Http\Controllers\Api\DigitalizeController as ApiDigitalizeController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DataTableRowsController;
use App\Http\Controllers\DataViewController;
use App\Http\Controllers\FolderController;
use App\Http\Controllers\OAuthController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::inertia('/', 'Welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::prefix('oauth')->group(function () {
    Route::get('/{provider}/redirect', [OAuthController::class, 'redirect'])->middleware('guest')->name('oauth.redirect');
    Route::get('/{provider}/callback', [OAuthController::class, 'callback'])->name('oauth.callback');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('dashboard/api/digitalize-options', [ApiDigitalizeController::class, 'digitalizeOptions'])->name('dashboard.api.digitalize-options');
    Route::post('dashboard/digitalize', [ApiDigitalizeController::class, 'store'])->name('dashboard.digitalize');
    Route::post('dashboard/digitalize/batch', [ApiDigitalizeController::class, 'storeBatch'])->name('dashboard.digitalize.batch');
    Route::post('dashboard/api/data/{data}/append-rows', [ApiDigitalizeController::class, 'appendToTable'])->name('dashboard.api.data.append-rows');
    Route::post('dashboard/api/data/{data}/append-doc', [ApiDigitalizeController::class, 'appendToDoc'])->name('dashboard.api.data.append-doc');
    Route::get('dashboard/api/data', [DashboardController::class, 'dataIndex'])->name('dashboard.api.data.index');
    Route::get('dashboard/api/folders', [FolderController::class, 'index'])->name('dashboard.api.folders.index');
    Route::post('dashboard/api/folders', [FolderController::class, 'store'])->name('dashboard.api.folders.store');
    Route::patch('dashboard/api/folders/{folder}', [FolderController::class, 'update'])->name('dashboard.api.folders.update');
    Route::delete('dashboard/api/folders/{folder}', [FolderController::class, 'destroy'])->name('dashboard.api.folders.destroy');
    Route::delete('dashboard/api/data/{data}', [DashboardController::class, 'destroyData'])->name('dashboard.api.data.destroy');
    Route::get('dashboard/api/data/{data}', [DataViewController::class, 'dataShow'])->name('dashboard.api.data.show');
    Route::get('dashboard/api/data/{data}/original-file', [DataViewController::class, 'originalFile'])->name('dashboard.api.data.original-file');
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
    Route::get('dashboard/api/data/{data}/saved-chats', [DataViewController::class, 'savedChatsIndex'])->name('dashboard.api.data.saved-chats.index');
    Route::post('dashboard/api/data/{data}/saved-chats', [DataViewController::class, 'savedChatStore'])->name('dashboard.api.data.saved-chats.store');
    Route::patch('dashboard/api/data/{data}/saved-chats/{saved_chat}', [DataViewController::class, 'savedChatUpdate'])->name('dashboard.api.data.saved-chats.update');
    Route::delete('dashboard/api/data/{data}/saved-chats/{saved_chat}', [DataViewController::class, 'savedChatDestroy'])->name('dashboard.api.data.saved-chats.destroy');
    Route::get('dashboard/api/data/{data}/saved-charts', [DataViewController::class, 'savedChartsIndex'])->name('dashboard.api.data.saved-charts.index');
    Route::post('dashboard/api/data/{data}/saved-charts', [DataViewController::class, 'savedChartStore'])->name('dashboard.api.data.saved-charts.store');
    Route::delete('dashboard/api/data/{data}/saved-charts/{saved_chart}', [DataViewController::class, 'savedChartDestroy'])->name('dashboard.api.data.saved-charts.destroy');
    Route::get('dashboard/data/{data}', [DataViewController::class, 'show'])->name('dashboard.data.show');
    Route::get('data', [DashboardController::class, 'dataPage'])->name('data.index');
});

require __DIR__.'/settings.php';
