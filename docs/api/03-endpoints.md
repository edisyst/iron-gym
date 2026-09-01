# API02 — Endpoint di lettura (reference)

Tutti gli endpoint richiedono `Authorization: Bearer <token>` e sono soggetti al kill switch `public_api`.  
Formato risposta: JSON. Errori: `{message, code}` con `errors` aggiunto solo per 422.  
Paginazione standard Laravel: `data / links / meta`. Cap `per_page`: max 100, default 25.

---

## GET /api/v1/subscription-plans

**Ability:** `subscription-plans:read`

### Query string

| Parametro | Tipo | Note |
|---|---|---|
| `active` | boolean | Filtra per `is_active` |
| `per_page` | integer 1–100 | Default 25 |

### Risposta 200

```json
{
  "data": [
    {
      "id": 1,
      "name": "Mensile",
      "description": "...",
      "price_cents": 5000,
      "duration_days": 30,
      "max_accesses": null,
      "is_active": true
    }
  ],
  "links": { "..." },
  "meta": { "current_page": 1, "per_page": 25, "total": 3 }
}
```

---

## GET /api/v1/members

**Ability:** `members:read`

### Query string

| Parametro | Tipo | Note |
|---|---|---|
| `search` | string max 100 | Cerca su cognome, nome, email |
| `is_active` | boolean | Filtra per stato attivo |
| `cert_expiry_before` | date Y-m-d | Richiede anche `members:medical-read`; senza → 422 |
| `per_page` | integer 1–100 | Default 25 |

### Risposta 200

`medical_cert_expiry` compare solo se il token ha `members:medical-read`.

```json
{
  "data": [
    {
      "id": 42,
      "first_name": "Mario",
      "last_name": "Rossi",
      "email": "mario@example.com",
      "phone": "333000000",
      "is_active": true,
      "medical_cert_valid": true
    }
  ]
}
```

---

## GET /api/v1/members/{id}

**Ability:** `members:read`

### Risposta 200

Include oggetto `active_subscription` inline (null se assente). `medical_cert_expiry` condizionale come sopra.

```json
{
  "id": 42,
  "first_name": "Mario",
  "last_name": "Rossi",
  "email": "mario@example.com",
  "phone": "333000000",
  "is_active": true,
  "medical_cert_valid": true,
  "active_subscription": {
    "plan_id": 1,
    "plan_name": "Mensile",
    "status": "active",
    "started_at": "2026-08-01",
    "expires_at": "2026-08-31"
  }
}
```

---

## GET /api/v1/members/{id}/subscription

**Ability:** `members:read`

Restituisce l'abbonamento attivo del tesserato. 404 se nessun abbonamento attivo.

```json
{
  "id": 10,
  "plan_id": 1,
  "plan_name": "Mensile",
  "status": "active",
  "started_at": "2026-08-01",
  "expires_at": "2026-08-31",
  "accesses_used": 5,
  "accesses_remaining": null,
  "notes": null
}
```

---

## GET /api/v1/access-logs

**Ability:** `access-logs:read`

### Query string

| Parametro | Tipo | Note |
|---|---|---|
| `member_id` | integer | Filtra per tesserato |
| `date_from` | date Y-m-d | Inizio range (inclusivo) |
| `date_to` | date Y-m-d | Fine range (inclusivo); max 31 giorni da `date_from` → 422 se superato |
| `per_page` | integer 1–100 | Default 25 |

### Risposta 200

```json
{
  "data": [
    {
      "id": 1,
      "member_id": 42,
      "member_name": "Mario Rossi",
      "subscription_id": 10,
      "checked_in_at": "2026-08-15T09:30:00Z",
      "note": null
    }
  ]
}
```

---

## GET /api/v1/exercises

**Ability:** `exercises:read`

### Query string

| Parametro | Tipo | Valori |
|---|---|---|
| `muscle_slug` | string | Slug del muscolo |
| `equipment_slug` | string | Slug attrezzatura |
| `measurement_type` | enum | `reps_weight`, `reps_only`, `time`, `time_weight`, `isometric_hold` |
| `mechanic` | enum | `compound`, `isolation` |
| `per_page` | integer 1–100 | Default 25 |

### Risposta 200

