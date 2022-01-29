<?php

//Route::prefix('admin')->group(function() {
//Route::get('/', 'AdminController@index');
//});
//admin
Route::prefix('admin')->group(function () {
    Route::get('/', 'LoginController@index');
    Route::get('login', 'LoginController@showLoginForm')->name('admin.login');
    Route::post('login', 'LoginController@login')->name('admin.login.submit');
    Route::get('dashboard', ['uses' => 'AdminController@index'])->name('admin.dashboard');
    Route::get('profile', 'AdminController@profile')->name('admin.profile');

    Route::get('add-vendor', 'VendorController@add_vendor')->name('admin.add.vendor');
    Route::post('save-vendor', 'VendorController@store')->name('admin.save.vendor');
    Route::get('vendors', 'VendorController@index')->name('admin.vendors');
    Route::post('filtervendors', 'VendorController@filterVendors')->name('admin.vendors.filter');
    Route::post('getVendorsByCategory', 'VendorController@getVendorsByCategory')->name('admin.cat.vendors');
    Route::get('edit-vendor/{vendor_id}', 'VendorController@edit_vendor')->name('admin.edit.vendor');
    Route::post('update-vendor/{vendor_id}', 'VendorController@update_vendor')->name('admin.update.vendor');

    Route::get('users', 'UserController@list_users')->name('admin.users');
    Route::post('add_users', 'UserController@add_users')->name('admin.add_users');
    Route::get('edit-user/{user_id}', 'UserController@edit_user')->name('admin.edit_user');
    Route::post('edit-user/{user_id}', 'UserController@edit_user');
    Route::post('delete_users', 'UserController@userDeleteRequests');
    Route::get('ajax_user_list', 'UserController@ajax_user_list')->name('admin.ajax_user_list');
    Route::get('settings', 'HomeController@settings')->name('admin.settings');
    // Route::get('settings/create', 'HomeController@createSettings')->name('admin.settings.create');
    Route::post('settings/create', 'HomeController@store')->name('admin.settings.create');
    Route::get('categories', 'CategoryController@index')->name('admin.categories');
    Route::post('category/edit_category', 'CategoryController@editCategory');
    Route::post('category/update/{$id}', 'CategoryController@update')->name('admin.update.category');

    // Tags Management
    Route::get('manage_tags', 'TagsController@manageTags')->name('admin.manage.tags');
    Route::post('search_tags', 'TagsController@searchTags')->name('admin.search.tags');
    Route::post('delete_cat_tag', 'TagsController@delCategoryTag')->name('admin.del.categorytag');
    Route::post('create_category', 'CategoryController@store')->name('admin.create.category');
    Route::get('category-tags/{category_id}', 'TagsController@index')->name('admin.category-tags');
    Route::post('category-tags/submit', 'TagsController@addTagByCategory')->name('admin.submit-category-tags');
    //Route::post('category-tags/{category_id}/bulkUpload', 'TagsController@bulkUpload')->name('admin.bulkUpload-category-tags');
    Route::post('category-tags/importData', 'TagsController@importData');
    Route::get('tags/{tag_id}/delete', 'TagsController@delete')->name('admin.delete.tags');

    Route::get('find-category', 'CategoryController@findcategory')->name('admin.find-category');
    Route::post('find-category/submit', 'CategoryController@submitFindCategory')->name('admin.submit-find-category');

    // manage city banner
    Route::get('manage_banner', 'BannerController@manageBanner')->name('admin.banner');
    Route::post('submit_banner', 'BannerController@submit_banner')->name('admin.submit_banner');
    Route::post('delete_banner', 'BannerController@deleteBanner')->name('admin.delete_banner');
    Route::post('banner/edit_banner', 'BannerController@editBanner')->name('admin.edit_banner');
    Route::post('banner/update', 'BannerController@update')->name('admin.update');
    // manage default banners
    Route::get('default_banners', 'BannerController@defaultBanners')->name('admin.default_banners');
    Route::post('submit_default_banner', 'BannerController@storeDefaultBanners')->name('admin.submit_default_banner');
    Route::post('edit_default_banner', 'BannerController@editDefaultBanner')->name('admin.edit_default_banner');
    Route::post('delete_default_banner', 'BannerController@deleteDefaultBanner')->name('admin.delete_default_banner');
    Route::post('update_default_banner', 'BannerController@updateDefaultBanner')->name('admin.update_default_banner');

    // Banned Words
    Route::get('bannedwords', 'BannedwordsController@index')->name('admin.bannedwords');
    Route::post('deletebannedword', 'BannedwordsController@delBannedWord')->name('admin.del.bannword');
    Route::post('bannedwords/submit', 'BannedwordsController@store')->name('admin.submit-bannedwords');
    Route::post('bannedwords/bulkUpload', 'BannedwordsController@bulkUpload')->name('admin.bulkUpload-bannedwords');
    Route::post('bannedwords/importData', 'BannedwordsController@importData');

    // Filter Words
    Route::get('neutralwords', 'NeutralwordsController@index')->name('admin.neutralwords');
    Route::post('neutralwords/submit', 'NeutralwordsController@store')->name('admin.submit-neutralwords');
    Route::post('neutralwords/bulkUpload', 'NeutralwordsController@bulkUpload')->name('admin.bulkUpload-neutralwords');
    Route::post('deleteneutralword', 'NeutralwordsController@delNeutralWord')->name('admin.del.neutword');
    Route::post('neutralwords/importData', 'NeutralwordsController@importData');

    Route::get('quote-requests', 'QuoteController@quoteRequests')->name('admin.quotes');
    Route::get('ajax-quote-requests', 'QuoteController@ajaxQuoteRequests')->name('admin.ajax_quotes');
    Route::get('misc-quote-requests', 'QuoteController@miscQuoteRequests');
    Route::get('get_misc_quote_list', 'QuoteController@get_misc_quote_list')->name('admin.get_misc_quote_list');
    Route::get('view-misc-quote-requests/{quote_id}', 'QuoteController@viewMiscQuoteRequests');
    //Route::get('vendor/edit_quote/{quote_id}', 'VendorController@editquote')->name('vendor.edit.quotes');
    Route::post('send-misc-quote-requests', 'QuoteController@sendMiscQuoteRequests')->name('admin.send.miscquote');
    Route::post('filter-misc-quote-vendors', 'QuoteController@filterMiscQuoteVendors')->name('admin.send.miscquote,vfilter');
    Route::post('edit_quote_request', 'QuoteController@quoteEditRequests');
    Route::post('delete_quote_request', 'QuoteController@quoteDeleteRequests');
    Route::post('update_quote_request', 'QuoteController@updateRequest')->name('admin.update_quote_request');

    Route::get('quote-sent-vendors/{quote_id}', ['uses' => 'QuoteController@quoteSentVendors']);
    Route::get('quotes-senttovendors', ['uses' => 'QuoteController@quotesSentToVendors']);
    Route::get('quote-responses/{quote_id}', ['uses' => 'QuoteController@quoteResponses']);
    Route::get('all-quote-responses', ['uses' => 'QuoteController@getAllQuoteResponses']);
    Route::get('view-sent-quote/{vendor_quote_id}', ['uses' => 'QuoteController@view_sent_quote']);
    Route::post('view_sent_quote', ['uses' => 'QuoteController@ajax_view_sent_quote']);
    Route::post('update_sent_quote_price', ['uses' => 'QuoteController@update_sent_quote_price'])->name('admin.update_sent_quote_price');
    Route::get('get_misc_vendors_list', 'QuoteController@getVendorList')->name('admin.get_misc_vendors_list');

    //Route::get('import-vendors', 'VendorController@import_vendors')->name('admin.import.vendors');
    //Route::post('import-vendors', 'VendorController@save_vendors')->name('admin.store.import.vendors');

    // Vendors Data Import - Export Routes
    Route::get('import-vendors', 'VendorController@import_vendors')->name('admin.import.vendors');
    Route::get('downloadData/{type}', 'VendorController@downloadData');
    Route::post('importData', 'VendorController@importData');
    // location imports
    // Route::get('add_location', 'AdminController@addLocation')->name('admin.add_location');

    Route::match(['get', 'post'], 'add_location', 'AdminController@addLocation')->name('admin.add_location');

    Route::get('import-location', 'AdminController@importLocation')->name('admin.import.location');
    Route::get('get_location', 'AdminController@get_location');
    Route::post('import-location-data', 'AdminController@importLocationData');
    Route::get('download-location/{type}', 'AdminController@downloadData');
    Route::get('edit-location/{location_id}', 'AdminController@edit_location')->name('admin.edit.location');
    Route::post('update-location/{location_id}', 'AdminController@update_location')->name('admin.update.location');
    Route::get('remove-location/{id}', 'AdminController@remove_location')->name('admin.remove.location');

    Route::get('get_vendors_list', 'VendorController@getVendorList')->name('admin.get_vendors_list');
    Route::post('delete_vendor', 'VendorController@vendorDeleteRequests');

    Route::get('raised_quote', 'QuoteController@raisedQuote')->name('admin.raised_quote');
    Route::post('get_vendor_count', 'VendorController@getVendorCount')->name('admin.get_vendor_count');

    Route::get('registered_vendors', 'VendorController@getRegisteredVendor');
    Route::post('registered_vendors', 'VendorController@getRegisteredVendor');

    Route::get('logout', 'LoginController@logout')->name('admin.logout');
    //blog module
    Route::get('blogs', 'BlogController@index')->name('admin.blogs');
    Route::post('create_blog', 'BlogController@store')->name('admin.create.blog');
    // Route::post('blog/edit_blog', 'BlogController@editblog');
    Route::get('edit_blog/{id}', 'BlogController@editBlog');
    Route::post('delete_blog', 'BlogController@blogDeleteRequests');

    /**
     * City Module
     */
    Route::get('cities', 'AdminController@getCities')->name('admin.cities');
    Route::get('ajax_cities', 'AdminController@ajaxGetCity')->name('admin.ajax_cities');
    Route::post('edit_city', 'AdminController@editCity')->name('admin.edit_city');
    Route::post('update_city', 'AdminController@updateCity')->name('admin.update_city');

});