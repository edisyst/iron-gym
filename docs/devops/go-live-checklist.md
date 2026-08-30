# Checklist go-live iron-gym

## Ambiente
- [ ] Server produzione provisionato (OS, PHP 8.3, MySQL 8, Redis 7, Nginx)
- [ ] Certificato SSL installato (Let's Encrypt)
- [ ] Variabili .env produzione configurate (DB, Redis, mail, Flare, VAPID, backup)
- [ ] GitHub Secrets configurati (PROD_HOST, PROD_USER, PROD_KEY, ecc.)
  > Nota: deploy staging non ancora configurato (pipeline CI commentata); i secret STAGING_* non sono necessari.

## Database
- [ ] `php artisan migrate --force` eseguito su produzione
- [ ] `php artisan db:seed --class=ExerciseSeeder` eseguito
- [ ] `php artisan pilot:init` eseguito con dati reali palestra
- [ ] Backup manuale pre-go-live eseguito e verificato

## Servizi
- [ ] Queue worker configurato come servizio systemd (o supervisord)
- [ ] Scheduler configurato in crontab (`* * * * * php artisan schedule:run`)
- [ ] Health check `/health` risponde 200
- [ ] Flare riceve errori di test (lancia eccezione manuale e verifica dashboard)

## Accessi
- [ ] Account gestore reale creato e testato
- [ ] Account trainer creati e password comunicate
- [ ] Test login da mobile (app atleta PWA)
- [ ] Push notification testate su un dispositivo reale

## Comunicazioni
- [ ] Mail transazionale testata (scadenza abbonamento inviata a indirizzo reale)
- [ ] SMTP produzione configurato e verificato

## Roll-out graduale

I 14 flag sono gestibili da backoffice → Impostazioni → Funzioni (solo gestore).
Tutti i flag usano `Setting::bool(key, default)` + `Feature::purge` — modificabili live senza deploy.

### Moduli (attivi per tutta la palestra)

| Flag | Stato consigliato al primo avvio | Note |
|---|---|---|
| `group_classes` | OFF → ON se palestra usa corsi collettivi | Attivare solo dopo aver caricato corsi e palinsesti |
| `messaging` | ON | Messaggistica trainer-atleta, nessun prerequisito |
| `pt_bookings` | ON | Prenotazioni PT lato atleta; richiede disponibilita' trainer configurate |

### Sessione atleta (attivi per tutti gli atleti)

| Flag | Stato consigliato al primo avvio | Note |
|---|---|---|
| `readiness_check` | ON | Check pre-sessione 4 domande |
| `exercise_substitution` | ON | Sostituzione esercizio live |
| `session_recap` | ON | Riepilogo post-sessione con PR e top muscoli |
| `personal_records` | ON | Rilevamento e storico PR e1RM |
| `weekly_volume` | ON | Dashboard volume muscolare |
| `plate_calculator` | ON | Nessun gating point UI corrente; lasciare ON |

### Sistema

| Flag | Stato consigliato al primo avvio | Note |
|---|---|---|
| `financial_reports` | ON | Report KPI e fatturato; visibile solo al gestore |
| `periodization_engine` | OFF → ON dopo 2 sett. test | Progressione automatica mesocicli; testare con trainer beta prima |
| `push_notifications` | OFF | Attivare dopo verifica service worker su dispositivo reale |
| `outbound_notifications` | **ON obbligatorio** | Kill switch globale: se OFF blocca tutte le email e push schedulati |
| `in_app_feedback` | OFF | Widget feedback in-app; attivare dopo rodaggio |

- [ ] Primo gruppo pilota: 3-5 atleti + 1 trainer
