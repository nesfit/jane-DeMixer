<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| This route group applies the "web" middleware group to every route
| it contains. The "web" middleware group is defined in your HTTP
| kernel and includes session state, CSRF protection, and more.
|
*/

use Illuminate\Http\Request;
use GuzzleHttp\Client;

Route::group(['middleware' => ['web']], function () {

    Route::get('/', function () {
        return view('basic_search');
    });

    Route::get('/basic_search', function () {
        return view('basic_search');
    });
    
    Route::get('/advanced_search', function () {
        return view('advanced_search');
    });
    

    Route::post('/searchrequest', 'DemixerController@findMatchingTransactions');
    
});

Route::any('{query}',  function() {
    return redirect('/'); }
    )->where('query', '.*');
