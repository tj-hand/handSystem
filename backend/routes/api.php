<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/login', function () {
    return "login.redirect";
})->name('login');

Route::prefix('auth')
    ->name('api.')
    ->namespace('App\Http\Controllers')
    ->group(function () {
        Route::post('/request-tokens', 'AuthController@getTokensAsCookies')->name('auth.token');
        Route::post('/revoke-tokens', 'AuthController@revokeTokens')->name('auth.revokeToken')->middleware('auth:api');
    });

Route::prefix('user')
    ->name('api.')
    ->namespace('App\Http\Controllers')
    ->group(function () {
        Route::post('/show', 'UserController@show')->name('user.show')->middleware('auth:api');
        Route::post('/exists', 'UserController@exists')->name('user.exists')->middleware('auth:api');
        Route::post('/upsert', 'UserController@upsert')->name('user.upsert')->middleware('auth:api');
        Route::post('/delete', 'UserController@delete')->name('user.delete')->middleware('auth:api');
        Route::post('/send-invite', 'UserController@sendInvite')->name('user.sendInvite')->middleware('auth:api');
        Route::post('/update-scope', 'UserController@updateScope')->name('user.updateScope')->middleware('auth:api');
        Route::post('/add-to-account', 'UserController@addToAccount')->name('user.addToAccount')->middleware('auth:api');
        Route::post('/request-enviroment', 'UserController@requestEnviroment')->name('user.requestEnviroment')->middleware('auth:api');
    });

Route::prefix('password')
    ->name('api.')
    ->namespace('App\Http\Controllers')
    ->group(function () {
        Route::post('/reset', 'PasswordController@reset')->name('password.reset');
        Route::post('/reset-request', 'PasswordController@resetRequest')->name('password.reset-request');
    });

Route::prefix('account')
    ->name('api.')
    ->namespace('App\Http\Controllers')
    ->group(function () {
        Route::post('/show', 'AccountController@show')->name('account.show')->middleware('auth:api');
        Route::post('/users', 'AccountController@users')->name('account.users')->middleware('auth:api');
        Route::post('/delete', 'AccountController@delete')->name('account.delete')->middleware('auth:api');
        Route::post('/groups', 'AccountController@groups')->name('account.groups')->middleware('auth:api');
        Route::post('/upsert', 'AccountController@upsert')->name('account.upsert')->middleware('auth:api');
    });

Route::prefix('group')
    ->name('api.')
    ->namespace('App\Http\Controllers')
    ->group(function () {
        Route::post('/show', 'GroupController@show')->name('group.show')->middleware('auth:api');
        Route::post('/upsert', 'GroupController@upsert')->name('group.upsert')->middleware('auth:api');
        Route::post('/delete', 'GroupController@delete')->name('group.delete')->middleware('auth:api');
        Route::post('/associated_users', 'GroupController@associatedUsers')->name('group.associatedUsers')->middleware('auth:api');
        Route::post('/associated_actions', 'GroupController@associatedActions')->name('group.associatedActions')->middleware('auth:api');
    });
Route::prefix('authorization')
    ->name('api.')
    ->namespace('App\Http\Controllers')
    ->group(function () {
        Route::post('/set', 'AuthorizationController@set')->name('authorization.set')->middleware('auth:api');
        Route::post('/queue', 'AuthorizationController@queue')->name('authorization.queue')->middleware('auth:api');
    });
