<?php

  /*
  |--------------------------------------------------------------------------
  | API Routes
  |--------------------------------------------------------------------------
  |
  | Here is where you can register API routes for your application. These
  | routes are loaded by the RouteÚServiceProvider within a group which
  | is assigned the "api" middleware group. Enjoy building your API!
  |
  */
//Route::middleware('auth:api')->get('/user', function (Request $request) {
//    return $request->user();
//});
  Route::group(['middleware' => ['api', 'cors', 'logVT']], function () {
    Route::post('auth/login', 'ApiController@login'); // API LOGIN
    Route::post('auth/loginWeb', 'ApiController@loginWeb'); // WEB LOGIN WITH GCAPCHA TOKEN VERIFY
    Route::group(['middleware' => ['jwt.auth', 'jwt-auth']], function () {
      Route::prefix('/v1')->group(function () {
        Route::post('dnc', 'V1DNDController@postDnd'); //2.1

      });
    });
  });




