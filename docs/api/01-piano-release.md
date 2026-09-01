# Piano Release API — iron-gym

Data: 2026-09-01  
Prerequisiti: `00-assessment.md` letto.

---

## Fase 4 — Catalogo endpoint candidati

### Criteri di esclusione applicati

- **E1** — Escluso se duplica superficie esistente senza valore aggiunto.
- **E2** — Escluso in scrittura se la logica è solo in Livewire senza service estraibile senza refactor significativo (elencati in sezione 4.3).
- **E3** — Escluso tutto il dominio periodizzazione in scrittura per la prima fase.
- **S** — Marcato sensibile se espone dati personali (cert medico, misurazioni, foto, messaggi).

### 4.1 Endpoint candidati

| # | Metodo | Path `/api/v1` | Entità | Verbo | Consumer | Valore | Costo | Rischio | Dipendenze |
|---|---|---|---|---|---|---|---|---|---|
| 1 | GET | /ping | — | read | entrambi | alto | basso | nessuno | — |
| 2 | GET | /me | User autenticato | read | entrambi | alto | basso | nessuno | Sanctum installato |
| 3 | GET | /members | Member (lista paginata) | read | terza parte, script | alto | basso | **S** (dati anagrafici) | — |
| 4 | GET | /members/{id} | Member | read | terza parte, script | alto | basso | **S** | — |
| 5 | GET | /members/{id}/subscription | Subscription attiva | read | terza parte, totem | alto | basso | basso | — |
| 6 | POST | /access-logs | AccessLog (check-in) | write | totem/tornello | alto | medio | medio (validazione cert+abb inline in Livewire — da estrarre) | estrazione service |
| 7 | GET | /access-logs | AccessLog (lista, filtri date+member) | read | script, sistema contabile | alto | basso | **S** (presenza in struttura) | — |
| 8 | GET | /subscription-plans | SubscriptionPlan (lista) | read | sito vetrina, script import | alto | basso | nessuno | — |
| 9 | POST | /members | Member + User | write | script import massivo | alto | alto | alto (crea User+ruolo+password — logica Livewire complessa) | estrazione service |
| 10 | POST | /subscriptions | Subscription | write | script import massivo | medio | alto | alto (calcolo expires_at, accesses_remaining — Livewire) | estrazione service |
| 11 | GET | /exercises | Exercise (lista, filtri) | read | sito vetrina, tool esterno | medio | basso | nessuno | — |
| 12 | GET | /exercises/{slug} | Exercise dettaglio | read | sito vetrina, tool | medio | basso | nessuno | — |
| 13 | GET | /group-classes | GroupClass (definizioni attive) | read | sito vetrina | medio | basso | nessuno | feature flag `group_classes` |
| 14 | GET | /class-occurrences | ClassOccurrence (prossime, con posti) | read | sito vetrina, totem | medio | basso | nessuno | feature flag `group_classes` |
| 15 | POST | /class-bookings | ClassBooking enroll | write | script, kiosk | medio | basso | medio (effetti collaterali waitlist, notifiche) | `ClassBookingService` già estratto |
| 16 | DELETE | /class-bookings/{id} | ClassBooking cancel | write | script, kiosk | medio | basso | medio (promozione waitlist, notifiche) | `ClassBookingService` già estratto |
| 17 | GET | /pt-bookings | PtBooking (filtri trainer/member/date) | read | script, sistema contabile | medio | basso | basso | feature flag `pt_bookings` |
| 18 | POST | /pt-bookings | PtBooking prenotazione | write | script | basso | medio | medio (overlap check, observer) | `PtBookingService` già estratto |
| 19 | GET | /members/{id}/personal-records | PersonalRecord | read | script analytics | basso | basso | **S** (performance atleta) | feature flag `personal_records` |
| 20 | GET | /members/{id}/body-measurements | BodyMeasurement | read | script analytics | basso | medio | **S** MOLTO SENSIBILE | — |
| 21 | GET | /members/{id}/progress-photos | ProgressPhoto | read | — | basso | alto | **S** MOLTO SENSIBILE | — |

### 4.2 Top 10 per rapporto valore/costo

| Rank | # | Endpoint | Valore | Costo | Motivazione |
|---|---|---|---|---|---|
| 1 | 1 | GET /ping | alto | basso | health check fondamentale |
| 2 | 2 | GET /me | alto | basso | verifica token, identità consumer |
| 3 | 8 | GET /subscription-plans | alto | basso | nessuna logica di business, sola lettura |
| 4 | 11 | GET /exercises | medio | basso | catalogo pubblico, cache tag `exercises` già presente |
| 5 | 12 | GET /exercises/{slug} | medio | basso | route key già su slug |
| 6 | 3 | GET /members | alto | basso | valore alto per script import e integrazioni |
| 7 | 4 | GET /members/{id} | alto | basso | prerequisito per qualsiasi integrazione per-atleta |
| 8 | 5 | GET /members/{id}/subscription | alto | basso | totem/tornello — verifica abbonamento |
| 9 | 7 | GET /access-logs | alto | basso | audit, sistema contabile |
| 10 | 13 | GET /group-classes | medio | basso | sito vetrina, gated su feature flag |

