<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PlacesController extends Controller
{
    /**
     * Tìm kiếm địa điểm sử dụng Google Places API
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'query' => 'required|string|min:3|max:255',
        ]);

        $query = $request->input('query');
        
        try {
            // Trong thực tế, bạn cần có Google API key
            // $apiKey = config('services.google.places_api_key');
            
            // Tạm thời sử dụng mock data để demo
            $mockResults = $this->getMockPlacesResults($query);
            
            return response()->json([
                'success' => true,
                'results' => $mockResults,
                'message' => 'Tìm kiếm thành công'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Lỗi tìm kiếm địa điểm: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi tìm kiếm địa điểm',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lấy chi tiết địa điểm theo place_id
     */
    public function details(Request $request): JsonResponse
    {
        $request->validate([
            'place_id' => 'required|string',
        ]);

        $placeId = $request->input('place_id');
        
        try {
            // Trong thực tế, bạn sẽ gọi Google Places API
            $mockDetails = $this->getMockPlaceDetails($placeId);
            
            return response()->json([
                'success' => true,
                'result' => $mockDetails,
                'message' => 'Lấy thông tin địa điểm thành công'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Lỗi lấy chi tiết địa điểm: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi lấy thông tin địa điểm',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mock data cho kết quả tìm kiếm
     */
    private function getMockPlacesResults(string $query): array
    {
        // Tạo mock data dựa trên query
        $baseResults = [
            [
                'place_id' => 'ChIJN1t_tDeuEmsRUsoyG83frY4',
                'name' => 'Sân cầu lông ABC',
                'formatted_address' => '123 Đường ABC, Quận 1, TP.HCM',
                'geometry' => [
                    'location' => [
                        'lat' => 10.762622,
                        'lng' => 106.660172
                    ]
                ],
                'types' => ['establishment', 'point_of_interest']
            ],
            [
                'place_id' => 'ChIJN1t_tDeuEmsRUsoyG83frY5',
                'name' => 'Trung tâm thể thao XYZ',
                'formatted_address' => '456 Đường XYZ, Quận 2, TP.HCM',
                'geometry' => [
                    'location' => [
                        'lat' => 10.7879,
                        'lng' => 106.7498
                    ]
                ],
                'types' => ['establishment', 'point_of_interest']
            ],
            [
                'place_id' => 'ChIJN1t_tDeuEmsRUsoyG83frY6',
                'name' => 'Câu lạc bộ thể thao DEF',
                'formatted_address' => '789 Đường DEF, Quận 3, TP.HCM',
                'geometry' => [
                    'location' => [
                        'lat' => 10.7829,
                        'lng' => 106.7004
                    ]
                ],
                'types' => ['establishment', 'point_of_interest']
            ]
        ];

        // Lọc kết quả dựa trên query
        $filteredResults = array_filter($baseResults, function($place) use ($query) {
            return stripos($place['name'], $query) !== false || 
                   stripos($place['formatted_address'], $query) !== false;
        });

        return array_values($filteredResults);
    }

    /**
     * Mock data cho chi tiết địa điểm
     */
    private function getMockPlaceDetails(string $placeId): array
    {
        $mockDetails = [
            'ChIJN1t_tDeuEmsRUsoyG83frY4' => [
                'place_id' => 'ChIJN1t_tDeuEmsRUsoyG83frY4',
                'name' => 'Sân cầu lông ABC',
                'formatted_address' => '123 Đường ABC, Quận 1, TP.HCM',
                'formatted_phone_number' => '+84 28 1234 5678',
                'website' => 'https://example.com',
                'geometry' => [
                    'location' => [
                        'lat' => 10.762622,
                        'lng' => 106.660172
                    ]
                ],
                'opening_hours' => [
                    'open_now' => true,
                    'weekday_text' => [
                        'Thứ 2: 6:00 AM – 10:00 PM',
                        'Thứ 3: 6:00 AM – 10:00 PM',
                        'Thứ 4: 6:00 AM – 10:00 PM',
                        'Thứ 5: 6:00 AM – 10:00 PM',
                        'Thứ 6: 6:00 AM – 10:00 PM',
                        'Thứ 7: 6:00 AM – 10:00 PM',
                        'Chủ nhật: 6:00 AM – 10:00 PM'
                    ]
                ]
            ]
        ];

        return $mockDetails[$placeId] ?? [
            'place_id' => $placeId,
            'name' => 'Địa điểm không xác định',
            'formatted_address' => 'Không có thông tin',
            'geometry' => [
                'location' => [
                    'lat' => 0,
                    'lng' => 0
                ]
            ]
        ];
    }
}