```json
{
  "data": [
    {
      "id": 1,
      "slug": "squat",
      "name": "Squat",
      "measurement_type": "reps_weight",
      "mechanic": "compound",
      "skill_level": "beginner",
      "pattern": { "id": 2, "name": "Knee Dominant", "category": "compound_pattern" },
      "primary_muscles": [
        { "slug": "quadriceps", "name": "Quadricipiti", "contribution_pct": 60 }
      ]
    }
  ]
}
```

---

## GET /api/v1/exercises/{slug}

**Ability:** `exercises:read`

Route binding su `slug` (non `id`).

### Risposta 200

Aggiunge ai campi della lista: `description`, `execution_description`, `video_url`, `thumbnail_url`, `muscles` (tutti i ruoli), `equipment`.

```json
{
  "id": 1,
  "slug": "squat",
  "name": "Squat",
  "measurement_type": "reps_weight",
  "mechanic": "compound",
  "skill_level": "beginner",
  "description": "...",
  "execution_description": "...",
  "video_url": null,
  "thumbnail_url": null,
  "pattern": { "id": 2, "name": "Knee Dominant", "category": "compound_pattern" },
  "muscles": [
    { "slug": "quadriceps", "name": "Quadricipiti", "role": "primary", "contribution_pct": 60 }
  ],
  "equipment": [
    { "slug": "barbell", "name": "Bilanciere" }
  ]
}
```

---

---

## POST /api/v1/access-logs

**Ability:** `access-logs:write`

Registra un accesso (check-in). Idempotente entro la finestra configurata
(`config/api.checkin_idempotency_window_minutes`, default 5 minuti).

### Body JSON

| Campo | Tipo | Note |
|---|---|---|
| `member_id` | integer, required | ID del tesserato |
| `note` | string max 255, opzionale | Nota libera |

### Risposte

**201 Created** — accesso registrato; header `Location: /api/v1/access-logs/{id}`.

```json
{
  "data": {
    "id": 101,
    "member_id": 42,
    "member_name": "Mario Rossi",
    "subscription_id": 10,
    "checked_in_at": "2026-09-01T09:30:00Z",
    "note": null
  }
}
```

**200 OK** — duplicato entro finestra (stesso `member_id`, `checked_in_at` entro N min);
restituisce il log già esistente, nessun decremento aggiuntivo.

**404** — tesserato non trovato o soft-deleted: `{ "code": "not_found" }`.

**422** — fallimento di dominio; ciascuno ha un `code` stabile:

| `code` | Causa |
|---|---|
| `cert_invalid` | Certificato medico scaduto o mancante |
| `subscription_inactive` | Nessun abbonamento attivo |
| `accesses_exhausted` | Accessi residui esauriti |
| `validation_failed` | `member_id` assente o non intero (+ `errors`) |

---

## GET /api/v1/group-classes

**Ability:** `group-classes:read`  
**Modulo:** richiede flag `group_classes` → 503 `module_disabled` se spento.

### Query string

| Parametro | Tipo | Note |
|---|---|---|
| `per_page` | integer 1–100 | Default 25 |

### Risposta 200

```json
{
  "data": [
    {
      "id": 1,
      "slug": "yoga",
      "name": "Yoga",
      "description": "Corso di yoga per tutti i livelli.",
      "duration_minutes": 60,
      "default_capacity": 20,
      "room": "Sala A",
      "color": "#A8D8A8",
      "is_active": true
    }
  ],
  "links": { "..." },
  "meta": { "current_page": 1, "per_page": 25, "total": 5 }
}
```

---

## GET /api/v1/class-occurrences

**Ability:** `group-classes:read`  
**Modulo:** richiede flag `group_classes` → 503 `module_disabled` se spento.

Restituisce solo occorrenze con `status = planned` e `date >= date_from` (default oggi).

### Query string

| Parametro | Tipo | Note |
|---|---|---|
| `date_from` | date Y-m-d | Default: oggi |
| `date_to` | date Y-m-d | Max 31 giorni da `date_from` → 422 se superato |
| `group_class_id` | integer | Filtra per corso |
| `per_page` | integer 1–100 | Default 25 |

### Risposta 200

```json
{
  "data": [
    {
      "id": 55,
      "group_class_id": 1,
      "group_class_name": "Yoga",
      "date": "2026-09-05",
      "start_time": "10:00",
      "end_time": "11:00",
      "capacity": 20,
      "available_spots": 17,
      "status": "planned",
      "trainer_id": 3,
      "trainer_name": "Laura Bianchi"
    }
  ],
  "links": { "..." },
  "meta": { "current_page": 1, "per_page": 25, "total": 12 }
}
```

