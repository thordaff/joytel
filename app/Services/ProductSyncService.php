<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ProductSyncService extends JoytelService
{
    /**
     * Get products from JoyTel API with local fallback
     */
    public function getProducts(string $systemType = 'warehouse', ?string $category = null): array
    {
        $cacheKey = "joytel_products_{$systemType}_{$category}";
        
        // Try cache first (1 hour cache)
        $cachedProducts = Cache::get($cacheKey);
        if ($cachedProducts) {
            return $cachedProducts;
        }

        // Try to fetch from JoyTel API
        try {
            $apiProducts = $this->fetchProductsFromApi($systemType);
            
            if (!empty($apiProducts)) {
                // Cache the results
                Cache::put($cacheKey, $apiProducts, 3600); // 1 hour
                
                // Sync with local database in background
                $this->syncProductsToDatabase($apiProducts, $systemType);
                
                return $this->filterProducts($apiProducts, $category);
            }
        } catch (\Exception $e) {
            Log::warning('Failed to fetch products from JoyTel API, using local fallback', [
                'error' => $e->getMessage(),
                'system_type' => $systemType
            ]);
        }

        // Fallback to local database
        return $this->getLocalProducts($systemType, $category);
    }

    /**
     * Fetch products from JoyTel API (hypothetical endpoints)
     */
    private function fetchProductsFromApi(string $systemType): array
    {
        if ($systemType === 'warehouse') {
            return $this->fetchWarehouseProducts();
        } elseif ($systemType === 'rsp') {
            return $this->fetchRSPProducts();
        }

        return [];
    }

    /**
     * Fetch Warehouse system products
     */
    private function fetchWarehouseProducts(): array
    {
        // Hypothetical endpoint - in real implementation this would exist
        // For now, we'll simulate what the response might look like
        
        // Example API call (if endpoint existed):
        // $response = $this->makeRequest('GET', '/customerApi/products');
        
        // Simulated response based on typical JoyTel structure
        return [
            'esim' => [
                [
                    'product_code' => 'ESIM_GLOBAL_1GB',
                    'name' => 'eSIM Global 1GB',
                    'category' => 'esim',
                    'region' => 'global',
                    'data_amount_mb' => 1024,
                    'price' => 15.00,
                    'description' => 'Global eSIM with 1GB data allowance',
                    'validity_days' => 30,
                    'countries' => 'Worldwide coverage'
                ],
                [
                    'product_code' => 'ESIM_GLOBAL_3GB',
                    'name' => 'eSIM Global 3GB', 
                    'category' => 'esim',
                    'region' => 'global',
                    'data_amount_mb' => 3072,
                    'price' => 35.00,
                    'description' => 'Global eSIM with 3GB data allowance',
                    'validity_days' => 30,
                    'countries' => 'Worldwide coverage'
                ],
                // ... other products would come from actual API
            ],
            'recharge' => [
                [
                    'product_code' => 'RECHARGE_1GB',
                    'name' => 'Data Recharge 1GB',
                    'category' => 'recharge', 
                    'data_amount_mb' => 1024,
                    'price' => 12.00,
                    'description' => '1GB data recharge for existing SIM/eSIM'
                ],
                // ... other recharge products
            ]
        ];
    }

    /**
     * Fetch RSP system products  
     */
    private function fetchRSPProducts(): array
    {
        // In real implementation, might call coupon/query to get available coupons
        // or a dedicated products endpoint
        
        try {
            // Example: Get available coupons which represent products
            // $response = $this->makeRequest('POST', '/openapi/coupon/query', [
            //     'status' => 'available'
            // ]);
            
            // For now return simulated data
            return [
                'esim' => [
                    [
                        'product_code' => 'RSP_ESIM_START',
                        'name' => 'RSP eSIM Starter',
                        'category' => 'esim',
                        'region' => 'global',
                        'data_amount_mb' => 1024,
                        'price' => 18.00,
                        'description' => 'RSP system eSIM with starter package'
                    ]
                ]
            ];
        } catch (\Exception $e) {
            Log::error('Failed to fetch RSP products', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Sync API products to local database
     */
    private function syncProductsToDatabase(array $products, string $systemType): void
    {
        try {
            foreach ($products as $category => $categoryProducts) {
                foreach ($categoryProducts as $productData) {
                    Product::updateOrCreate(
                        [
                            'product_code' => $productData['product_code'],
                            'system_type' => $systemType
                        ],
                        [
                            'name' => $productData['name'],
                            'category' => $productData['category'],
                            'region' => $productData['region'] ?? null,
                            'data_amount_mb' => $productData['data_amount_mb'],
                            'price' => $productData['price'],
                            'description' => $productData['description'],
                            'is_active' => true,
                            'metadata' => [
                                'validity_days' => $productData['validity_days'] ?? null,
                                'countries' => $productData['countries'] ?? null,
                                'last_synced' => now()->toISOString()
                            ]
                        ]
                    );
                }
            }

            Log::info('Products synced successfully', [
                'system_type' => $systemType,
                'count' => collect($products)->flatten(1)->count()
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to sync products to database', [
                'error' => $e->getMessage(),
                'system_type' => $systemType
            ]);
        }
    }

    /**
     * Get products from local database (fallback)
     */
    private function getLocalProducts(string $systemType, ?string $category): array
    {
        $query = Product::active()->forSystem($systemType);
        
        if ($category) {
            $query->byCategory($category);
        }
        
        $products = $query->orderBy('region')
            ->orderBy('data_amount_mb')
            ->get();

        return $products->groupBy('category')->map(function ($categoryProducts) {
            return $categoryProducts->map(function ($product) {
                return [
                    'product_code' => $product->product_code,
                    'name' => $product->display_name,
                    'category' => $product->category,
                    'region' => $product->region,
                    'data_amount_mb' => $product->data_amount_mb,
                    'price' => (float) $product->price,
                    'description' => $product->description
                ];
            })->toArray();
        })->toArray();
    }

    /**
     * Filter products by category
     */
    private function filterProducts(array $products, ?string $category): array
    {
        if (!$category) {
            return $products;
        }

        return array_filter($products, function($key) use ($category) {
            return $key === $category;
        }, ARRAY_FILTER_USE_KEY);
    }

    /**
     * Force refresh products from API
     */
    public function refreshProducts(?string $systemType = null): void
    {
        $systems = $systemType ? [$systemType] : ['warehouse', 'rsp'];
        
        foreach ($systems as $system) {
            // Clear cache
            Cache::forget("joytel_products_{$system}_");
            Cache::forget("joytel_products_{$system}_esim");
            Cache::forget("joytel_products_{$system}_recharge");
            
            // Fetch fresh data
            $this->getProducts($system);
        }
    }
}