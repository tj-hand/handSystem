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
        Route::post('/request-enviroment', 'UserController@requestEnviroment')->name('user.requestEnviroment')->middleware('auth:api');
        Route::post('/update-scope', 'UserController@updateScope')->name('user.updateScope')->middleware('auth:api');
    });

Route::prefix('password')
    ->name('api.')
    ->namespace('App\Http\Controllers')
    ->group(function () {
        Route::post('/reset-request', 'PasswordController@resetRequest')->name('password.reset-request');
        Route::post('/reset', 'PasswordController@reset')->name('password.reset');
    });

Route::prefix('account')
    ->name('api.')
    ->namespace('App\Http\Controllers')
    ->group(function () {
        Route::post('/show', 'AccountController@show')->name('account.show');
    });
