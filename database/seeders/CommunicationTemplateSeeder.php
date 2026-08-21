<?php

namespace Database\Seeders;

use App\Models\CommunicationTemplate;
use Illuminate\Database\Seeder;

class CommunicationTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'name'    => 'Scadenza abbonamento — Email',
                'channel' => 'email',
                'subject' => 'Il tuo abbonamento Iron Gym sta per scadere',
                'body'    => "Ciao {{nome}} {{cognome}},\n\nti ricordiamo che il tuo abbonamento scade il {{scadenza_abbonamento}}.\n\nRinnova in palestra o contattaci per non interrompere i tuoi allenamenti.\n\nA presto,\nLo staff di Iron Gym",
            ],
            [
                'name'    => 'Scadenza abbonamento — SMS',
                'channel' => 'sms',
                'subject' => null,
                'body'    => "Iron Gym: ciao {{nome}}, il tuo abbonamento scade il {{scadenza_abbonamento}}. Rinnova in palestra per non perdere neanche un allenamento.",
            ],
            [
                'name'    => 'Scadenza certificato medico — Email',
                'channel' => 'email',
                'subject' => 'Certificato medico in scadenza — Iron Gym',
                'body'    => "Ciao {{nome}} {{cognome}},\n\nti segnaliamo che il tuo certificato medico scade il {{scadenza_certificato}}.\n\nPer continuare ad accedere alla palestra devi rinnovarlo e consegnarne una copia in reception prima della scadenza.\n\nA presto,\nLo staff di Iron Gym",
            ],
            [
                'name'    => 'Scadenza certificato medico — SMS',
                'channel' => 'sms',
                'subject' => null,
                'body'    => "Iron Gym: ciao {{nome}}, il tuo certificato medico scade il {{scadenza_certificato}}. Rinnovalo e portalo in reception prima della scadenza.",
            ],
            [
                'name'    => 'Chiusura straordinaria — Email',
                'channel' => 'email',
                'subject' => 'Chiusura straordinaria Iron Gym',
                'body'    => "Ciao {{nome}} {{cognome}},\n\nti informiamo che la palestra rimarrà chiusa per motivi straordinari.\n\nCi scusiamo per il disagio e ti aggiorneremo non appena sarà di nuovo possibile allenarsi.\n\nGrazie per la comprensione,\nLo staff di Iron Gym",
            ],
            [
                'name'    => 'Chiusura straordinaria — SMS',
                'channel' => 'sms',
                'subject' => null,
                'body'    => "Iron Gym: ciao {{nome}}, la palestra sarà chiusa straordinariamente. Ci scusiamo per il disagio e ti aggiorneremo a breve.",
            ],
            [
                'name'    => 'Evento speciale — Email',
                'channel' => 'email',
                'subject' => 'Ti aspettiamo a un evento speciale — Iron Gym',
                'body'    => "Ciao {{nome}} {{cognome}},\n\nti aspettiamo a un evento speciale in palestra!\n\nResta aggiornato sui nostri canali per tutti i dettagli.\n\nA presto,\nLo staff di Iron Gym",
            ],
            [
                'name'    => 'Evento speciale — SMS',
                'channel' => 'sms',
                'subject' => null,
                'body'    => "Iron Gym: ciao {{nome}}, ti aspettiamo a un evento speciale! Controlla i nostri canali per i dettagli.",
            ],
        ];

        foreach ($templates as $data) {
            CommunicationTemplate::firstOrCreate(
                ['name' => $data['name']],
                $data,
            );
        }
    }
}
