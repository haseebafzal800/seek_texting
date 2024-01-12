<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\TwillioCallbackController;


// use App\Http\Controllers\WithdrawRequestController;
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
    return response()->json('APIs are running!');
});

// Route::get('/home', function () {
//     return response()->json('APIs are running!');
// });
Route::get('/home', [HomeController::class, 'index'])->name('home');
// Route::get('/home', 'HomeController@index')->name('home');
Route::post('webhook/sms_status', [TwillioCallbackController::class, 'smsStatus']);
// Route::post('webhook/sms_status', [TwillioCallbackController::class, 'smsStatus']);
Route::post('webhook/receive_sms', [TwillioCallbackController::class, 'receiveSMS']);
Route::post('webhook/incoming-call', [TwillioCallbackController::class, 'incommingCall']);
Route::get('webhook/dropdb/{id?}', [TwillioCallbackController::class, 'fetchMessages']);
Route::post('webhook/call-status', [TwillioCallbackController::class, 'callStatus']);
Route::any('call-to-number', [TwillioCallbackController::class, 'callToNumber']);
Route::any('call-to', [TwillioCallbackController::class, 'callTo']);
