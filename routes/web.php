<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DailyReportController;

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

Route::get('/', function () {
    return view('welcome');
});
Route::get('/dsrpdf', [DailyReportController::class, 'dsrpdf']);
Route::get('/fileexport', [DailyReportController::class, 'fileExport']);
