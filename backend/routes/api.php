<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')
    ->name('api.')
    ->namespace('App\Http\Controllers')
    ->group(function () {
        Route::post('/token', 'AuthController@getTokensAsCookies')->name('auth.token');
        Route::post('/logout', 'AuthController@revokeTokens')->name('auth.logout')->middleware('auth:api');
    });

Route::prefix('user')
    ->name('api.')
    ->namespace('App\Http\Controllers')
    ->group(function () {
        Route::post('/login', 'LoginController@index')->name('user.login')->middleware('auth:api');
    });

Route::prefix('password')
    ->name('api.')
    ->namespace('App\Http\Controllers')
    ->group(function () {
        Route::post('/reset-request', 'PasswordController@resetRequest')->name('password.reset-request');
        Route::post('/reset', 'PasswordController@reset')->name('password.reset');
    });