### 4.3 Endpoint rimandati — richiedono estrazione service

| Endpoint | Blocco |
|---|---|
| POST /access-logs (check-in) | Logica cert+abb+decremento ingressi in `QuickCheckin.php:77` e `AccessLogList.php:96` — necessita `AccessService` |
| POST /members | Logica User+ruolo+password in `MemberForm.php:109` — necessita `MemberRegistrationService` |
| POST /subscriptions | Calcolo expires_at e accesses_remaining in `SubscriptionForm.php:76` — necessita `SubscriptionService` |
| PUT /members/{id} | Stessa dipendenza di POST /members |
| POST /exercises | Gestione XOR compound/joint_action e pivot muscoli in ExerciseForm Livewire |
| Tutto il dominio periodizzazione in scrittura | Per decisione: escluso fase 1 |

---

## Fase 5 — Piano release

### Già deciso (non in discussione)

- Kill switch: feature flag `public_api` in `config/features.php`, default `false`, convenzioni SET01.
- Auth: Sanctum personal access token con abilities; account di servizio dedicato per consumer con ruolo `api_client` privo di permessi standard.

### API01 — Foundation (prerequisiti, nessun endpoint di dominio)

**Obiettivo:** infrastruttura completa pronta a ricevere endpoint di dominio.

**Lavori inclusi:**
1. `composer require laravel/sanctum` + publish migration `personal_access_tokens` + `php artisan migrate`
2. `User` aggiunge trait `HasApiTokens`
3. `routes/api.php` creato; `bootstrap/app.php` aggiunge `api: routes/api.php` + middleware group `api` con `auth:sanctum`
4. Feature flag `public_api` aggiunto a `config/features.php` (gruppo Sistema, default false) + `SettingsFlagSeeder` aggiornato
5. Middleware `EnsureApiEnabled` che legge il flag e restituisce 503 se spento
6. Rate limiting: `RateLimiter::for('api', ...)` in AppServiceProvider — 60 req/min per token, 10 req/min unauthenticated
7. Guard `api_client`: nuovo ruolo spatie, creazione account di servizio tramite artisan command dedicato (`api:create-token`)
8. Meccanismo blocco web per account di servizio: colonna `is_service_account boolean default false` su `users` + middleware che blocca login web se true (oppure: nessun ruolo web assegnato = bloccato dai middleware route esistenti — da decidere, vedi punti aperti)
9. Gestione errori JSON uniforme: `Handler` con risposte strutturate `{error: {code, message}}` per 401/403/404/422/429/500
10. Endpoint: `GET /api/v1/ping` e `GET /api/v1/me`
11. Test: feature test API foundation (token assente → 401, flag spento → 503, ping, me)

**Criterio done:** CI verde, flag `public_api` off per default, token creabile via artisan, ping + me rispondono.  
**Stima:** 1 sprint (alta priorità infra).  
**Dipendenze:** nessuna — tutto nuovo.

---

### API02 — Lettura dati gestionali (read-only, no dati sensibili)

**Obiettivo:** esporre i dati anagrafici e di abbonamento necessari a integrazioni esterne e totem.

**Endpoint inclusi:**
- `GET /api/v1/subscription-plans` — lista piani attivi (public, no auth richiesta, o ability `plans:read`)
- `GET /api/v1/members` — lista paginata (ability `members:read`), filtri: search, is_active, cert_expiry_before
- `GET /api/v1/members/{id}` — dettaglio (ability `members:read`)
- `GET /api/v1/members/{id}/subscription` — abbonamento attivo (ability `members:read`)
- `GET /api/v1/access-logs` — lista paginata (ability `access-logs:read`), filtri: member_id, date_from, date_to
- `GET /api/v1/exercises` — lista paginata, filtri: muscle, equipment, measurement_type (ability `exercises:read` o public)
- `GET /api/v1/exercises/{slug}` — dettaglio con muscoli e equipment (ability `exercises:read` o public)

**Criterio done:** 7 endpoint, test feature per ciascuno (auth, ability, paginazione, filtri), PHPStan 0 errori, Pint conforme.  
**Stima:** 1 sprint.  
**Dipendenze:** API00 completato.  
**Note:** Member ha SoftDeletes — i soft-deleted non vengono esposti per default; aggiungere scope `withTrashed` solo se l'ability lo prevede.

---

### API03 — Check-in totem + lettura corsi collettivi

**Obiettivo:** abilitare totem fisici e sito vetrina.