---

## GET /api/v1/class-bookings

**Ability:** `class-bookings:read`  
**Modulo:** richiede flag `group_classes` → 503 `module_disabled` se spento.

### Query string

| Parametro | Tipo | Note |
|---|---|---|
| `member_id` | integer | Filtra per tesserato |
| `occurrence_id` | integer | Filtra per occorrenza |
| `status` | enum | `confirmed`, `waitlisted`, `cancelled_by_athlete`, `cancelled_by_gym`, `no_show` |
| `per_page` | integer 1–100 | Default 25 |

### Risposta 200

```json
{
  "data": [
    {
      "id": 201,
      "class_occurrence_id": 55,
      "member_id": 42,
      "member_name": "Mario Rossi",
      "status": "confirmed",
      "position": null,
      "attended_at": null,
      "created_at": "2026-09-01T08:00:00Z"
    }
  ],
  "links": { "..." },
  "meta": { "current_page": 1, "per_page": 25, "total": 8 }
}
```

---

## POST /api/v1/class-bookings

**Ability:** `class-bookings:write`  
**Modulo:** richiede flag `group_classes` → 503 `module_disabled` se spento.

Iscrive un tesserato a un'occorrenza. Se l'occorrenza è piena lo mette in waitlist.  
Idempotente: se il membro è già iscritto (confirmed/waitlisted) restituisce 200 con la prenotazione esistente.

### Body JSON

| Campo | Tipo | Note |
|---|---|---|
| `member_id` | integer, required | ID del tesserato |
| `class_occurrence_id` | integer, required | ID dell'occorrenza |

### Risposte

**201 Created** — nuova iscrizione; header `Location: /api/v1/class-bookings/{id}`.

```json
{
  "data": {
    "id": 201,
    "class_occurrence_id": 55,
    "member_id": 42,
    "member_name": "Mario Rossi",
    "status": "confirmed",
    "position": null,
    "attended_at": null,
    "created_at": "2026-09-01T08:00:00Z"
  }
}
```

**200 OK** — membro già iscritto (idempotente); restituisce la prenotazione esistente.

**404** — tesserato o occorrenza non trovata: `{ "code": "not_found" }`.

**422** — fallimento di dominio:

| `code` | Causa |
|---|---|
| `booking_not_open` | Finestra prenotazioni non ancora aperta |
| `booking_closed` | Finestra prenotazioni chiusa |
| `occurrence_not_enrollable` | Occorrenza non in stato `planned` |
| `subscription_inactive` | Nessun abbonamento attivo |
| `cert_invalid` | Certificato medico scaduto o mancante |

**409** — conflitto:

| `code` | Causa |
|---|---|
| `athlete_overlap` | Membro ha già un corso confermato nello stesso orario |
| `pt_overlap` | Membro ha già una sessione PT confermata nello stesso orario |

---

## DELETE /api/v1/class-bookings/{booking}

**Ability:** `class-bookings:write`  
**Modulo:** richiede flag `group_classes` → 503 `module_disabled` se spento.

Cancella la prenotazione (cancellazione atleta, verifica deadline gratuita).  
Se la prenotazione era `confirmed` e il corso è futuro, promuove automaticamente il primo in waitlist.

### Risposte

**204 No Content** — cancellazione eseguita.

**404** — prenotazione non trovata.

**409** — impossibile cancellare:

| `code` | Causa |
|---|---|
| `cancel_deadline_exceeded` | Cancellazione oltre la deadline gratuita (`free_cancel_hours`) |
| `booking_not_cancellable` | Prenotazione in stato non cancellabile (es. `no_show`) |

---

## Codici di errore comuni

| HTTP | `code` | Causa |
|---|---|---|
| 401 | `unauthenticated` | Token assente o revocato |
| 403 | `forbidden` | Token privo dell'ability richiesta |
| 404 | `not_found` | Risorsa inesistente |
| 422 | `validation_failed` | Parametri non validi (+ `errors`) |
| 429 | `rate_limited` | Superato limite richieste |
| 503 | `api_disabled` | Kill switch `public_api` spento |
| 503 | `module_disabled` | Flag di modulo spento (`group_classes`) |
