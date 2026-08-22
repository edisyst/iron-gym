<?php

namespace Database\Seeders;

use App\Models\DumbbellInventory;
use Illuminate\Database\Seeder;

class DumbbellInventorySeeder extends Seeder
{
    /**
     * Dotazione standard palestra fitness — 1–9 kg step 1 kg + 10–50 kg step 2 kg, idempotente.
     * Quantità 0 per pesi non disponibili: 7, 9, 48, 50 kg.
     */
    public function run(): void
    {
        if (app()->environment('production')) {
            return;
        }

        $zeroQty = [7, 9, 48, 50];

        $dumbbells = [];

        for ($kg = 1; $kg <= 9; $kg++) {
            $dumbbells[] = ['weight_kg' => $kg, 'quantity_pairs' => in_array($kg, $zeroQty) ? 0 : 1];
        }

        for ($kg = 10; $kg <= 50; $kg += 2) {
            $dumbbells[] = ['weight_kg' => $kg, 'quantity_pairs' => in_array($kg, $zeroQty) ? 0 : 1];
        }

        foreach ($dumbbells as $row) {
            DumbbellInventory::updateOrCreate(
                ['weight_kg' => $row['weight_kg']],
                ['quantity_pairs' => $row['quantity_pairs'], 'color' => null, 'is_active' => true]
            );
        }
    }
}
