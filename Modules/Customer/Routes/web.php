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

Route::prefix('customer')->group(function() {
	Route::get('dashboard', ['uses'=>'CustomerController@dashboard']);
	Route::get('profile', ['uses'=>'CustomerController@profile']);
	Route::get('edit_profile', ['uses'=>'CustomerController@edit_profile']);
	Route::post('submit_profile', ['uses'=>'CustomerController@submit_profile']);
	Route::get('quote-requests', ['uses'=>'CustomerController@quoteRequests']);
	Route::post('ajax_quote_load', ['uses'=>'CustomerController@ajax_quote_load']);
	Route::get('quote-responses/{quote_id}', ['uses'=>'CustomerController@quoteResponses']);
	Route::get('quote-sent-vendors/{quote_id}', ['uses'=>'CustomerController@quoteSendVendors']);
	Route::post('ajax_load_sent_quote', ['uses'=>'CustomerController@ajax_load_sent_quote']);
	Route::get('view-sent-quote/{vendor_quote_id}', ['uses'=>'CustomerController@view_sent_quote']);
	Route::get('create-quote-request', 'CustomerController@createquote')->name('create.quotes');
	Route::post('create-quote-request', 'CustomerController@submitquote')->name('submit.quotes');
	Route::get('edit-quote-request/{quote_id}', 'CustomerController@editquote')->name('edit.quotes');
	Route::post('edit-quote-request/{quote_id}', 'CustomerController@updatequote')->name('update.quotes');
	Route::post('user_create_quotes', 'CustomerController@createQuote')->name('user.create.quotes');
	Route::get('select2-autocomplete-ajax', 'CustomerController@dataAjax');	
	Route::get('downlaod/{file}', ['uses'=>'CustomerController@downloadFile']);
});