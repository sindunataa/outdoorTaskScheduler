<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class WeatherService
{
    private $client;
    private $baseUrl;

    public function __construct()
    {
        $this->client = new Client();
        $this->baseUrl = 'https://api.bmkg.go.id/publik/prakiraan-cuaca';
    }

    public function getWeatherForecast($location, $subdistrict = null)
    {
        try {
            // Get area code for the location
            $areaCode = $this->getAreaCode($location, $subdistrict);
            
            if (!$areaCode) {
                Log::warning("Area code not found for location: {$location}, {$subdistrict}");
                return $this->getMockWeatherData();
            }

            // Cache key to avoid hitting API too frequently
            $cacheKey = "weather_forecast_{$areaCode}";
            
            // Check cache first (cache for 30 minutes)
            $cachedData = Cache::get($cacheKey);
            if ($cachedData) {
                return $cachedData;
            }

            $url = $this->baseUrl . '?adm4=' . $areaCode;
            
            $response = $this->client->get($url, [
                'timeout' => 15,
                'headers' => [
                    'User-Agent' => 'Outdoor-Scheduler/1.0',
                    'Accept' => 'application/json'
                ]
            ]);

            $data = json_decode($response->getBody(), true);
            
            if (!$data || !isset($data['data']) || empty($data['data'])) {
                Log::warning("Empty response from BMKG API for area code: {$areaCode}");
                return $this->getMockWeatherData();
            }

            $forecasts = $this->parseWeatherData($data['data']);
            
            // Cache the result
            Cache::put($cacheKey, $forecasts, now()->addMinutes(30));
            
            return $forecasts;

        } catch (RequestException $e) {
            Log::error('BMKG API Request Error: ' . $e->getMessage());
            return $this->getMockWeatherData();
        } catch (\Exception $e) {
            Log::error('Weather Service Error: ' . $e->getMessage());
            return $this->getMockWeatherData();
        }
    }

    private function getAreaCode($location, $subdistrict = null)
    {
        // Sample area codes from kodewilayah.id (Kode Wilayah Tingkat IV)
        // In production, you should have a complete mapping or API to get these codes
        $areaCodes = [
            // Jakarta
            'jakarta' => [
                'menteng' => '3171040001',
                'tanah abang' => '3171030001', 
                'gambir' => '3171010001',
                'sawah besar' => '3171020001',
                'kemayoran' => '3171050001',
                'senen' => '3171060001',
                'cempaka putih' => '3171070001',
                'johar baru' => '3171080001',
                'default' => '3171040001' // Default to Menteng
            ],
            // Bandung
            'bandung' => [
                'coblong' => '3273040001',
                'bandung wetan' => '3273020001',
                'sumur bandung' => '3273010001',
                'andir' => '3273030001',
                'default' => '3273040001'
            ],
            // Surabaya  
            'surabaya' => [
                'genteng' => '3578010001',
                'bubutan' => '3578020001',
                'simokerto' => '3578030001',
                'default' => '3578010001'
            ],
            // Yogyakarta
            'yogyakarta' => [
                'gondokusuman' => '3471030001',
                'jetis' => '3471010001',
                'tegalrejo' => '3471020001',
                'default' => '3471030001'
            ],
            // Semarang
            'semarang' => [
                'semarang tengah' => '3374010001',
                'semarang utara' => '3374020001',
                'semarang timur' => '3374030001',
                'default' => '3374010001'
            ],
            // Bekasi
            'bekasi' => [
                'bekasi timur' => '3275010001',
                'bekasi selatan' => '3275020001',
                'bekasi barat' => '3275030001',
                'default' => '3275010001'
            ],
            // Tangerang
            'tangerang' => [
                'tangerang' => '3671010001',
                'batuceper' => '3671020001',
                'default' => '3671010001'
            ],
            // Medan
            'medan' => [
                'medan kota' => '1271010001',
                'medan baru' => '1271020001',
                'default' => '1271010001'
            ],
            // Makassar
            'makassar' => [
                'makassar' => '7371010001',
                'wajo' => '7371020001',
                'default' => '7371010001'
            ],
            // Palembang
            'palembang' => [
                'bukit kecil' => '1671010001',
                'gandus' => '1671020001',
                'default' => '1671010001'
            ]
        ];

        $location = strtolower(trim($location));
        $subdistrict = $subdistrict ? strtolower(trim($subdistrict)) : null;

        // Find location match
        foreach ($areaCodes as $city => $districts) {
            if (strpos($location, $city) !== false) {
                // If subdistrict is provided, try to find specific match
                if ($subdistrict) {
                    foreach ($districts as $district => $code) {
                        if ($district !== 'default' && strpos($subdistrict, $district) !== false) {
                            return $code;
                        }
                    }
                }
                // Return default code for the city
                return $districts['default'];
            }
        }

        // Default to Jakarta Menteng if no match found
        return $areaCodes['jakarta']['default'];
    }

    private function parseWeatherData($data)
    {
        $forecasts = [];
        $processedDates = [];

        foreach ($data as $item) {
            $localDateTime = Carbon::parse($item['local_datetime']);
            $dateKey = $localDateTime->format('Y-m-d');
            
            // Only process each date once (take the first forecast of each day)
            if (in_array($dateKey, $processedDates)) {
                continue;
            }
            
            // Only get 3 days forecast
            if (count($processedDates) >= 3) {
                break;
            }

            $weatherCondition = $this->mapWeatherCondition($item['weather_desc_en'] ?? $item['weather_desc']);
            
            $forecasts[] = [
                'date' => $dateKey,
                'datetime' => $localDateTime->format('Y-m-d H:i:s'),
                'weather' => $weatherCondition,
                'weather_desc' => $item['weather_desc'] ?? 'Tidak diketahui',
                'weather_desc_en' => $item['weather_desc_en'] ?? 'Unknown',
                'temperature' => (float) $item['t'],
                'humidity' => (float) $item['hu'],
                'wind_speed' => (float) $item['ws'],
                'wind_direction' => $item['wd'] ?? '',
                'cloud_cover' => (float) ($item['tcc'] ?? 0),
                'visibility' => $item['vs_text'] ?? '',
                'is_suitable' => $this->isWeatherSuitable($weatherCondition, (float) $item['t'], (float) $item['ws'])
            ];

            $processedDates[] = $dateKey;
        }

        // If we don't have enough data, fill with mock data
        while (count($forecasts) < 3) {
            $date = Carbon::now()->addDays(count($forecasts));
            $forecasts[] = $this->generateMockDayData($date->format('Y-m-d'));
        }

        return $forecasts;
    }

    private function mapWeatherCondition($weatherDesc)
    {
        $weatherDesc = strtolower($weatherDesc);
        
        // Map BMKG weather descriptions to our internal format
        if (strpos($weatherDesc, 'cerah') !== false || strpos($weatherDesc, 'clear') !== false) {
            return 'sunny';
        } elseif (strpos($weatherDesc, 'berawan') !== false || strpos($weatherDesc, 'cloudy') !== false) {
            if (strpos($weatherDesc, 'sebagian') !== false || strpos($weatherDesc, 'partly') !== false) {
                return 'partly_cloudy';
            }
            return 'cloudy';
        } elseif (strpos($weatherDesc, 'hujan') !== false || strpos($weatherDesc, 'rain') !== false) {
            if (strpos($weatherDesc, 'petir') !== false || strpos($weatherDesc, 'thunder') !== false) {
                return 'thunderstorm';
            }
            return 'rainy';
        } elseif (strpos($weatherDesc, 'badai') !== false || strpos($weatherDesc, 'storm') !== false) {
            return 'thunderstorm';
        } else {
            return 'cloudy'; // Default
        }
    }

    private function isWeatherSuitable($weather, $temperature, $windSpeed)
    {
        $suitableWeathers = ['sunny', 'cloudy', 'partly_cloudy'];
        
        return in_array($weather, $suitableWeathers) 
            && $temperature >= 20 
            && $temperature <= 35 
            && $windSpeed <= 25;
    }

    private function getMockWeatherData()
    {
        $forecasts = [];
        
        for ($i = 0; $i < 3; $i++) {
            $date = Carbon::now()->addDays($i);
            $forecasts[] = $this->generateMockDayData($date->format('Y-m-d'));
        }

        return $forecasts;
    }

    private function generateMockDayData($date)
    {
        $weathers = ['sunny', 'cloudy', 'partly_cloudy', 'rainy', 'thunderstorm'];
        $weather = $weathers[array_rand($weathers)];
        $temperature = rand(24, 32);
        $windSpeed = rand(5, 15);
        
        $weatherDescriptions = [
            'sunny' => ['Cerah', 'Clear Sky'],
            'cloudy' => ['Berawan', 'Cloudy'],
            'partly_cloudy' => ['Cerah Berawan', 'Partly Cloudy'],
            'rainy' => ['Hujan Ringan', 'Light Rain'],
            'thunderstorm' => ['Hujan Petir', 'Thunderstorm']
        ];
        
        return [
            'date' => $date,
            'datetime' => $date . ' 12:00:00',
            'weather' => $weather,
            'weather_desc' => $weatherDescriptions[$weather][0],
            'weather_desc_en' => $weatherDescriptions[$weather][1],
            'temperature' => $temperature,
            'humidity' => rand(60, 85),
            'wind_speed' => $windSpeed,
            'wind_direction' => ['N', 'NE', 'E', 'SE', 'S', 'SW', 'W', 'NW'][array_rand(['N', 'NE', 'E', 'SE', 'S', 'SW', 'W', 'NW'])],
            'cloud_cover' => rand(10, 90),
            'visibility' => rand(5, 15) . ' km',
            'is_suitable' => $this->isWeatherSuitable($weather, $temperature, $windSpeed)
        ];
    }

    public function getSuggestedTimeSlots($weatherData, $preferredDate)
    {
        $suggestions = [];
        $preferredDateTime = Carbon::parse($preferredDate);
        
        foreach ($weatherData as $forecast) {
            $forecastDate = Carbon::parse($forecast['date']);
            
            // Include preferred date and 2 days after
            if ($forecastDate->diffInDays($preferredDateTime, false) >= -2 && 
                $forecastDate->diffInDays($preferredDateTime, false) <= 2) {
                
                $timeSlots = [
                    ['time' => '06:00-08:00', 'period' => 'early_morning', 'hour' => 6],
                    ['time' => '08:00-10:00', 'period' => 'morning', 'hour' => 8],
                    ['time' => '10:00-12:00', 'period' => 'late_morning', 'hour' => 10],
                    ['time' => '14:00-16:00', 'period' => 'afternoon', 'hour' => 14],
                    ['time' => '16:00-18:00', 'period' => 'late_afternoon', 'hour' => 16]
                ];

                foreach ($timeSlots as $slot) {
                    // Skip past time slots
                    $slotDateTime = $forecastDate->copy()->setHour($slot['hour']);
                    if ($slotDateTime->isPast()) {
                        continue;
                    }

                    $suitabilityScore = $this->calculateSuitability($forecast, $slot);
                    
                    // Only suggest if weather is somewhat suitable
                    if ($suitabilityScore >= 5) {
                        $suggestions[] = [
                            'date' => $forecast['date'],
                            'time' => $slot['time'],
                            'period' => $slot['period'],
                            'weather' => $forecast['weather'],
                            'weather_desc' => $forecast['weather_desc'],
                            'temperature' => $forecast['temperature'],
                            'humidity' => $forecast['humidity'],
                            'wind_speed' => $forecast['wind_speed'],
                            'suitability_score' => $suitabilityScore,
                            'recommendation' => $this->getRecommendation($suitabilityScore)
                        ];
                    }
                }
            }
        }

        // Sort by suitability score (descending)
        usort($suggestions, function($a, $b) {
            return $b['suitability_score'] <=> $a['suitability_score'];
        });

        return array_slice($suggestions, 0, 8); // Return top 8 suggestions
    }

    private function calculateSuitability($forecast, $slot)
    {
        $score = 0;
        
        // Base weather score
        switch ($forecast['weather']) {
            case 'sunny':
                $score += 15;
                break;
            case 'partly_cloudy':
                $score += 12;
                break;
            case 'cloudy':
                $score += 8;
                break;
            case 'rainy':
                $score += 2;
                break;
            case 'thunderstorm':
                $score += 0;
                break;
        }

        // Temperature score (optimal range 24-30°C)
        $temp = $forecast['temperature'];
        if ($temp >= 24 && $temp <= 30) {
            $score += 8;
        } elseif ($temp >= 20 && $temp <= 34) {
            $score += 5;
        } elseif ($temp >= 18 && $temp <= 36) {
            $score += 2;
        }

        // Wind speed score (lower is better)
        $windSpeed = $forecast['wind_speed'];
        if ($windSpeed <= 10) {
            $score += 5;
        } elseif ($windSpeed <= 15) {
            $score += 3;
        } elseif ($windSpeed <= 20) {
            $score += 1;
        }

        // Humidity score (comfortable range 40-70%)
        $humidity = $forecast['humidity'];
        if ($humidity >= 40 && $humidity <= 70) {
            $score += 3;
        } elseif ($humidity >= 30 && $humidity <= 80) {
            $score += 1;
        }

        // Time period adjustments
        switch ($slot['period']) {
            case 'early_morning':
                $score += 2; // Good for avoiding heat
                break;
            case 'morning':
                $score += 5; // Best time
                break;
            case 'late_morning':
                $score += 3;
                break;
            case 'afternoon':
                $score -= 1; // Can be hot
                break;
            case 'late_afternoon':
                $score += 2; // Cooler
                break;
        }

        return max(0, $score);
    }

    private function getRecommendation($score)
    {
        if ($score >= 25) {
            return 'Excellent - Perfect conditions for outdoor activities';
        } elseif ($score >= 20) {
            return 'Very Good - Great weather for outdoor work';
        } elseif ($score >= 15) {
            return 'Good - Suitable for outdoor activities';
        } elseif ($score >= 10) {
            return 'Fair - Acceptable conditions with precautions';
        } elseif ($score >= 5) {
            return 'Poor - Consider postponing if possible';
        } else {
            return 'Not Recommended - Unsuitable weather conditions';
        }
    }

    public function getDetailedForecast($location, $subdistrict = null)
    {
        // Get detailed hourly forecast for more precise planning
        return $this->getWeatherForecast($location, $subdistrict);
    }
}