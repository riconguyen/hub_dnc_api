<?php

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


//Route::get('/test', function (){
//    return view('test',['sitename'=>'3C Billing']);
//});


//Route::get('/', function (){
//
//    return view('dashboard', ['sitename'=>'#', 'lang'=>'vi']);
//});





  Route::group(['middleware' => ['cors','logVT']], function (){
    Route::get('/','DashboardController@getHomePage');
    Route::get('/exportExcel/{id}', 'BillingController@exportExcel');
    Route::get('/exportBilling', 'BillingController@exportBillog');
    Route::get('/exportCustomer', 'V1CustomerController@getDownloadCustomer');
    Route::get('/exportSipCallLog', 'SipController@exportSipCallLog');
    Route::get('/images/captcha', 'UserController@captcha');
    Route::post('/api2/loginWeb', 'UserController@LoginWebSession');
  });



//Route::get('/home', 'HomeController@index')->name('home');

//Auth::routes();


//
//Route::group(['prefix' => '{language}'], function ($language) {
//    config(['app.locale' => $language]); //đặt dòng này ở đầu
//
//    //Toàn bộ các route khác đặt ở đây.
//
//
//    Route::get('/', function ($language){
//        return view('dashboard', ['sitename'=>'#', 'lang'=>$language]);
//    });
//
//
//
//
//});