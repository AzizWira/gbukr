<?php

namespace Database\Seeders;

use App\Models\{BankAccount, Country, GoGroup, ShippingOption, User, Warehouse};
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $ownerEmail = env('OWNER_EMAIL');
        $ownerPassword = env('OWNER_PASSWORD');

        if (!$ownerEmail || !$ownerPassword || strlen($ownerPassword) < 12) {
            throw new \RuntimeException('Isi OWNER_EMAIL dan OWNER_PASSWORD minimal 12 karakter sebelum seed.');
        }

        User::updateOrCreate(
            ['email' => strtolower($ownerEmail)],
            [
                'name' => env('OWNER_NAME', 'Owner GBUKPOP x KRJASTIP'),
                'password' => $ownerPassword,
                'role' => 'owner',
                'admin_enabled' => false,
                'active' => true,
                'email_verified_at' => now(),
            ]
        );

        $countries = [
            ['KR', 'Korea Selatan', 'KRW', '₩', 12, 25000, [3000, 5000, 12000]],
            ['JP', 'Jepang', 'JPY', '¥', 110, 25000, [500, 1000, 1500]],
            ['CH', 'China', 'CNY', '¥', 2400, 25000, [8, 12, 16]],
            ['US', 'Amerika Serikat', 'USD', '$', 16500, 25000, [5, 10, 15]],
            ['PH', 'Filipina', 'PHP', '₱', 290, 25000, [100, 200, 300]],
            ['TH', 'Thailand', 'THB', '฿', 510, 25000, [50, 100, 150]],
        ];

        foreach ($countries as [$code, $name, $currency, $symbol, $rate, $adminFee, $shippingAmounts]) {
            $country = Country::updateOrCreate(
                ['code' => $code],
                [
                    'name' => $name,
                    'currency_code' => $currency,
                    'currency_symbol' => $symbol,
                    'rate' => $rate,
                    'admin_fee_idr' => $adminFee,
                    'active' => true,
                ]
            );

            foreach ($shippingAmounts as $amount) {
                ShippingOption::updateOrCreate(
                    ['country_id' => $country->id, 'amount_foreign' => $amount],
                    [
                        'label' => $symbol . number_format($amount, 0, ',', '.'),
                        'active' => true,
                    ]
                );
            }
        }

        $krCountry = Country::where('code', 'KR')->first();
        if ($krCountry) {
            ShippingOption::updateOrCreate(
                ['country_id' => $krCountry->id, 'amount_foreign' => 0],
                ['label' => 'Free Shipping', 'active' => true]
            );
        }

        foreach ([
            ['MY', 'Malaysia', 'MYR', 'RM'],
            ['TW', 'Taiwan', 'TWD', 'NT$'],
            ['SG', 'Singapura', 'SGD', 'S$'],
            ['ID', 'Indonesia', 'IDR', 'Rp'],
        ] as [$code, $name, $currency, $symbol]) {
            Country::updateOrCreate(
                ['code' => $code],
                [
                    'name' => $name,
                    'currency_code' => $currency,
                    'currency_symbol' => $symbol,
                    'rate' => 1,
                    'admin_fee_idr' => 0,
                    'active' => false,
                ]
            );
        }

        $warehouseSeeds = [
            ['KR', 'KR-WH01', 'Warehouse Korea 01'],
            ['KR', 'KR-WH02', 'Warehouse Korea 02'],
            ['JP', 'JP-WH01', 'Warehouse Jepang 01'],
            ['CH', 'CH-WH01', 'Warehouse China 01'],
            ['CH', 'CH-WH02', 'Warehouse China 02'],
            ['US', 'US-WH01', 'Warehouse Amerika 01'],
            ['PH', 'PH-WH01', 'Warehouse Filipina 01'],
            ['TH', 'TH-WH01', 'Warehouse Thailand 01'],
        ];

        foreach ($warehouseSeeds as [$countryCode, $code, $name]) {
            $country = Country::where('code', $countryCode)->firstOrFail();
            Warehouse::updateOrCreate(
                ['code' => $code],
                [
                    'country_id' => $country->id,
                    'name' => $name,
                    'notes' => 'Data warehouse operasional. Ubah nama/catatan sesuai kebutuhan.',
                    'active' => true,
                ]
            );
        }

        GoGroup::updateOrCreate(['name' => 'GBUKPOP'], ['status' => 'active', 'notes' => 'GO utama GBUKPOP']);
        GoGroup::updateOrCreate(['name' => 'KRJASTIP'], ['status' => 'active', 'notes' => 'GO utama KRJASTIP']);

        BankAccount::updateOrCreate(
            ['account_number' => '0000000000'],
            [
                'bank_name' => 'Atur rekening',
                'account_name' => 'GBUKPOP x KRJASTIP',
                'instructions' => 'Data placeholder. Ganti dan aktifkan rekening asli dari dashboard Owner sebelum menerima pembayaran.',
                'active' => false,
            ]
        );

        if (filter_var(env('SEED_DEMO_DATA', true), FILTER_VALIDATE_BOOL)) {
            $this->call(DemoDataSeeder::class);
        }
    }
}
