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

Route::group(['domain' => env('ES_SERVICES_DOMAIN')], function () {
    // 1. Root Routes
    Route::get('/', function () {
        return "Welcome Elastic Search API: <a href='".env('ES_SERVICES_DOMAIN')."' name='Elastic Search Git'>Elastic Search</a><br /> Laravel:5.4.36 "; // .app()::VERSION;
    });

    // 2, Config Routes
    Route::group(['prefix' => 'config'], function () {
        // 2.1 Config Routes List
        Route::get('/', 'EsConfigController@index'); // http://xxx.com/config

        // 2.2 Index Routes
        Route::get('/index', 'EsConfigController@allIndex'); // http://xxx.com/config/index/all
        Route::get('/index/get', 'EsConfigController@queryIndex'); // http://xxx.com/config/index/get?index_name=index_name1
        Route::post('/index/create', 'EsConfigController@createIndex'); // http://xxx.com/config/index/create
        Route::post('/index/delete', 'EsConfigController@deleteIndex'); // http://xxx.com/config/index/delete

        // 2.3 Template Routes
        Route::get('/template', 'EsConfigController@allTemplate'); // http://xxx.com/config/template/all
        Route::get('/template/get', 'EsConfigController@queryTemplate'); // http://xxx.com/config/template/get?index_name=index_name1
        Route::post('/template/create', 'EsConfigController@createTemplate'); // http://xxx.com/config/template/create
        Route::post('/template/delete', 'EsConfigController@deleteTemplate'); // http://xxx.com/config/template/delete

        // 2.4 IK Routes
        Route::get('/ik', 'EsConfigController@allIK'); // http://xxx.com/config/ik
        Route::get('/ik/add', 'EsConfigController@addIK'); // http://xxx.com/config/ik/add?key_word=xxxxx
    });

    // 3, Data Routes
    Route::group(['prefix' => 'data'], function () {
        // 3.1 Data Routes List
        Route::get('/', 'EsDataController@index'); // http://xxx.com/data

        // 3.2 Data Handle Routes
        Route::post('/insert', 'EsDataController@insertData'); // http://xxx.com/data/insert
        Route::post('/bulk', 'EsDataController@bulkInsertData'); // http://xxx.com/data/bulk
        Route::post('/delete/{id}', 'EsDataController@deleteDataById');  // http://xxx.com/data/delete/xxxid
        Route::get('/{index_name}/{type_name}/{id}', 'EsDataController@queryDataById');  // http://xxx.com/data/{index_name}/{type_name}/{id}

        Route::post('/rawquery', 'EsDataController@queryDataWithRawDSL');  // http://xxx.com/data/rawquery with Request Body
        Route::post('/query', 'EsDataController@queryDataWithDSL');  // http://xxx.com/data/query with Request Body
    });

    // 3, Businiess Routes
});
