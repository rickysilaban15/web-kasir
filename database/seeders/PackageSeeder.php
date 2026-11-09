<?php

namespace Database\Seeders;

use App\Models\Feature;
use App\Models\Package;
use Illuminate\Database\Seeder;

class PackageSeeder extends Seeder
{
    public function run(): void
    {
        $free = Package::updateOrCreate(
            ['code' => 'FREE'],
            [
                'name' => 'Paket FREE',
                'description' => 'Fitur dasar kasir UMKM offline-first (produk, transaksi, struk digital, laporan omzet harian, backup lokal).',
                'max_devices' => 1,
                'default_duration_days' => null,
                'sort_order' => 1,
                'is_active' => true,
            ]
        );
        $free->features()->sync([]);

        $proFeatureCodes = [
            'profit_analysis',
            'expense_management',
            'stock_opname',
            'supplier_management',
            'receivables',
            'report_export',
            'staff_management',
            'cash_shifts',
        ];

        $pro = Package::updateOrCreate(
            ['code' => 'PRO'],
            [
                'name' => 'Paket PRO',
                'description' => 'Fitur lanjutan analisis laba, pengeluaran operasional, stok opname, supplier, piutang, shift kasir, dan export laporan.',
                'max_devices' => 2,
                'default_duration_days' => 365,
                'sort_order' => 2,
                'is_active' => true,
            ]
        );
        $proFeatureIds = Feature::whereIn('code', $proFeatureCodes)->pluck('id')->all();
        $pro->features()->sync($proFeatureIds);

        $business = Package::updateOrCreate(
            ['code' => 'BUSINESS'],
            [
                'name' => 'Paket BUSINESS',
                'description' => 'Seluruh fitur PRO ditambah multi-outlet, multi-device sync, Business Health Score, prediksi stok, dan AI Assistant.',
                'max_devices' => 5,
                'default_duration_days' => 365,
                'sort_order' => 3,
                'is_active' => true,
            ]
        );
        $allFeatureIds = Feature::pluck('id')->all();
        $business->features()->sync($allFeatureIds);
    }
}

