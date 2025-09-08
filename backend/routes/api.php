<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ActivityController;
use Illuminate\Support\Facades\Route;

// Auth routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    
    // Activity routes
    Route::apiResource('activities', ActivityController::class);
    
    // Weather and suggestion routes
    Route::post('/weather-forecast', [ActivityController::class, 'getWeatherForecast']);
    Route::post('/get-suggestions', [ActivityController::class, 'getSuggestions']);
    // Route::get('/location-codes', [ActivityController::class, 'getLocationCodes']);

    //     // Location routes
 // Enhanced location routes
    Route::get('/location-codes', [ActivityController::class, 'getLocationCodes']);
    Route::get('/search-location', [ActivityController::class, 'searchLocation']);
    Route::get('/recent-locations', [ActivityController::class, 'getRecentLocations']);
    Route::post('/reverse-geocode', [ActivityController::class, 'reverseGeocode']);
    
    // New hierarchical location routes
    Route::get('/location-hierarchy', [ActivityController::class, 'getLocationHierarchy']);
    Route::get('/location-suggestions', [ActivityController::class, 'getLocationSuggestions']);
    Route::get('/provinces', [ActivityController::class, 'getProvinces']);
    Route::get('/regencies/{provinceCode}', [ActivityController::class, 'getRegencies']);
    Route::get('/districts/{regencyCode}', [ActivityController::class, 'getDistricts']);
    Route::get('/villages/{districtCode}', [ActivityController::class, 'getVillages']);
    
    Route::get('/location-breadcrumb/{code}/{level}', [ActivityController::class, 'getLocationBreadcrumb']);
    
    // Testing and debugging routes (remove in production)
    Route::get('/test-wilayah-api', [ActivityController::class, 'testWilayahAPI']);
});