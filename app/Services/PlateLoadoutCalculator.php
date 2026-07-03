<?php

namespace App\Services;

use App\Models\PlateInventory;

class PlateLoadoutCalculator
{
    /**
     * Calcola i dischi per lato del bilanciere dato un peso target e il peso della barra.
     *
     * Algoritmo greedy decrescente sui dischi attivi con quantity_pairs > 0.
     * Se il peso esatto non e' raggiungibile, restituisce la combinazione per difetto
     * (delta_kg positivo indica kg mancanti).
     *
     * @return array{plates: array<array{weight_kg: float, count: int, color: string|null}>, loaded_kg: float, delta_kg: float, bar_kg: float, target_kg: float}
     */
    public function calculate(float $targetKg, float $barKg): array
    {
        $perSide = ($targetKg - $barKg) / 2;

        // Peso inferiore o uguale alla barra: nessun disco necessario
        if ($perSide <= 0) {
            return [
                'plates' => [],
                'loaded_kg' => $barKg,
                'delta_kg' => round($targetKg - $barKg, 2),
                'bar_kg' => $barKg,
                'target_kg' => $targetKg,
            ];
        }

        // Carica dischi attivi con almeno una coppia disponibile, dal piu' pesante
        $available = PlateInventory::active()
            ->where('quantity_pairs', '>', 0)
            ->get();

        $remaining = $perSide;
        $platesPerSide = [];

        foreach ($available as $plate) {
            $plateWeight = (float) $plate->weight_kg;

            // Salta il disco se supera il peso residuo (tolleranza floating point)
            if ($plateWeight > $remaining + 0.001) {
                continue;
            }

            $maxCount = (int) floor($remaining / $plateWeight);
            // quantity_pairs rappresenta il numero massimo per lato
            $count = min($maxCount, (int) $plate->quantity_pairs);

            if ($count <= 0) {
                continue;
            }

            $platesPerSide[] = [
                'weight_kg' => $plateWeight,
                'count' => $count,
                'color' => $plate->color,
            ];

            $remaining -= $count * $plateWeight;

            if ($remaining < 0.001) {
                break;
            }
        }

        $loadedPerSide = $perSide - $remaining;
        $loadedTotal = $barKg + $loadedPerSide * 2;

        return [
            'plates' => $platesPerSide,
            'loaded_kg' => round($loadedTotal, 2),
            'delta_kg' => round($targetKg - $loadedTotal, 2),
            'bar_kg' => $barKg,
            'target_kg' => $targetKg,
        ];
    }
}
