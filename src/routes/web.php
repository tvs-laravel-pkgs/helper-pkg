<?php

Route::group(['namespace' => 'Abs\HelperPkg', 'middleware' => ['web', 'auth'], 'prefix' => 'helper-pkg'], function () {
	Route::get('/helpers/get-list', 'HelperController@getHelperList')->name('getHelperList');
	Route::get('/helper/get-form-data/{id?}', 'HelperController@getHelperFormData')->name('getHelperFormData');
	Route::post('/helper/save', 'HelperController@saveHelper')->name('saveHelper');
	Route::get('/helper/delete/{id}', 'HelperController@deleteHelper')->name('deleteHelper');

});