**Prerequisiti fuori API:**  
Estrazione `AccessService` da `QuickCheckin.php` (logica validazione cert+abb+decremento ingressi) — refactor necessario prima di questo endpoint.

**Endpoint inclusi:**
- `POST /api/v1/access-logs` — check-in (ability `access-logs:write`); restituisce 201 con AccessLog; 422 se cert scaduto/abbonamento non attivo/ingressi esauriti
- `GET /api/v1/group-classes` — definizioni corsi attivi (gated su flag `group_classes`; ability `group-classes:read`)
- `GET /api/v1/class-occurrences` — occorrenze future con posti disponibili (filtri: date_from, date_to, group_class_id; ability `group-classes:read`)

**Criterio done:** 3 endpoint, test con scenari errore (cert scaduto, abbonamento inattivo, ingressi 0), flag `group_classes` spento → 404/403.  
**Stima:** 1.5 sprint (include refactor AccessService).  
**Dipendenze:** API01 completato + estrazione AccessService.

---

### API04 — Prenotazioni corsi collettivi

**Obiettivo:** kiosk e integrazioni possono iscrivere/cancellare atleti ai corsi.

**Endpoint inclusi:**
- `POST /api/v1/class-bookings` — enroll (ability `class-bookings:write`); delega a `ClassBookingService::enroll()` già estratto; effetti collaterali: waitlist, notifiche (gated su `outbound_notifications`)
- `DELETE /api/v1/class-bookings/{id}` — cancel (ability `class-bookings:write`); delega a `ClassBookingService::cancel()`

**Criterio done:** 2 endpoint, test per enroll/cancel/waitlist/full-course, ownership check (solo prenotazioni del member associato al token).  
**Stima:** 1 sprint.  
**Dipendenze:** API02 completato. `ClassBookingService` già estratto — nessun refactor aggiuntivo.

---

### API05 — Import massivo (script interni)

**Obiettivo:** script interni possono creare tesserati e abbonamenti in bulk.

**Prerequisiti fuori API:**  
Estrazione `MemberRegistrationService` da `MemberForm.php` e `SubscriptionService` da `SubscriptionForm.php`.

**Endpoint inclusi:**
- `POST /api/v1/members` — crea Member + User (ability `members:write`)
- `POST /api/v1/subscriptions` — crea Subscription (ability `subscriptions:write`)

**Criterio done:** 2 endpoint, test validazione + idempotenza email, test calcolo expires_at, PHPStan.  
**Stima:** 2 sprint (include refactor due service).  
**Dipendenze:** API01 completato + estrazione MemberRegistrationService + SubscriptionService.

---

### Punti decisionali aperti

**1. Strategia di versioning del path**  
Opzione A: `/api/v1/...` fisso nel path (già proposto) — semplice, nessun middleware aggiuntivo.  
Opzione B: versioning via header `Accept: application/vnd.iron-gym.v1+json` — più flessibile ma richiede resolver custom.  
**Da decidere prima di API00.**

**2. Formato paginazione**  
Opzione A: paginazione standard Laravel (`data`, `links`, `meta`) — zero lavoro aggiuntivo.  
Opzione B: formato custom (`items`, `total`, `page`, `per_page`) — richiede Resource/transformer dedicato.  
**Da decidere prima di API01.**

**3. Documentazione (OpenAPI)**  
Opzione A: OpenAPI scritto a mano in `docs/api/openapi.yaml` — zero dipendenze, aggiornato a mano.  
Opzione B: pacchetto generatore (es. `dedoc/scramble`) — nuova dipendenza, richiede approvazione come ogni `composer require`.  
Opzione C: nessuna documentazione formale nella prima fase — solo questo piano e i test come contratto.  
**Da decidere prima di API01. Nuova dipendenza richiede approvazione esplicita.**

**4. Meccanismo blocco web account di servizio**  
Opzione A: colonna `is_service_account boolean` su `users` + middleware — richiede migration, più esplicito.  
Opzione B: nessun ruolo web → bloccato da `role:atleta|gestore|...` esistenti — zero migration, ma fragile se si aggiungono route non-gated.  
**Da decidere prima di API00.**

**5. FK `booked_by` / `checked_in_by` / `created_by`**  
Le scritture che valorizzano queste colonne con `Auth::id()` useranno l'ID dell'account di servizio. Questo è corretto semanticamente (è il sistema che fa l'operazione) ma potrebbe rompere query che assumono che `booked_by` sia un User con ruolo staff. Verificare i report che filtrano su questi campi prima di API02/API03.

**6. Abilities standard vs permessi spatie**  
I token Sanctum usano abilities (stringhe libere nel token). I Gate usano `hasRole`. Scegliere se le abilities replicano i gate esistenti o definiscono un namespace separato (`members:read`, `access-logs:write`, ecc.). Proposta: namespace separato, mai delegare ai gate role-based dall'API.

---

*Documento prodotto in sessione read-only — nessun file applicativo modificato.*
