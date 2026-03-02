<?php

use Illuminate\Support\Facades\Route;

Route::get('/', 'DashboardController@index')->name('hashguardian.dashboard');

Route::get('/requests', 'RequestsController@index')->name('hashguardian.requests.index');
Route::get('/requests/chart-data', 'RequestsController@chartData')->name('hashguardian.requests.chart-data');
Route::get('/requests/{uuid}', 'RequestsController@show')->name('hashguardian.requests.show');

Route::get('/queries', 'QueriesController@index')->name('hashguardian.queries.index');

Route::get('/exceptions', 'ExceptionsController@index')->name('hashguardian.exceptions.index');
Route::get('/exceptions/{uuid}', 'ExceptionsController@show')->name('hashguardian.exceptions.show');

Route::get('/jobs', 'JobsController@index')->name('hashguardian.jobs.index');
Route::get('/jobs/{uuid}', 'JobsController@show')->name('hashguardian.jobs.show');

Route::get('/outgoing-requests', 'OutgoingRequestsController@index')->name('hashguardian.outgoing-requests.index');

Route::get('/cache', 'CacheController@index')->name('hashguardian.cache.index');

Route::get('/mail', 'MailController@index')->name('hashguardian.mail.index');

Route::get('/notifications', 'NotificationsController@index')->name('hashguardian.notifications.index');

Route::get('/commands', 'CommandsController@index')->name('hashguardian.commands.index');

Route::get('/schedule', 'ScheduleController@index')->name('hashguardian.schedule.index');

Route::get('/logs', 'LogsController@index')->name('hashguardian.logs.index');

Route::get('/batches', 'BatchesController@index')->name('hashguardian.batches.index');

Route::get('/dumps', 'DumpsController@index')->name('hashguardian.dumps.index');

Route::get('/events', 'EventsController@index')->name('hashguardian.events.index');

Route::get('/gates', 'GatesController@index')->name('hashguardian.gates.index');

Route::get('/models', 'ModelsController@index')->name('hashguardian.models.index');

Route::get('/redis', 'RedisController@index')->name('hashguardian.redis.index');

Route::get('/views', 'ViewsController@index')->name('hashguardian.views.index');

Route::get('/timeline/{batchId}', 'TimelineController@show')->name('hashguardian.timeline.show');

Route::get('/server', 'ServerController@index')->name('hashguardian.server.index');
Route::get('/server/chart-data', 'ServerController@chartData')->name('hashguardian.server.chart-data');
Route::get('/server/correlate', 'ServerController@correlate')->name('hashguardian.server.correlate');

Route::get('/htop', 'HtopController@index')->name('hashguardian.htop.index');
Route::get('/htop/data', 'HtopController@data')->name('hashguardian.htop.data');

Route::get('/trends', 'TrendsController@index')->name('hashguardian.trends.index');
Route::get('/trends/chart-data', 'TrendsController@chartData')->name('hashguardian.trends.chart-data');
Route::get('/trends/comparison', 'TrendsController@comparison')->name('hashguardian.trends.comparison');
Route::get('/trends/slowest', 'TrendsController@slowest')->name('hashguardian.trends.slowest');

Route::get('/activity', 'ActivityController@index')->name('hashguardian.activity.index');
Route::get('/activity/users', 'ActivityController@users')->name('hashguardian.activity.users');
Route::get('/activity/user/{id}', 'ActivityController@user')->name('hashguardian.activity.user');
Route::get('/activity/session/{id}', 'ActivityController@session')->name('hashguardian.activity.session');

Route::get('/metrics', 'MetricsController@index')->name('hashguardian.metrics.index');
