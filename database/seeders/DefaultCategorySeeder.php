<?php

namespace Database\Seeders;

use App\Enums\TransactionType;
use App\Models\Category;
use App\Models\Wallet;
use Illuminate\Database\Seeder;

class DefaultCategorySeeder extends Seeder
{
    /**
     * Seed the standard categories for every wallet.
     */
    public function run(): void
    {
        Wallet::query()->each(function (Wallet $wallet): void {
            foreach ($this->categories() as $category) {
                Category::query()->updateOrCreate(
                    [
                        'wallet_id' => $wallet->id,
                        'name' => $category['name'],
                        'type' => $category['type'],
                    ],
                    [
                        'parent_id' => null,
                        'icon' => $category['icon'],
                        'icon_color' => $category['icon_color'],
                        'active' => true,
                    ],
                );
            }
        });
    }

    /**
     * @return list<array{name: string, type: string, icon: string, icon_color: string|null}>
     */
    private function categories(): array
    {
        return [
            ['name' => 'Alimentação', 'type' => TransactionType::EXPENSE->value, 'icon' => 'Utensils', 'icon_color' => '#279112'],
            ['name' => 'Moradia', 'type' => TransactionType::EXPENSE->value, 'icon' => 'Home', 'icon_color' => null],
            ['name' => 'Lazer', 'type' => TransactionType::EXPENSE->value, 'icon' => 'Clapperboard', 'icon_color' => '#0050f0'],
            ['name' => 'Saúde', 'type' => TransactionType::EXPENSE->value, 'icon' => 'HeartPulse', 'icon_color' => '#ff0000'],
            ['name' => 'Salário', 'type' => TransactionType::INCOME->value, 'icon' => 'DollarSign', 'icon_color' => '#129138'],
            ['name' => 'Rendimento', 'type' => TransactionType::INCOME->value, 'icon' => 'TrendingUp', 'icon_color' => '#002c85'],
        ];
    }
}
