<?php

namespace Database\Seeders;

use App\Models\Currency;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CurrencySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $currencies = [
            ['code' => 'EUR', 'name' => 'Euro', 'symbol' => '€', 'decimal_places' => 2, 'sort_order' => 1],
            ['code' => 'USD', 'name' => 'US Dollar', 'symbol' => '$', 'decimal_places' => 2, 'sort_order' => 2],
            ['code' => 'GBP', 'name' => 'British Pound', 'symbol' => '£', 'decimal_places' => 2, 'sort_order' => 3],
            ['code' => 'CHF', 'name' => 'Swiss Franc', 'symbol' => 'CHF', 'decimal_places' => 2, 'sort_order' => 4],
            ['code' => 'SEK', 'name' => 'Swedish Krona', 'symbol' => 'kr', 'decimal_places' => 2, 'sort_order' => 5],
            ['code' => 'NOK', 'name' => 'Norwegian Krone', 'symbol' => 'kr', 'decimal_places' => 2, 'sort_order' => 6],
            ['code' => 'DKK', 'name' => 'Danish Krone', 'symbol' => 'kr', 'decimal_places' => 2, 'sort_order' => 7],
            ['code' => 'AED', 'name' => 'UAE Dirham', 'symbol' => 'د.إ', 'decimal_places' => 2, 'sort_order' => 8],
            ['code' => 'THB', 'name' => 'Thai Baht', 'symbol' => '฿', 'decimal_places' => 2, 'sort_order' => 9],
            ['code' => 'SGD', 'name' => 'Singapore Dollar', 'symbol' => 'S$', 'decimal_places' => 2, 'sort_order' => 10],
            ['code' => 'JPY', 'name' => 'Japanese Yen', 'symbol' => '¥', 'decimal_places' => 0, 'sort_order' => 11],
            ['code' => 'CNY', 'name' => 'Chinese Yuan', 'symbol' => '¥', 'decimal_places' => 2, 'sort_order' => 12],
            ['code' => 'AUD', 'name' => 'Australian Dollar', 'symbol' => 'A$', 'decimal_places' => 2, 'sort_order' => 13],
            ['code' => 'NZD', 'name' => 'New Zealand Dollar', 'symbol' => 'NZ$', 'decimal_places' => 2, 'sort_order' => 14],
            ['code' => 'CAD', 'name' => 'Canadian Dollar', 'symbol' => 'C$', 'decimal_places' => 2, 'sort_order' => 15],
            ['code' => 'INR', 'name' => 'Indian Rupee', 'symbol' => '₹', 'decimal_places' => 2, 'sort_order' => 16],
            ['code' => 'IDR', 'name' => 'Indonesian Rupiah', 'symbol' => 'Rp', 'decimal_places' => 0, 'sort_order' => 17],
            ['code' => 'MYR', 'name' => 'Malaysian Ringgit', 'symbol' => 'RM', 'decimal_places' => 2, 'sort_order' => 18],
            ['code' => 'PHP', 'name' => 'Philippine Peso', 'symbol' => '₱', 'decimal_places' => 2, 'sort_order' => 19],
            ['code' => 'VND', 'name' => 'Vietnamese Dong', 'symbol' => '₫', 'decimal_places' => 0, 'sort_order' => 20],
        ];

        foreach ($currencies as $currency) {
            Currency::updateOrCreate(
                ['code' => $currency['code']],
                $currency
            );
        }
    }
}
