<?php

use App\Models\PlateInventory;
use App\Services\PlateLoadoutCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Crea i dischi standard del seeder in modo inline per i test.
 */
function createStandardPlates(): void
{
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
        PlateInventory::create(array_merge($plate, ['is_active' => true]));
    }
}

test('combinazione esatta: 100kg con barra 20kg', function () {
    createStandardPlates();

    $calculator = new PlateLoadoutCalculator;
    $result = $calculator->calculate(100.0, 20.0);

    // Peso totale esatto: nessun delta
    expect($result['target_kg'])->toBe(100.0)
        ->and($result['bar_kg'])->toBe(20.0)
        ->and($result['loaded_kg'])->toBe(100.0)
        ->and($result['delta_kg'])->toBe(0.0)
        ->and($result['plates'])->not->toBeEmpty();
});

test('arrotondamento per difetto: solo dischi 25kg e 10kg disponibili, target 50kg barra 20kg', function () {
    // Inventario ridotto: solo 25kg e 10kg
    PlateInventory::create(['weight_kg' => 25.00, 'quantity_pairs' => 4, 'color' => 'rosso', 'is_active' => true]);
    PlateInventory::create(['weight_kg' => 10.00, 'quantity_pairs' => 6, 'color' => 'verde', 'is_active' => true]);

    $calculator = new PlateLoadoutCalculator;
    // perSide = (50 - 20) / 2 = 15kg
    // 25kg troppo grande; 10kg: 1 per lato = 10kg. remaining = 5kg, nessun altro disco
    $result = $calculator->calculate(50.0, 20.0);

    expect($result['loaded_kg'])->toBe(40.0)  // 20 barra + 10*2
        ->and($result['delta_kg'])->toBe(10.0) // 50 - 40
        ->and($result['plates'])->toHaveCount(1)
        ->and($result['plates'][0]['weight_kg'])->toBe(10.0)
        ->and($result['plates'][0]['count'])->toBe(1);
});

test('inventario vuoto (quantity_pairs=0): nessun disco caricato', function () {
    // Tutti i dischi con quantity_pairs=0 — non devono essere usati
    PlateInventory::create(['weight_kg' => 20.00, 'quantity_pairs' => 0, 'color' => 'blu', 'is_active' => true]);
    PlateInventory::create(['weight_kg' => 10.00, 'quantity_pairs' => 0, 'color' => 'verde', 'is_active' => true]);

    $calculator = new PlateLoadoutCalculator;
    $result = $calculator->calculate(60.0, 20.0);

    expect($result['plates'])->toBeEmpty()
        ->and($result['loaded_kg'])->toBe(20.0) // solo barra
        ->and($result['delta_kg'])->toBe(40.0); // 60 - 20
});

test('peso target inferiore alla barra: nessun disco, delta negativo, service non crasha', function () {
    createStandardPlates();

    $calculator = new PlateLoadoutCalculator;
    // target=15kg < barra=20kg: perSide negativo
    $result = $calculator->calculate(15.0, 20.0);

    expect($result['plates'])->toBeEmpty()
        ->and($result['loaded_kg'])->toBe(20.0)
        ->and($result['delta_kg'])->toBe(-5.0)
        ->and($result['bar_kg'])->toBe(20.0)
        ->and($result['target_kg'])->toBe(15.0);
});
