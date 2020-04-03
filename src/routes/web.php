<?php

Route::group(['namespace' => 'Abs\HelperPkg', 'middleware' => ['web']], function () {
	Route::get('theme/', 'ThemeDemoController@home')->name('theme');
});