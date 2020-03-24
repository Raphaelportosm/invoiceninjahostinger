<?php

use Illuminate\Support\Facades\Route;

Route::get('client', 'Auth\ContactLoginController@showLoginForm')->name('client.login'); //catch all

Route::get('client/login', 'Auth\ContactLoginController@showLoginForm')->name('client.login')->middleware('locale');
Route::post('client/login', 'Auth\ContactLoginController@login')->name('client.login.submit');

Route::get('client/password/reset', 'Auth\ContactForgotPasswordController@showLinkRequestForm')->name('client.password.request')->middleware('locale');
Route::post('client/password/email', 'Auth\ContactForgotPasswordController@sendResetLinkEmail')->name('client.password.email')->middleware('locale');
Route::get('client/password/reset/{token}', 'Auth\ContactResetPasswordController@showResetForm')->name('client.password.reset')->middleware('locale');
Route::post('client/password/reset', 'Auth\ContactResetPasswordController@reset')->name('client.password.update')->middleware('locale');

//todo implement domain DB
Route::group(['middleware' => ['auth:contact','locale'], 'prefix' => 'client', 'as' => 'client.'], function () {

	Route::get('dashboard', 'ClientPortal\DashboardController@index')->name('dashboard'); // name = (dashboard. index / create / show / update / destroy / edit

	Route::get('invoices', 'ClientPortal\InvoiceController@index')->name('invoices.index')->middleware('portal_enabled');
	Route::post('invoices/payment', 'ClientPortal\InvoiceController@bulk')->name('invoices.bulk');
	Route::get('invoices/{invoice}', 'ClientPortal\InvoiceController@show')->name('invoice.show');
	Route::get('invoices/{invoice_invitation}', 'ClientPortal\InvoiceController@show')->name('invoice.show_invitation');

	Route::get('recurring_invoices', 'ClientPortal\RecurringInvoiceController@index')->name('recurring_invoices.index')->middleware('portal_enabled');
	Route::get('recurring_invoices/{recurring_invoice}', 'ClientPortal\RecurringInvoiceController@show')->name('recurring_invoices.show');
	Route::get('recurring_invoices/{recurring_invoice}/request_cancellation', 'ClientPortal\RecurringInvoiceController@requestCancellation')->name('recurring_invoices.request_cancellation');

	Route::post('payments/process', 'ClientPortal\PaymentController@process')->name('payments.process');
	Route::get('payments', 'ClientPortal\PaymentController@index')->name('payments.index')->middleware('portal_enabled');
	Route::get('payments/{payment}', 'ClientPortal\PaymentController@show')->name('payments.show');
	Route::post('payments/process/response', 'ClientPortal\PaymentController@response')->name('payments.response');
	Route::get('payments/process/response', 'ClientPortal\PaymentController@response')->name('payments.response.get');

	Route::get('profile/{client_contact}/edit', 'ClientPortal\ProfileController@edit')->name('profile.edit');
	Route::put('profile/{client_contact}/edit', 'ClientPortal\ProfileController@update')->name('profile.update');
	Route::put('profile/{client_contact}/edit_client', 'ClientPortal\ProfileController@updateClient')->name('profile.edit_client');
	Route::put('profile/{client_contact}/localization', 'ClientPortal\ProfileController@updateClientLocalization')->name('profile.edit_localization');

	Route::resource('payment_methods', 'ClientPortal\PaymentMethodController');// name = (payment_methods. index / create / show / update / destroy / edit

    Route::match(['GET', 'POST'], 'quotes/approve', 'ClientPortal\QuoteController@bulk')->name('quotes.bulk');
    Route::resource('quotes', 'ClientPortal\QuoteController')->only('index', 'show');

    Route::resource('credits', 'ClientPortal\CreditController')->only('index', 'show');

	Route::post('document', 'ClientPortal\DocumentController@store')->name('document.store');
	Route::delete('document', 'ClientPortal\DocumentController@destroy')->name('document.destroy');

	Route::get('logout', 'Auth\ContactLoginController@logout')->name('logout');
});

Route::group(['middleware' => ['invite_db'], 'prefix' => 'client', 'as' => 'client.'], function () {

	Route::get('invoice/{invitation_key}/download_pdf', 'InvoiceController@downloadPdf');
  	Route::get('quote/{invitation_key}/download_pdf', 'QuoteController@downloadPdf');
  	Route::get('credit/{invitation_key}/download_pdf', 'CreditController@downloadPdf');
  	Route::get('{entity}/{invitation_key}/download', 'ClientPortal\InvitationController@routerForDownload');

	/*Invitation catches*/
	Route::get('{entity}/{invitation_key}','ClientPortal\InvitationController@router');
	Route::get('{entity}/{client_hash}/{invitation_key}','ClientPortal\InvitationController@routerForIframe'); //should never need this
	Route::get('payment_hook/{company_gateway_id}/{gateway_type_id}','ClientPortal\PaymentHookController@process');

});

Route::fallback('BaseController@notFoundClient');
