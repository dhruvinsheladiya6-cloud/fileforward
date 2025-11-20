<?php

use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function () {
    Route::prefix('install')->name('install.')->middleware('installed')->group(function () {
        Route::get('/', 'InstallController@redirect')->name('index');
        Route::get('requirements', 'InstallController@requirements');
        Route::post('requirements', 'InstallController@requirementsAction')->name('requirements');
        Route::get('permissions', 'InstallController@permissions');
        Route::post('permissions', 'InstallController@permissionsAction')->name('permissions');
        Route::get('licence', 'InstallController@licence');
        Route::post('licence', 'InstallController@licenceAction')->name('licence');
        Route::get('information', 'InstallController@redirectToDatabase');
        Route::get('information/database', 'InstallController@database');
        Route::post('information/database', 'InstallController@databaseAction')->name('information.database');
        Route::get('information/database/import', 'InstallController@databaseImport');
        Route::post('information/database/import', 'InstallController@databaseImportAction')->name('information.databaseImport');
        Route::post('information/database/manual/download', 'InstallController@downloadSqlFile')->name('information.databaseImport.download.sql');
        Route::post('information/database/manual/skip', 'InstallController@databaseImportSkip')->name('information.databaseImport.skip');
        Route::get('information/building', 'InstallController@building');
        Route::post('information/building/back', 'InstallController@backToDatabaseImport')->name('information.building.back');
        Route::post('information/building', 'InstallController@buildingAction')->name('information.building');
    });
});
