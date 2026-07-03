<?php

namespace Database\Seeders;

use App\Models\PlateInventory;
use Illuminate\Database\Seeder;

class PlateInventorySeeder extends Seeder
{
    /**
     * Dotazione standard IPF/commerciale — idempotente via updateOrCreate su weight_kg.
     */
    public function run(): void
    {
        // Non eseguire in produzione: dati di configurazione palestra, da inserire manualmente
        if (app()->environment('production')) {
            return;
        }

        $plates = [
            ['weight_kg' => 25.00, 'quantity_pairs' => 4, 'color' => 'rosso'],
            ['weight_kg' => 20.00, 'quantity_pairs' => 4, 'color' => 'blu'],
            ['weight_kg' => 15.00, 'quantity_pairs' => 4, 'color' => 'giallo'],
            ['weight_kg' => 10.00, 'quantity_pairs' => 6, 'color' => 'verde'],
            ['weight_kg' => 5.00, 'quantity_pairs' => 8, 'color' => 'bianco'],
            ['weight_kg' => 2.50, 'quantity_pairs' => 8, 'color' => 'nero'],
            ['weight_kg' => 1.25, 'quantity_pairs' => 8, 'color' => 'cromato'],
        ];

        foreach ($plates as $plate) {
            PlateInventory::updateOrCreate(
                ['weight_kg' => $plate['weight_kg']],
                [
                    'quantity_pairs' => $plate['quantity_pairs'],
                    'color' => $plate['color'],
                    'is_active' => true,
                ]
            );
        }
    }
}
