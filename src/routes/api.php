<?php
Route::group(['namespace' => 'Abs\HelperPkg\Api', 'middleware' => ['api']], function () {
	Route::group(['prefix' => 'helper-pkg/api'], function () {
		Route::group(['middleware' => ['auth:api']], function () {
			// Route::get('taxes/get', 'TaxController@getTaxes');
		});
	});
});