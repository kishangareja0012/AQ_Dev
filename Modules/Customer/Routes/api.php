<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

/*Route::middleware('auth:api')->get('/customer', function (Request $request) {
    return $request->user();
});*/
Route::prefix('customer')->group(function() {
Route::post('quote-requests', [
    'as' => 'quote-requests', 'uses' => 'Api\CustomerController@quoteRequests'
]);
Route::post('quote-sent-vendors', [
    'as' => 'quote-sent-vendors', 'uses' => 'Api\CustomerController@quoteSendVendors'
]);
Route::post('view-sent-quote', [
    'as' => 'view-sent-quote', 'uses' => 'Api\CustomerController@view_sent_quote'
]);

Route::post('customer-profile', [
    'as' => 'customer-profile', 'uses' => 'Api\CustomerController@customer_profile'
]);

	/*Route::get('dashboard', ['uses'=>'CustomerController@dashboard']);
	Route::get('profile', ['uses'=>'CustomerController@profile']);
	Route::get('quote-requests', ['uses'=>'CustomerController@quoteRequests']);
	Route::get('quote-responses/{quote_id}', ['uses'=>'CustomerController@quoteResponses']);
	
	Route::get('quote-sent-vendors/{quote_id}', ['uses'=>'CustomerController@quoteSendVendors']);
	
	Route::get('view-sent-quote/{vendor_quote_id}', ['uses'=>'CustomerController@view_sent_quote']);
	Route::get('create-quote-request', 'CustomerController@createquote')->name('create.quotes');
	Route::post('create-quote-request', 'CustomerController@submitquote')->name('submit.quotes');
	Route::get('edit-quote-request/{quote_id}', 'CustomerController@editquote')->name('edit.quotes');
	Route::post('edit-quote-request/{quote_id}', 'CustomerController@updatequote')->name('update.quotes');
	Route::post('user_create_quotes', 'CustomerController@createQuote')->name('user.create.quotes');
	Route::get('select2-autocomplete-ajax', 'CustomerController@dataAjax');	
	Route::get('downlaod/{file}', ['uses'=>'CustomerController@downloadFile']);*/
	
});