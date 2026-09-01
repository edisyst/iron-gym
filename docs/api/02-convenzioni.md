# API v1 — Convenzioni

Data: 2026-09-01  
Release: API01 (foundation).

---

## Prefisso e versioning

Tutte le route: `/api/v1/{risorsa}`.  
Versioning nel path, non in header. Nessun resolver custom.

---

## Autenticazione

Header: `Authorization: Bearer <plain_text_token>`  
Meccanismo: Sanctum personal access token (nessun OAuth2, nessun Passport, nessun JWT).  
I token appartengono ad account di servizio (`is_service_account = true`, ruolo `api_client`).  
Un account per consumer; un consumer può avere più token (es. rotazione).

### Onboarding nuovo consumer

```bash
# 1. Crea account di servizio (idempotente)
php artisan api:create-service-account <consumer-slug>

# 2. Emetti token con abilities specifiche
php artisan api:issue-token <consumer-slug> --name="<descrizione>" --abilities="members:read,access-logs:write"

# 3. Fornisci il plain text token al consumer (una sola volta)

# Gestione token attivi
php artisan api:tokens
php artisan api:tokens --consumer=<consumer-slug>
php artisan api:tokens --revoke=<token-id>
```

Gli account di servizio non possono autenticarsi via browser.  
`email` dell'account: `api-<consumer-slug>@service.iron-gym.internal` (non contattabile).

---

## Kill switch

Feature flag `public_api` in `config/features.php`, chiave settings `public_api_enabled`, default `false`.  
Spento: tutte le route `/api/v1/*` restituiscono 503, **tranne** `GET /api/v1/ping`.  
Toggle: backoffice → Impostazioni → Funzioni (solo gestore) oppure `Setting::write('public_api_enabled', true/false)`.

---

## Formato risposte

### Successo

Chiavi snake_case. Date ISO 8601 UTC (`2026-09-01T10:30:00Z`).  
Esercizi e muscoli identificati per slug quando presenti come riferimento.

```json
{
  "data": { ... },
  "links": { "first": "...", "last": "...", "prev": null, "next": "..." },
  "meta": { "current_page": 1, "last_page": 3, "per_page": 25, "total": 67 }
}
```

Per risorse singole (non collection): JSON flat senza envelope `data`.

### Errori — forma uniforme

Tutti gli status di errore condividono le stesse chiavi:

```json
{
  "message": "Descrizione leggibile.",
  "code": "<slug-stabile>",
  "errors": { "campo": ["messaggio"] }
}
```

`errors` presente solo per 422 (ValidationException). Per tutti gli altri status: `message` + `code`.

| HTTP | `code` | Causa |
|---|---|---|
| 401 | `unauthenticated` | Token assente, scaduto o revocato |
| 403 | `forbidden` | Ability mancante sul token |
| 404 | `not_found` | Risorsa non trovata (mai "Server Error") |
| 422 | `validation_failed` | Payload non valido (+ campo `errors`) |
| 422 | `cert_invalid` | Certificato medico scaduto o mancante (check-in) |
| 422 | `subscription_inactive` | Nessun abbonamento attivo (check-in) |
| 422 | `accesses_exhausted` | Accessi residui esauriti (check-in) |
| 429 | `rate_limited` | Rate limit superato (+ header `Retry-After`) |
| 503 | `api_disabled` | Kill switch `public_api` spento |
| 503 | `module_disabled` | Flag di modulo spento (es. `group_classes`) |
| 500 | `error` | Errore generico (mai stack trace in produzione) |

---

## Paginazione

Formato Laravel standard (`data`, `links`, `meta`).  
Parametro `per_page`: intero 1–100, default 25. Validato via Form Request.  
Valori fuori range → 422 con `code: validation_failed`.

---

## Rate limiting

Driver: Redis (predis, già configurato).  
Valori configurabili in `config/api.php` o `.env`:

| Scenario | Default | ENV |
|---|---|---|
| Richiesta autenticata (by user ID) | 60 req/min | `API_RATE_LIMIT_AUTH` |
| Richiesta anonima (by IP) | 10 req/min | `API_RATE_LIMIT_ANON` |

Header di risposta: `X-RateLimit-Limit`, `X-RateLimit-Remaining`, `Retry-After` (su 429).

---

## Abilities dei token

Namespace separato dai gate role-based del backoffice. L'API non passa mai per gate che interrogano ruoli.  
Formato: `risorsa:azione` (es. `members:read`, `access-logs:write`).  
Wildcard `*` concede tutte le abilities (usare solo per token di test/staging).

| Ability | Endpoint | Note |
|---|---|---|
| `subscription-plans:read` | GET /subscription-plans | API02 |
| `members:read` | GET /members, GET /members/{id}, GET /members/{id}/subscription | API02 |
| `members:medical-read` | Aggiunge `medical_cert_expiry` + abilita filtro `cert_expiry_before` | API02, separato per minimizzare esposizione |
| `access-logs:read` | GET /access-logs | API02 |
| `exercises:read` | GET /exercises, GET /exercises/{slug} | API02 |
| `access-logs:write` | POST /access-logs | API03 |
| `group-classes:read` | GET /group-classes, GET /class-occurrences | API03 |
| `class-bookings:write` | POST/DELETE /class-bookings | API04 (futuro) |
| `subscriptions:write` | POST /subscriptions | API05 (futuro) |

---

## Endpoint disponibili (API01 + API02 + API03)

| Metodo | Path | Auth | Ability | Kill switch |
|---|---|---|---|---|
| GET | /api/v1/ping | No | — | Esente |
| GET | /api/v1/me | Bearer | — | Sì |
| GET | /api/v1/subscription-plans | Bearer | `subscription-plans:read` | Sì |
| GET | /api/v1/members | Bearer | `members:read` | Sì |
| GET | /api/v1/members/{id} | Bearer | `members:read` | Sì |
| GET | /api/v1/members/{id}/subscription | Bearer | `members:read` | Sì |
| GET | /api/v1/access-logs | Bearer | `access-logs:read` | Sì |
| POST | /api/v1/access-logs | Bearer | `access-logs:write` | Sì |
| GET | /api/v1/exercises | Bearer | `exercises:read` | Sì |
| GET | /api/v1/exercises/{slug} | Bearer | `exercises:read` | Sì |
| GET | /api/v1/group-classes | Bearer | `group-classes:read` | Sì + `group_classes` |
| GET | /api/v1/class-occurrences | Bearer | `group-classes:read` | Sì + `group_classes` |

---

*Documento aggiornato a ogni release API.*
