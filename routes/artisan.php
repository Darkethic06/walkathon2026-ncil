<?php

use App\Support\SsrServer;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

Route::get('clear', function () {
    Artisan::call('optimize:clear');
    Artisan::call('config:clear');
    Artisan::call('cache:clear');
    Artisan::call('route:clear');
    Artisan::call('view:clear');
    Artisan::call('clear-compiled');
    return 'Cleared.';
});



// Inertia SSR server control — the web equivalent of `php artisan inertia:start-ssr`
// (plus stop / restart / status). It starts Node detached in the background.
// SUPER-ADMIN only: these spawn server processes, so they must never be public.
// Use /admin/ssr/restart after a deploy so Node loads the new bundle.
Route::middleware(['auth', 'role:SUPER-ADMIN'])->prefix('admin/ssr')->name('admin.ssr.')->group(function () {
    foreach (['start', 'stop', 'restart', 'status'] as $action) {
        Route::get($action, function (SsrServer $ssr) use ($action) {
            $result = $ssr->{$action}();

            return response($result['message'], $result['ok'] ? 200 : 500)
                ->header('Content-Type', 'text/plain');
        })->name($action);
    }
});

if (config('app.artisan') == 1) {

    Route::get('view-env', function () {
        return file_get_contents($_SERVER['DOCUMENT_ROOT'] . "/../.env", true);
    });

    Route::get('laravel-log', [\Rap2hpoutre\LaravelLogViewer\LogViewerController::class, 'index']);

    Route::get('migrate', function () {
        Artisan::call('migrate');
        return 'Migrate done.';
    });
    Route::get('migrate-fresh-seed', function () {
        Artisan::call('migrate:fresh --seed');
        return 'Migrate fresh and seeder done.';
    });

    Route::get('db-seed', function () {
        Artisan::call('db:seed');
        return 'Seeder done.';
    });
    Route::get('single-seed/{class_name}', function ($class_name) {
        Artisan::call('db:seed --class=' . $class_name);
        return 'seeder done.';
    });
    Route::get('migration-roleback/{file_name}', function ($file_name) {
        Artisan::call('migrate:rollback --path=/database/migrations/' . $file_name);
        return 'roleback done.';
    });
    Route::get('storage-link', function () {
        Artisan::call('storage:link');
        return 'storage link generate.';
    });
}
