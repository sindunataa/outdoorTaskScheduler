<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Services\WeatherService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class ActivityController extends Controller
{
    private $apiBaseUrl = 'https://wilayah.id/api';
    private $weatherService;

    public function __construct(WeatherService $weatherService)
    {
        $this->weatherService = $weatherService;
    }

    public function index(): JsonResponse
    {
        $activities = Activity::where('user_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json($activities);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'location' => 'required|string|max:255',
            'subdistrict' => 'required|string|max:255',
            'preferred_date' => 'required|date|after_or_equal:today'
        ]);

        $weatherData = $this->weatherService->getWeatherForecast($request->location, $request->subdistrict);
        
        $suggestedSlots = $this->weatherService->getSuggestedTimeSlots(
            $weatherData, 
            $request->preferred_date
        );

        $activity = Activity::create([
            'user_id' => auth()->id(),
            'name' => $request->name,
            'location' => $request->location,
            'subdistrict' => $request->subdistrict,
            'preferred_date' => $request->preferred_date,
            'suggested_slots' => $suggestedSlots,
            'weather_data' => $weatherData
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Activity created successfully',
            'activity' => $activity,
            'suggested_slots' => $suggestedSlots,
            'weather_data' => $weatherData,
            'location_info' => [
                'location' => $request->location,
                'subdistrict' => $request->subdistrict,
                'has_suggestions' => count($suggestedSlots) > 0
            ]
        ], 201);
    }

    public function show(Activity $activity): JsonResponse
    {
        // $this->authorize('view', $activity);
        
        return response()->json([
            'success' => true,
            'activity' => $activity
        ]);
    }

    public function update(Request $request, Activity $activity): JsonResponse
    {
        // $this->authorize('update', $activity);

        $request->validate([
            'selected_slot' => 'sometimes|string',
            'status' => 'sometimes|in:pending,scheduled,completed,cancelled',
            'name' => 'sometimes|string|max:255',
            'location' => 'sometimes|string|max:255',
            'subdistrict' => 'sometimes|string|max:255',
            'preferred_date' => 'sometimes|date|after_or_equal:today'
        ]);

        if ($request->has(['location', 'subdistrict', 'preferred_date'])) {
            $location = $request->input('location', $activity->location);
            $subdistrict = $request->input('subdistrict', $activity->subdistrict);
            $preferredDate = $request->input('preferred_date', $activity->preferred_date);

            $weatherData = $this->weatherService->getWeatherForecast($location, $subdistrict);
            $suggestedSlots = $this->weatherService->getSuggestedTimeSlots($weatherData, $preferredDate);

            $request->merge([
                'weather_data' => $weatherData,
                'suggested_slots' => $suggestedSlots
            ]);
        }

        $activity->update($request->only([
            'name', 'location', 'subdistrict', 'preferred_date', 
            'selected_slot', 'status', 'weather_data', 'suggested_slots'
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Activity updated successfully',
            'activity' => $activity->fresh()
        ]);
    }

    public function destroy(Activity $activity): JsonResponse
    {
        // $this->authorize('delete', $activity);
        
        $activity->delete();

        return response()->json([
            'success' => true,
            'message' => 'Activity deleted successfully'
        ]);
    }

    public function getWeatherForecast(Request $request): JsonResponse
    {
        $request->validate([
            'location' => 'required|string',
            'subdistrict' => 'nullable|string'
        ]);

        $weatherData = $this->weatherService->getWeatherForecast(
            $request->location, 
            $request->subdistrict
        );

        return response()->json([
            'success' => true,
            'weather_data' => $weatherData,
            'location' => $request->location,
            'subdistrict' => $request->subdistrict,
            'forecast_count' => count($weatherData)
        ]);
    }

    public function getSuggestions(Request $request): JsonResponse
    {
        $request->validate([
            'location' => 'required|string',
            'subdistrict' => 'nullable|string',
            'preferred_date' => 'required|date|after_or_equal:today'
        ]);

        $weatherData = $this->weatherService->getWeatherForecast(
            $request->location, 
            $request->subdistrict
        );

        $suggestions = $this->weatherService->getSuggestedTimeSlots(
            $weatherData, 
            $request->preferred_date
        );

        return response()->json([
            'success' => true,
            'suggestions' => $suggestions,
            'weather_data' => $weatherData,
            'location_info' => [
                'location' => $request->location,
                'subdistrict' => $request->subdistrict,
                'preferred_date' => $request->preferred_date,
                'suggestion_count' => count($suggestions)
            ]
        ]);
    }

    
    public function getLocationCodes(Request $request): JsonResponse
    {
        $level = $request->get('level', 'regency');
        $parent_code = $request->get('parent_code', '');
        
        try {
            // Cache key based on parameters
            $cacheKey = "locations_{$level}_{$parent_code}";
            
            // Check cache first (cache for 6 hours)
            $locations = Cache::remember($cacheKey, 21600, function() use ($level, $parent_code) {
                return $this->fetchFromWilayahAPI($level, $parent_code);
            });

            return response()->json([
                'success' => true,
                'data' => $locations,
                'level' => $level
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch location data: ' . $e->getMessage()
            ], 500);
        }
    }

    private function fetchFromWilayahAPI($level = 'regency', $parent_code = '')
    {
        $url = '';
        
        switch ($level) {
            case 'province':
                $url = $this->apiBaseUrl . '/provinces.json';
                break;
            case 'regency':
                if ($parent_code) {
                    $url = $this->apiBaseUrl . "/regencies/{$parent_code}.json";
                } else {
                    throw new \Exception('Parent code required for districts');
                }
                break;
            case 'district':
                if ($parent_code) {
                    $url = $this->apiBaseUrl . "/districts/{$parent_code}.json";
                } else {
                    throw new \Exception('Parent code required for districts');
                }
                break;
            case 'village':
                if ($parent_code) {
                    $url = $this->apiBaseUrl . "/villages/{$parent_code}.json";
                } else {
                    throw new \Exception('Parent code required for villages');
                }
                break;
        }

        if (!$url) {
            throw new \Exception('Invalid level specified');
        }

        $response = Http::timeout(15)->get($url);
        
        if (!$response->successful()) {
            throw new \Exception('API request failed with status: ' . $response->status());
        }

        $responseData = $response->json();
        $data = $responseData['data'] ?? [];
        
        // Transform the data to match our frontend needs
        $locations = [];
        if (is_array($data)) {
            foreach ($data as $item) {
                $locations[] = [
                    'code' => $item['code'],
                    'name' => $item['name'],
                    'full_name' => $item['name'],
                    'level' => $level,
                    'parent_code' => $parent_code
                ];
            }
        }

        return $locations;
    }

    private function getAllRegencies()
    {
        // First get all provinces
        $provinces = $this->fetchFromWilayahAPI('province');
        $allRegencies = [];

        // Limit to major provinces to avoid timeout
        $majorProvinces = array_slice($provinces, 0, 10);

        foreach ($majorProvinces as $province) {
            try {
                $regencies = $this->fetchFromWilayahAPI('regency', $province['code']);
                $allRegencies = array_merge($allRegencies, $regencies);
            } catch (\Exception $e) {
                // Continue if one province fails
                \Log::warning("Failed to fetch regencies for province {$province['code']}: " . $e->getMessage());
                continue;
            }
        }

        return $allRegencies;
    }

    public function searchLocation(Request $request): JsonResponse
    {
        $query = $request->get('q', '');
        $type = $request->get('type', 'regency'); 
        
        if (strlen($query) < 2) {
            return response()->json([
                'success' => true,
                'data' => []
            ]);
        }

        try {
            $cacheKey = "search_{$type}_{$query}";
            
            $results = Cache::remember($cacheKey, 1800, function() use ($type, $query) {
                
                if ($type === 'regency') {
                    $allResults = [];
                    $provinces = $this->fetchFromWilayahAPI('province');
                    
                    $limitedProvinces = array_slice($provinces, 0, 8);
                    
                    foreach ($limitedProvinces as $province) {
                        try {
                            $regencies = $this->fetchFromWilayahAPI('regency', $province['code']);
                            $allResults = array_merge($allResults, $regencies);
                        } catch (\Exception $e) {
                            continue;
                        }
                    }
                    $locations = $allResults;
                } else {
                    $locations = $this->fetchFromWilayahAPI($type);
                }
                
                $filtered = array_filter($locations, function($location) use ($query) {
                    return stripos($location['name'], $query) !== false;
                });

                usort($filtered, function($a, $b) use ($query) {
                    $aExact = stripos($a['name'], $query) === 0 ? 1 : 0;
                    $bExact = stripos($b['name'], $query) === 0 ? 1 : 0;
                    return $bExact - $aExact;
                });

                return array_values(array_slice($filtered, 0, 20));
            });

            $results = array_map(function($item) use ($type) {
                $item['type'] = $type;
                return $item;
            }, $results);

            return response()->json([
                'success' => true,
                'data' => $results
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Search failed: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getRecentLocations(Request $request): JsonResponse
    {
        $user = $request->user();
        
        try {
            $recentLocations = \App\Models\Activity::where('user_id', $user->id)
                ->whereNotNull('location')
                ->whereNotNull('subdistrict')
                ->select('location', 'subdistrict', 'location_code', 'coordinates')
                ->distinct()
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get()
                ->map(function($activity) {
                    return [
                        'name' => $activity->subdistrict . ', ' . $activity->location,
                        'location' => $activity->location,
                        'subdistrict' => $activity->subdistrict,
                        'code' => $activity->location_code,
                        'coordinates' => $activity->coordinates,
                        'type' => 'recent'
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $recentLocations
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get recent locations: ' . $e->getMessage()
            ], 500);
        }
    }

    public function reverseGeocode(Request $request): JsonResponse
    {
        $request->validate([
            'lat' => 'required|numeric',
            'lng' => 'required|numeric'
        ]);

        $lat = $request->get('lat');
        $lng = $request->get('lng');

        try {
            $cacheKey = "geocode_" . round($lat, 4) . "_" . round($lng, 4);
            
            $locationData = Cache::remember($cacheKey, 7200, function() use ($lat, $lng) {

                $response = Http::withHeaders([
                    'User-Agent' => 'ActivityScheduler/1.0 (contact@example.com)'
                ])->timeout(10)->get('https://nominatim.openstreetmap.org/reverse', [
                    'format' => 'json',
                    'lat' => $lat,
                    'lon' => $lng,
                    'addressdetails' => 1,
                    'accept-language' => 'id,en',
                    'zoom' => 10
                ]);

                if (!$response->successful()) {
                    throw new \Exception('Geocoding service unavailable');
                }

                return $response->json();
            });
            
            if (!$locationData || !isset($locationData['address'])) {
                throw new \Exception('No address data found');
            }
            
            $address = $locationData['address'];
            
            $regency = $address['city'] ?? 
                      $address['town'] ?? 
                      $address['county'] ?? 
                      $address['state_district'] ?? '';
                      
            $subdistrict = $address['suburb'] ?? 
                          $address['village'] ?? 
                          $address['neighbourhood'] ?? 
                          $address['hamlet'] ?? 
                          $address['quarter'] ?? '';
                          
            $province = $address['state'] ?? '';
            
            $regency = trim(str_replace(['Kota ', 'Kabupaten ', 'Kab. ', 'Kota Administrasi '], '', $regency));
            $subdistrict = trim(str_replace(['Kecamatan ', 'Kec. '], '', $subdistrict));
            
            $locationCode = null;
            if ($regency) {
                try {
                    $provinces = $this->fetchFromWilayahAPI('province');
                    $provinceCode = null;
                    
                    foreach ($provinces as $prov) {
                        if (stripos($prov['name'], $province) !== false) {
                            $provinceCode = $prov['code'];
                            break;
                        }
                    }
                    
                    // If province found, search regencies
                    if ($provinceCode) {
                        $regencies = $this->fetchFromWilayahAPI('regency', $provinceCode);
                        foreach ($regencies as $reg) {
                            if (stripos($reg['name'], $regency) !== false || 
                                stripos($regency, $reg['name']) !== false) {
                                $locationCode = $reg['code'];
                                break;
                            }
                        }
                    }
                } catch (\Exception $e) {

                }
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'location' => $regency ?: 'Unknown Location',
                    'subdistrict' => $subdistrict ?: 'Unknown Subdistrict',
                    'province' => $province,
                    'location_code' => $locationCode,
                    'full_address' => $locationData['display_name'] ?? '',
                    'coordinates' => [
                        'lat' => (float)$lat,
                        'lng' => (float)$lng
                    ]
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Reverse geocoding failed: ' . $e->getMessage(),
                'data' => [
                    'location' => 'Unknown Location',
                    'subdistrict' => 'Unknown Subdistrict',
                    'province' => '',
                    'location_code' => null,
                    'full_address' => "Coordinates: {$lat}, {$lng}",
                    'coordinates' => [
                        'lat' => (float)$lat,
                        'lng' => (float)$lng
                    ]
                ]
            ], 200); 
        }
    }

    public function testWilayahAPI(): JsonResponse
    {
        try {
            $response = Http::timeout(10)->get($this->apiBaseUrl . '/provinces.json');
            
            if ($response->successful()) {
                $data = $response->json();
                return response()->json([
                    'success' => true,
                    'message' => 'API connection successful',
                    'sample_data' => array_slice($data['data'] ?? [], 0, 3)
                ]);
            } else {
                throw new \Exception('API returned status: ' . $response->status());
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'API test failed: ' . $e->getMessage()
            ], 500);
        }
    }
}