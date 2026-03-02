<?php

use Illuminate\Support\Facades\Route;

Route::get('/stats', 'Api\V1\StatsApiController@index')->name('hashguardian.api.stats');
Route::get('/trends', 'Api\V1\TrendsApiController@index')->name('hashguardian.api.trends');
Route::get('/activity', 'Api\V1\ActivityApiController@index')->name('hashguardian.api.activity');
Route::get('/entries/{type}', 'Api\V1\EntriesApiController@index')->name('hashguardian.api.entries.index');
Route::get('/entries/{type}/{uuid}', 'Api\V1\EntriesApiController@show')->name('hashguardian.api.entries.show');
Route::get('/metrics', 'Api\V1\MetricsApiController@index')->name('hashguardian.api.metrics');
Route::post('/export', 'Api\V1\ExportApiController@store')->name('hashguardian.api.export');
Route::get('/export/{id}', 'Api\V1\ExportApiController@show')->name('hashguardian.api.export.show');
