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

//Route::prefix('vendor')->group(function() {
    //Route::get('/', 'VendorController@index');
//});

Route::prefix('vendor')->group(function() {
	Route::get('/', 'VendorController@index');
	Route::get('profile', ['uses'=>'VendorController@profile']);
	Route::get('dashboard', ['uses'=>'VendorController@dashboard']);
	Route::get('download/{filename}', ['uses'=>'VendorController@getDownload']);
	Route::get('send-quote/{quote_id}/{vendorId?}', ['uses'=>'VendorController@send_quote']);
	Route::post('send-quote/{quote_id}', ['uses'=>'VendorController@submitSendQuote']);
	Route::get('view-sent-quote/{vendor_quote_id}', ['uses'=>'VendorController@view_sent_quote']);	
	
	Route::get('quote-requests', 'VendorController@quoteRequests')->name('vendor.quotes');
	Route::post('ajax_load_quote', 'VendorController@ajax_load_quote');
	
	Route::post('sendVOtp', ['uses'=>'VendorController@sendVOtp']);
	Route::post('verifyVOtp', ['uses'=>'VendorController@verifyVOtp']);
	Route::post('vendorRegister', ['uses'=>'VendorController@register']);
	Route::post('sendVendorOtp', ['uses'=>'VendorController@sendVendorOtp']);
	Route::post('verifyVendorOtp', ['uses'=>'VendorController@verifyVendorOtp']);

	Route::get('subscribe-now', ['uses'=>'VendorController@subscribeNow']);

	// Post Route For Make Payment Request
	Route::post('payment', 'VendorController@payment')->name('payment');


	Route::get('edit_profile', ['uses'=>'VendorController@edit_profile']);
	Route::post('submit_profile', ['uses'=>'VendorController@submit_profile']);
	
});
