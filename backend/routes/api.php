<?php

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
        Route::post('/add-to-client', 'UserController@addToClient')->name('user.addToClient')->middleware('auth:api');
        Route::post('/add-to-account', 'UserController@addToAccount')->name('user.addToAccount')->middleware('auth:api');
        Route::post('/exists-in-client', 'UserController@existsInClient')->name('user.existsInClient')->middleware('auth:api');
        Route::post('/request-enviroment', 'UserController@requestEnviroment')->name('user.requestEnviroment')->middleware('auth:api');
        Route::post('/associated-with-clients', 'UserController@associatedWithClients')->name('user.associatedWithClients')->middleware('auth:api');
        Route::post('/associated-with-groups', 'UserController@associatedWithGroups')->name('user.associatedWithGroups')->middleware('auth:api');
        Route::post('/local_associated-with-groups', 'UserController@localAssociatedWithGroups')->name('user.localAssociatedWithGroups')->middleware('auth:api');
        Route::post('/associated-with-local-actions', 'UserController@associatedWithLocalActions')->name('user.associatedWithLocalActions')->middleware('auth:api');
        Route::post('/associated-with-global-actions', 'UserController@associatedWithGlobalActions')->name('user.associatedWithGlobalActions')->middleware('auth:api');
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
        Route::post('/clients', 'AccountController@clients')->name('account.clients')->middleware('auth:api');
        Route::post('/workspaces', 'AccountController@workspaces')->name('account.workspaces')->middleware('auth:api');
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
Route::prefix('client')
    ->name('api.')
    ->namespace('App\Http\Controllers')
    ->group(function () {
        Route::post('/show', 'ClientController@show')->name('client.show')->middleware('auth:api');
        Route::post('/files', 'ClientController@files')->name('client.files')->middleware('auth:api');
        Route::post('/upsert', 'ClientController@upsert')->name('client.upsert')->middleware('auth:api');
        Route::post('/delete', 'ClientController@delete')->name('client.delete')->middleware('auth:api');
        Route::post('/profiles', 'ClientController@profiles')->name('client.profiles')->middleware('auth:api');
        Route::post('/signages', 'ClientController@signages')->name('client.signages')->middleware('auth:api');
        Route::post('/local_users', 'ClientController@localUsers')->name('client.localUsers')->middleware('auth:api');
        Route::post('/associated-users', 'ClientController@associatedUsers')->name('client.associatedUsers')->middleware('auth:api');
        Route::post('/associated-workspaces', 'ClientController@associatedWorkspaces')->name('client.associatedWorkspaces')->middleware('auth:api');
    });
Route::prefix('powerbi')
    ->name('api.')
    ->namespace('App\Http\Controllers')
    ->group(function () {
        Route::post('/sync', 'PowerBIController@sync')->name('powerbi.sync')->middleware('auth:api');
        Route::post('/workspace/show', 'PowerBIController@workspaceShow')->name('powerbi.workspaceShow')->middleware('auth:api');
        Route::post('/workspace/upsert', 'PowerBIController@workspaceUpsert')->name('powerbi.workspaceUpsert')->middleware('auth:api');
        Route::post('/workspace/delete', 'PowerBIController@workspaceDelete')->name('powerbi.workspaceDelete')->middleware('auth:api');
        Route::post('/workspace/associated-clients', 'PowerBIController@workspaceAssociatedClients')->name('powerbi.workspaceAssociatedClients')->middleware('auth:api');
        Route::post('/bis/list', 'PowerBIController@bisList')->name('powerbi.bisList')->middleware('auth:api');
        Route::post('/bis/show', 'PowerBIController@bisShow')->name('powerbi.bisShow')->middleware('auth:api');
        Route::post('/bis/page', 'PowerBIController@bisPage')->name('powerbi.bisPage')->middleware('auth:api');
        Route::post('/bis/pages', 'PowerBIController@bisPages')->name('powerbi.bisPages')->middleware('auth:api');
        Route::post('/bis/render', 'PowerBIController@bisRender')->name('powerbi.bisRender')->middleware('auth:api');
        Route::post('/bis/upsert', 'PowerBIController@bisUpsert')->name('powerbi.bisUpsert')->middleware('auth:api');
        Route::post('/bis/delete', 'PowerBIController@bisDelete')->name('powerbi.bisDelete')->middleware('auth:api');
        Route::post('/bis/bookmark', 'PowerBIController@bisBookmark')->name('powerbi.bisBookmark')->middleware('auth:api');
        Route::post('/bis/create-image', 'PowerBIController@bisCreateImage')->name('powerbi.bisCreateImage')->middleware('auth:api');
        Route::post('/bis/destroy-image', 'PowerBIController@bisDestroyImage')->name('powerbi.bisDestroyImage')->middleware('auth:api');
        Route::post('/bis/associated-profiles', 'PowerBIController@bisAssociatedProfiles')->name('powerbi.bisAssociatedProfiles')->middleware('auth:api');
    });

Route::prefix('profile')
    ->name('api.')
    ->namespace('App\Http\Controllers')
    ->group(function () {
        Route::post('/show', 'ProfileController@show')->name('profile.show')->middleware('auth:api');
        Route::post('/upsert', 'ProfileController@upsert')->name('profile.upsert')->middleware('auth:api');
        Route::post('/delete', 'ProfileController@delete')->name('profile.delete')->middleware('auth:api');
        Route::post('/associated_users', 'ProfileController@associatedUsers')->name('profile.associatedUsers')->middleware('auth:api');
        Route::post('/associated_objects', 'ProfileController@associatedObjects')->name('profile.associatedActions')->middleware('auth:api');
    });
Route::prefix('repository')
    ->name('api.')
    ->namespace('App\Http\Controllers')
    ->group(function () {
        Route::post('/view', 'RepositoryController@view')->name('repository.view')->middleware('auth:api');
        Route::post('/upload', 'RepositoryController@upload')->name('repository.upload')->middleware('auth:api');
        Route::post('/rename', 'RepositoryController@rename')->name('repository.rename')->middleware('auth:api');
        Route::post('/destroy', 'RepositoryController@destroy')->name('repository.destroy')->middleware('auth:api');
    });
Route::prefix('signage')
    ->name('api.')
    ->namespace('App\Http\Controllers')
    ->group(function () {
        Route::post('/show', 'SignageController@show')->name('signage.show')->middleware('auth:api');
        Route::post('/slides', 'SignageController@slides')->name('signage.slides')->middleware('auth:api');
        Route::post('/upsert', 'SignageController@upsert')->name('signage.upsert')->middleware('auth:api');
        Route::post('/delete', 'SignageController@delete')->name('signage.delete')->middleware('auth:api');
        Route::post('/delete-slide', 'SignageController@deleteSlide')->name('signage.deleteSlide')->middleware('auth:api');
        Route::post('/move-slide-up', 'SignageController@moveSlideUp')->name('signage.moveSlideUp')->middleware('auth:api');
        Route::post('/set-slide-time', 'SignageController@setSlideTime')->name('signage.setSlideTime')->middleware('auth:api');
        Route::post('/move-slide-down', 'SignageController@moveSlideDown')->name('signage.moveSlideDown')->middleware('auth:api');
        Route::post('/add-to-broadcast', 'SignageController@addToBroadcast')->name('signage.addToBroadcast')->middleware('auth:api');
    });
