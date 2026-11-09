<?php

namespace Database\Seeders;

use App\Models\Feature;
use Illuminate\Database\Seeder;

class FeatureSeeder extends Seeder
{
    public function run(): void
    {
        $features = [
            // Fitur PRO
            [
                'code' => 'profit_analysis',
                'name' => 'Analisis Laba & Margin Produk',
                'module' => 'reports',
                'description' => 'Perhitungan laba kotor, estimasi laba bersih, dan margin per produk.',
            ],
            [
                'code' => 'expense_management',
                'name' => 'Pengeluaran Operasional',
                'module' => 'expenses',
                'description' => 'Pencatatan dan laporan biaya operasional usaha.',
            ],
            [
                'code' => 'stock_opname',
                'name' => 'Stok Opname & Manajemen Stok Lanjutan',
                'module' => 'inventory',
                'description' => 'Penyesuaian stok fisik, stok opname, dan analisis perputaran stok.',
            ],
            [
                'code' => 'supplier_management',
                'name' => 'Manajemen Pemasok (Supplier)',
                'module' => 'suppliers',
                'description' => 'Pengelolaan daftar pemasok dan riwayat pembelian stok.',
            ],
            [
                'code' => 'receivables',
                'name' => 'Manajemen Piutang Pelanggan',
                'module' => 'receivables',
                'description' => 'Pencatatan kasbon/piutang pelanggan dan pembayaran bertahap.',
            ],
            [
                'code' => 'report_export',
                'name' => 'Export Laporan Excel & PDF',
                'module' => 'reports',
                'description' => 'Ekspor laporan penjualan, stok, dan keuangan ke PDF dan Excel.',
            ],
            [
                'code' => 'staff_management',
                'name' => 'Manajemen Pegawai Lokal',
                'module' => 'staff',
                'description' => 'Pengelolaan akses kasir dan staf pada perangkat.',
            ],
            [
                'code' => 'cash_shifts',
                'name' => 'Manajemen Shift Kasir',
                'module' => 'cashier',
                'description' => 'Buka/tutup shift kasir dan rekonsiliasi uang tunai laci.',
            ],

            // Fitur BUSINESS
            [
                'code' => 'multi_outlet',
                'name' => 'Manajemen Multi-Outlet',
                'module' => 'enterprise',
                'description' => 'Pengelolaan lebih dari satu cabang usaha dan laporan gabungan.',
            ],
            [
                'code' => 'multi_device_sync',
                'name' => 'Sinkronisasi Multi-Perangkat',
                'module' => 'enterprise',
                'description' => 'Sinkronisasi data antar perangkat kasir dan pemilik usaha.',
            ],
            [
                'code' => 'business_health_score',
                'name' => 'Business Health Score & Prediksi Stok',
                'module' => 'intelligence',
                'description' => 'Indikator kesehatan usaha dan prediksi kebutuhan restock otomatis.',
            ],
            [
                'code' => 'ai_assistant',
                'name' => 'Smart Business Assistant AI',
                'module' => 'intelligence',
                'description' => 'Rekomendasi pengembangan bisnis berbasis data penjualan.',
            ],
        ];

        foreach ($features as $featureData) {
            Feature::updateOrCreate(
                ['code' => $featureData['code']],
                $featureData
            );
        }
    }
}

