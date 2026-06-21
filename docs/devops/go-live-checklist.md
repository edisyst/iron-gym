# Checklist go-live iron-gym

## Ambiente
- [ ] Server produzione provisionato (OS, PHP 8.3, MySQL 8, Redis 7, Nginx)
- [ ] Certificato SSL installato (Let's Encrypt)
- [ ] Variabili .env produzione configurate (DB, Redis, mail, Flare, VAPID, backup)
- [ ] GitHub Secrets configurati (STAGING_HOST, STAGING_USER, STAGING_KEY, PROD_HOST, ecc.)

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
- [ ] Feature flag `periodization_engine` OFF (attivare dopo 2 settimane di test manuale)
- [ ] Feature flag `push_notifications` OFF (attivare dopo verifica service worker)
- [ ] Feature flag `group_classes` OFF (attivare se la palestra li usa)
- [ ] Primo gruppo pilota: 3-5 atleti + 1 trainer
