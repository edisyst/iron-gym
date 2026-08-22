<?php

namespace Database\Seeders;

use App\Models\OpeningHour;
use Illuminate\Database\Seeder;

class OpeningHoursSeeder extends Seeder
{
    public function run(): void
    {
        // Idempotente: svuota e reinserisce
        OpeningHour::truncate();

        // Slot settimanali ricorrenti
        // 0=Lun, 1=Mar, 2=Mer, 3=Gio, 4=Ven, 5=Sab, 6=Dom
        $weekly = [
            ['day_of_week' => 0, 'start_time' => '06:30', 'end_time' => '22:30'], // Lunedì
            ['day_of_week' => 1, 'start_time' => '06:30', 'end_time' => '22:30'], // Martedì
            ['day_of_week' => 2, 'start_time' => '06:30', 'end_time' => '22:30'], // Mercoledì
            ['day_of_week' => 3, 'start_time' => '06:30', 'end_time' => '22:30'], // Giovedì
            ['day_of_week' => 4, 'start_time' => '06:30', 'end_time' => '22:30'], // Venerdì
            ['day_of_week' => 5, 'start_time' => '08:00', 'end_time' => '18:00'], // Sabato
            ['day_of_week' => 6, 'start_time' => '10:00', 'end_time' => '14:00'], // Domenica
        ];

        foreach ($weekly as $slot) {
            OpeningHour::create(array_merge($slot, [
                'specific_date' => null,
                'is_annual' => false,
                'is_open' => true,
            ]));
        }

        // Festività annuali — chiusura totale
        // Anno di riferimento 2000: irrilevante perché is_annual=true
        $holidays = [
            ['date' => '2000-01-01', 'notes' => 'Capodanno'],
            ['date' => '2000-04-25', 'notes' => 'Festa della Liberazione'],
            ['date' => '2000-06-02', 'notes' => 'Festa della Repubblica'],
            ['date' => '2000-12-08', 'notes' => 'Immacolata Concezione'],
            ['date' => '2000-12-25', 'notes' => 'Natale'],
        ];

        foreach ($holidays as $h) {
            OpeningHour::create([
                'day_of_week' => null,
                'specific_date' => $h['date'],
                'is_annual' => true,
                'start_time' => null,
                'end_time' => null,
                'is_open' => false,
                'notes' => $h['notes'],
            ]);
        }

        // Vigilie delle festività — orario ridotto 10:00-14:00
        $eves = [
            ['date' => '2000-12-31', 'notes' => 'Vigilia di Capodanno'],
            ['date' => '2000-04-24', 'notes' => 'Vigilia della Liberazione'],
            ['date' => '2000-06-01', 'notes' => 'Vigilia della Repubblica'],
            ['date' => '2000-12-07', 'notes' => "Vigilia dell'Immacolata"],
            ['date' => '2000-12-24', 'notes' => 'Vigilia di Natale'],
        ];

        foreach ($eves as $e) {
            OpeningHour::create([
                'day_of_week' => null,
                'specific_date' => $e['date'],
                'is_annual' => true,
                'start_time' => '10:00',
                'end_time' => '14:00',
                'is_open' => true,
                'notes' => $e['notes'],
            ]);
        }
    }
}
