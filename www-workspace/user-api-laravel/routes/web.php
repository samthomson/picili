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

/*
Route::get('/', function () {
    // return view('index');
    return File::get(public_path() . '/index.html');
    // return response()->json([':)']);
});
*/

Route::group(['prefix' => 'app', 'middleware' => 'cors'], function () {

    Route::post('/register', 'UserController@register');
    Route::post('/authenticate', 'UserController@authenticate');


    Route::group(['middleware' => 'jwt.auth'], function () {
        Route::get('/me', 'UserController@getUser');
        Route::get('/settings', 'UserController@getSettings');

        Route::put('/settings/dropboxfolder', 'UserController@updateDropboxFilesource');
        Route::delete('/settings/dropbox', 'UserController@disconnectDropbox');

        Route::put('/settings/privacy', 'UserController@updatePrivacy');

        Route::get('/fileinfo', 'UserController@getFileInfo');
        
        Route::get('/pagestate/{username}', 'AppController@getPageState');
    });
    Route::get('/search', 'SearchController@search');
});

Route::group([/*'middleware' => 'jwt.auth'*/], function () {
    Route::get('/oauth/dropbox', 'UserController@connectDropbox');
});

Route::any( '{catchall?}', function () {
    $sIndexPath = \File::get(public_path() . DIRECTORY_SEPARATOR . 'index.html');
    // echo "index path: {$sIndexPath}";
    return response($sIndexPath);
} )->where('catchall', '(.*)');
