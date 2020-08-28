<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::group(['prefix' => 'linkedin'], function () {
    Route::get('all', 'LinkedinController@all')->middleware(['cors']);
    Route::get('infoAccess/{userId}', 'LinkedinController@infoAccess');
    Route::post('auth', 'LinkedinController@auth')->middleware(['cors']);
    Route::post('sharePost', 'LinkedinController@sharePost');
});
