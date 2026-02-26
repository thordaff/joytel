<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = [
            // eSIM Products - Warehouse System
            [
                'product_code' => 'ESIM_GLOBAL_1GB',
                'name' => 'eSIM Global',
                'category' => 'esim',
                'region' => 'global',
                'data_amount_mb' => 1024, // 1GB
                'price' => 15.00,
                'description' => 'Global eSIM with 1GB data allowance',
                'system_type' => 'warehouse',
                'is_active' => true,
                'metadata' => [
                    'countries' => 'Worldwide coverage',
                    'validity_days' => 30,
                    'speed' => '4G/5G'
                ]
            ],
            [
                'product_code' => 'ESIM_GLOBAL_3GB',
                'name' => 'eSIM Global',
                'category' => 'esim',
                'region' => 'global',
                'data_amount_mb' => 3072, // 3GB
                'price' => 35.00,
                'description' => 'Global eSIM with 3GB data allowance',
                'system_type' => 'warehouse',
                'is_active' => true,
                'metadata' => [
                    'countries' => 'Worldwide coverage',
                    'validity_days' => 30,
                    'speed' => '4G/5G'
                ]
            ],
            [
                'product_code' => 'ESIM_GLOBAL_5GB',
                'name' => 'eSIM Global',
                'category' => 'esim',
                'region' => 'global',
                'data_amount_mb' => 5120, // 5GB
                'price' => 50.00,
                'description' => 'Global eSIM with 5GB data allowance',
                'system_type' => 'warehouse',
                'is_active' => true,
                'metadata' => [
                    'countries' => 'Worldwide coverage',
                    'validity_days' => 30,
                    'speed' => '4G/5G'
                ]
            ],
            [
                'product_code' => 'ESIM_GLOBAL_10GB',
                'name' => 'eSIM Global',
                'category' => 'esim',
                'region' => 'global',
                'data_amount_mb' => 10240, // 10GB
                'price' => 85.00,
                'description' => 'Global eSIM with 10GB data allowance',
                'system_type' => 'warehouse',
                'is_active' => true,
                'metadata' => [
                    'countries' => 'Worldwide coverage',
                    'validity_days' => 30,
                    'speed' => '4G/5G'
                ]
            ],
            [
                'product_code' => 'ESIM_ASIA_2GB',
                'name' => 'eSIM Asia',
                'category' => 'esim',
                'region' => 'asia',
                'data_amount_mb' => 2048, // 2GB
                'price' => 25.00,
                'description' => 'Asia region eSIM with 2GB data allowance',
                'system_type' => 'warehouse',
                'is_active' => true,
                'metadata' => [
                    'countries' => 'Asia Pacific countries',
                    'validity_days' => 30,
                    'speed' => '4G/5G'
                ]
            ],
            [
                'product_code' => 'ESIM_EUROPE_5GB',
                'name' => 'eSIM Europe',
                'category' => 'esim',
                'region' => 'europe',
                'data_amount_mb' => 5120, // 5GB
                'price' => 45.00,
                'description' => 'Europe region eSIM with 5GB data allowance',
                'system_type' => 'warehouse',
                'is_active' => true,
                'metadata' => [
                    'countries' => 'European Union countries',
                    'validity_days' => 30,
                    'speed' => '4G/5G'
                ]
            ],

            // OTA Recharge Products - Warehouse System
            [
                'product_code' => 'RECHARGE_1GB',
                'name' => 'Data Recharge',
                'category' => 'recharge',
                'region' => null,
                'data_amount_mb' => 1024, // 1GB
                'price' => 12.00,
                'description' => '1GB data recharge for existing SIM/eSIM',
                'system_type' => 'warehouse',
                'is_active' => true,
                'metadata' => [
                    'recharge_type' => 'data_only',
                    'validity_days' => 30
                ]
            ],
            [
                'product_code' => 'RECHARGE_3GB',
                'name' => 'Data Recharge',
                'category' => 'recharge',
                'region' => null,
                'data_amount_mb' => 3072, // 3GB
                'price' => 30.00,
                'description' => '3GB data recharge for existing SIM/eSIM',
                'system_type' => 'warehouse',
                'is_active' => true,
                'metadata' => [
                    'recharge_type' => 'data_only',
                    'validity_days' => 30
                ]
            ],
            [
                'product_code' => 'RECHARGE_5GB',
                'name' => 'Data Recharge',
                'category' => 'recharge',
                'region' => null,
                'data_amount_mb' => 5120, // 5GB
                'price' => 45.00,
                'description' => '5GB data recharge for existing SIM/eSIM',
                'system_type' => 'warehouse',
                'is_active' => true,
                'metadata' => [
                    'recharge_type' => 'data_only',
                    'validity_days' => 30
                ]
            ],
            [
                'product_code' => 'RECHARGE_10GB',
                'name' => 'Data Recharge',
                'category' => 'recharge',
                'region' => null,
                'data_amount_mb' => 10240, // 10GB
                'price' => 80.00,
                'description' => '10GB data recharge for existing SIM/eSIM',
                'system_type' => 'warehouse',
                'is_active' => true,
                'metadata' => [
                    'recharge_type' => 'data_only',
                    'validity_days' => 30
                ]
            ],

            // RSP System Products (example)
            [
                'product_code' => 'RSP_ESIM_START',
                'name' => 'RSP eSIM Starter',
                'category' => 'esim',
                'region' => 'global',
                'data_amount_mb' => 1024, // 1GB
                'price' => 18.00,
                'description' => 'RSP system eSIM with starter package',
                'system_type' => 'rsp',
                'is_active' => true,
                'metadata' => [
                    'activation_type' => 'coupon_based',
                    'validity_days' => 30
                ]
            ]
        ];

        foreach ($products as $product) {
            Product::create($product);
        }

        $this->command->info('Products seeded successfully!');
    }
}