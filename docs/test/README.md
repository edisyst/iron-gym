# Test funzionali — Iron Gym

Checklist di verifica manuale per ogni tipologia di utente.  
Spunta `[x]` ciò che funziona, lascia `[ ]` e aggiungi una nota per ciò che non funziona.

## Credenziali demo

| Ruolo        | Email                                     | Password        | URL di atterraggio              |
|--------------|-------------------------------------------|-----------------|---------------------------------|
| Gestore      | admin@admin.admin                         | admin           | /backoffice/dashboard           |
| Trainer      | trainer@trainer.trainer                   | trainer         | /backoffice/dashboard           |
| Receptionist | receptionist@receptionist.receptionist    | receptionist    | /backoffice/dashboard           |
| Atleta       | alessia.colombo@example.com               | atleta          | /athlete (ha mesociclo attivo)  |
| Atleta       | atleta@atleta.atleta                      | atleta          | /athlete (nessun mesociclo)     |

## File per ruolo

- [01-gestore.md](01-gestore.md) — Dashboard KPI, membri, abbonamenti, template, mesocicli
- [02-trainer.md](02-trainer.md) — Template builder, assegnazione mesociclo, atleti
- [03-receptionist.md](03-receptionist.md) — Anagrafica, abbonamenti, accessi
- [04-atleta.md](04-atleta.md) — Dashboard, sessione allenamento, feedback, progressi

## Convenzioni

- `[ ]` — da testare / non funzionante
- `[x]` — funziona
- `[!]` — funziona con anomalia (descrivere sotto)
- `[~]` — non testabile (mancano dati prerequisiti)

Aggiungere note libere sotto ogni voce con `> nota`.
