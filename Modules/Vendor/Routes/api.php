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

Route::middleware('auth:api')->get('/vendor', function (Request $request) {
    return $request->user();
});
Route::prefix('vendor')->group(function () {
    /*
    //not needed this api.
    Route::post('quote-requests', [
        'as' => 'quote-requests', 'uses' => 'Api\CustomerController@quoteRequests'
    ]);*/
    Route::post('quote-sent-vendors', [
    'as' => 'quote-sent-vendors', 'uses' => 'Api\CustomerController@quoteSendVendors'
]);
    Route::post('view-sent-quote', [
    'as' => 'view-sent-quote', 'uses' => 'Api\CustomerController@view_sent_quote'
]);
    Route::post('sendVOtp', ['uses'=>'Api\VendorController@sendVOtp']);
    Route::post('verifyVOtp', ['uses'=>'Api\VendorController@verifyVOtp']);
    Route::post('quoteRequests', ['uses'=>'Api\VendorController@quoteRequests']);
    Route::post('sendQuote', ['uses'=>'Api\VendorController@submitSendQuote']);
    Route::post('sendQuoteResponse', ['uses'=>'Api\VendorController@submitQuoteResponse']);

    Route::post('search-quotes', ['uses'=>'Api\VendorController@searchQuote']);
    Route::post('get_profile', ['uses'=>'Api\VendorController@getProfile']);
    Route::post('update_profile', ['uses'=>'Api\VendorController@updateProfile']);
    Route::post('get_vendor_quote_response', ['uses'=> 'Api\VendorController@getVendorQuoteResponse']);

    Route::post('update_payment_status', ['uses'=> 'Api\VendorController@updatePaymentStatus']);

});
