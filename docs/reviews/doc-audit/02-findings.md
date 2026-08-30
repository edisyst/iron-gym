# DOC01 — Diff documentazione vs codice

**Data:** 2026-08-23
**Fase:** 3 di 6 — READ-ONLY
**Input:** `00-inventory.md`, `01-codebase-snapshot.md` + lettura diretta di ogni file nel perimetro.
**Escluso:** `docs/test/` (fase 5).

Legenda severità: **[CRITICO]** — informazione sbagliata che porta a codice errato; **[MEDIO]** — divergenza che causa confusione; **[BASSO]** — gap o incompletezza; **[ARCHIVIARE]** — snapshot storico, non aggiornare.

---

## CLAUDE.md

**Stato sintetico:** da correggere (2 finding minori)

Finding:
- [BASSO] `DumbbellInventory` model esiste (`app/Models/DumbbellInventory.php`) ed è usato attivamente da `PlateInventoryManager` (`Admin/PlateInventoryManager.php:5,63,79,80,103,105`): renderizza sia dischi bilanciere (`PlateInventory`) sia manubri (`DumbbellInventory`) nello stesso componente. Nessuna menzione in "Dominio — entità principali", nella tabella entità o nei servizi. | Evidence: `app/Livewire/Backoffice/Admin/PlateInventoryManager.php`
- [BASSO] Sezione "Stato sviluppo" — accumulo storico release-per-release ormai molto lungo. Conteggio finale ("Suite 220/226") coincide con `suite.txt` ✓. Tutta la narrativa storica è la sorgente per CHANGELOG.md (fase 6).

Azione proposta: aggiungere `DumbbellInventory` come nota a piè della sezione entità; la sezione "Stato sviluppo" viene compressa in fase 6.

---

## docs/architecture/component-map.md

**Stato sintetico:** da correggere (4 finding)

Finding:
- [CRITICO] Riga 31: URL `backoffice.mesocycles.show` documentato come `/backoffice/mesocycles/{mesocycle}` → `routes.json` riporta `/backoffice/mesocycles/{mesocycleId}` (parametro diverso). Chi scrive codice con il param sbagliato ottiene un 500. | Evidence: `.tmp/doc-audit/routes.json`
- [MEDIO] Riga 106: `PlateInventoryManager` elencato sotto namespace `PlateInventory` → file reale è `app/Livewire/Backoffice/Admin/PlateInventoryManager.php` (namespace `Admin`). Il componente gestisce anche `DumbbellInventory`, non solo dischi bilanciere. | Evidence: `app/Livewire/Backoffice/Admin/PlateInventoryManager.php:1`
- [MEDIO] Riga 135 (sezione atleta): `Athlete\Progress` elencato come componente embedded in TrainingHub → file `app/Livewire/Athlete/Progress.php` non esiste (rimosso in HK01). | Evidence: `ls app/Livewire/Athlete/`
- [BASSO] Tabella seeder: elenca 6 su 16 seeders reali. Mancano: `ActiveMesocycleSeeder`, `BookingDemoSeeder`, `CommunicationTemplateSeeder`, `DatabaseSeeder`, `DemoTemplatesSeeder`, `DumbbellInventorySeeder`, `ExerciseDescriptionSeeder`, `OpeningHoursSeeder`, `ProgressDemoSeeder`, `TrainingHistorySeeder`. | Evidence: `.tmp/doc-audit/seeders.txt`

Azione proposta: fix riga 31 (`{mesocycle}` → `{mesocycleId}`); fix riga 106 (namespace `Admin`, aggiungere nota su DumbbellInventory); rimuovere riga `Athlete\Progress`; aggiungere i seeder mancanti (solo quelli non di sviluppo puro, vedi decisioni).

---

## docs/architecture/ui-atleta.md

**Stato sintetico:** allineato ✓

Finding: nessuno.

Verifiche eseguite:
- 9 componenti Blade `x-athlete.*` documentati = 9 file in `resources/views/components/athlete/` ✓
- Token CSS verificati con grep su `public/css/athlete.css` (228 occorrenze) ✓
- `session-recap.css` documentato esiste in `public/css/session-recap.css` ✓
- Sezione "Toggle viewport" coincide con implementazione (script `local`-only, badge fisso, localStorage) ✓
- Alpine stores (`messages`, `restTimer`, `syncQueue`) documentati correttamente ✓

---

## docs/architecture/body-map-svg.md

**Stato sintetico:** allineato ✓

Finding: nessuno.

Verifiche eseguite:
- File referenziato `resources/views/livewire/athlete/partials/body-map.blade.php` esiste ✓
- 25 slug muscolo elencati; `transverse_abdominis` correttamente marcato come "non rappresentato" ✓
- Classi `intensity-{0..5}` usano i colori che coincidono con token `--ig-intensity-*` in `athlete.css` ✓

---

## .claude/docs/domain/glossary.md

**Stato sintetico:** da correggere (1 finding critico)

Finding:
- [CRITICO] Riga 28: "appartiene a un microciclo (tabella `sessions`)" → la tabella `sessions` esiste ma è la tabella delle **sessioni HTTP** di Laravel (driver session DB). La tabella delle sessioni di allenamento è `training_sessions`. La versione `docs/domain/glossary.md` ha già la correzione corretta. | Evidence: `.tmp/doc-audit/tables.txt` (entrambe le tabelle presenti), `docs/domain/glossary.md:28`

Azione proposta: correggere riga 28 in `.claude/docs/domain/glossary.md`: `sessions` → `training_sessions`. Poi allineare `docs/domain/glossary.md` come da policy (`.claude/` autoritativa, ma qui ironicamente `docs/` è già corretta).

---

## .claude/docs/README.md

**Stato sintetico:** da correggere (2 finding)

Finding:
- [MEDIO] Riga 8-9 e nota sincronizzazione: descrive `.claude/docs/domain/` come "copie sincronizzate di `docs/domain/`" → CLAUDE.md dichiara il contrario (`.claude/docs/domain/` è la **fonte autoritativa**; `docs/domain/` ne è la copia). La direzione è invertita. | Evidence: CLAUDE.md sezione "Documenti di dominio"
- [MEDIO] "Ultima sincronizzazione: 2026-06-25" → i tre file divergono (diff in fase 1: exercises-catalog 24 righe, glossary 4 righe — quella critica `sessions` vs `training_sessions`, step-0 306 righe). Nessuna sincronizzazione è avvenuta da quella data. | Evidence: diff riportato in `00-inventory.md`

Azione proposta: riscrivere la nota di sincronizzazione con direzione corretta (`.claude/` è la fonte), aggiornare data dopo allineamento in fase 4.

---

## .claude/docs/domain/exercises-catalog.md

**Stato sintetico:** allineato ✓

Finding: nessuno.

Verifiche eseguite (SQLite):
- exercises: 83 ✓ | muscles: 26 ✓ | movement_patterns: 27 ✓ | exercise_muscle: 259 ✓ | equipment: 14 ✓ | exercise_equipment: 108 ✓
- `execution_description` valorizzata su tutti e 83 esercizi ✓
- Sezione "Dati di riferimento (SQLite)" presente in `.claude/`, assente in `docs/` (gap nella copia) ✓

---

## .claude/docs/domain/step-0-discovery.md

**Stato sintetico:** allineato ✓

Finding: nessuno. Schema SQL completo presente; è la versione autoritativa.

---

## docs/domain/exercises-catalog.md

**Stato sintetico:** da correggere (1 finding basso)

Finding:
- [BASSO] Manca la sezione "Dati di riferimento (SQLite)" presente in `.claude/docs/domain/exercises-catalog.md`. Non è un errore critico (il testo in CLAUDE.md copre lo stesso concetto) ma crea un'asimmetria rispetto alla fonte autoritativa. | Evidence: diff 24 righe riportato in fase 1

Azione proposta: allineare `docs/domain/exercises-catalog.md` a `.claude/docs/domain/exercises-catalog.md` dopo aver corretto quest'ultima.

---

## docs/domain/glossary.md

**Stato sintetico:** allineato ✓

Nota: riga 28 usa `training_sessions` (corretto). Questa versione è **più accurata** della `.claude/` su questo punto specifico.

---

## docs/domain/step-0-discovery.md

**Stato sintetico:** allineato ✓ (schema SQL rimosso intenzionalmente con nota)

Finding: nessuno. Il taglio è documentato con `> **Rimosso.**` — scelta coerente con la policy (SQL completo nella versione `.claude/`).

---

## docs/installation.md

**Stato sintetico:** da correggere (1 finding basso)

Finding:
- [BASSO] Tabella "Account di default" elenca solo gestore, trainer, receptionist. Manca l'utente atleta creato da `DemoSeeder` (email `alessia.colombo@example.com`, password `atleta`). L'utente atleta è quello con mesociclo attivo usato dai test (`docs/test/04-atleta.md`). | Evidence: `docs/test/04-atleta.md:3` ("Credenziali con mesociclo attivo: alessia.colombo@example.com / atleta")

Azione proposta: aggiungere riga atleta nella tabella account.

---

## docs/devops/go-live-checklist.md

**Stato sintetico:** da correggere (2 finding)

Finding:
- [BASSO] Sezione "Roll-out graduale" elenca i flag `periodization_engine`, `push_notifications`, `group_classes` ma omette `financial_reports`. Anche `financial_reports` è un feature flag Pennant che deve essere attivato esplicitamente per il gestore in produzione. | Evidence: `AppServiceProvider.php:61` (`Feature::define('financial_reports', ...)`)
- [BASSO] Riga 7: lista GitHub Secrets include `STAGING_HOST`, `STAGING_USER`, `STAGING_KEY` → il pipeline CI ha la fase di deploy staging commentata (commit `4a3f1c9 ci: commenta deploy-staging (nessun server staging configurato)`). I secret staging non sono necessari. | Evidence: git log, `.github/workflows/`

Azione proposta: aggiungere `financial_reports` nella lista flag; sostituire la riga staging con una nota "server staging non ancora configurato".

---

## docs/review/audit-codice.md

**Stato sintetico:** da archiviare

Snapshot storico (2026-06-28, audit v2). I finding sono stati applicati. Mantenere come storico, non aggiornare.

---

## docs/review/audit-grafica.md

**Stato sintetico:** da archiviare

Snapshot storico (2026-06-28, audit grafico). Mantenere come storico, non aggiornare.

---

## docs/review/audit-receptionist-2026-08-19.md

**Stato sintetico:** da archiviare

Snapshot storico (2026-08-19). Mantenere come storico, non aggiornare.

---

## docs/review/test-per-ruolo.md

**Stato sintetico:** da correggere (1 finding medio)

Finding:
- [MEDIO] Intestazione: "Suite: **177 test** (171 pass + 6 skip)" → suite reale è 220 pass + 6 skip (226 totali). La tabella in fondo riporta 177 test totali — 43 test aggiunti dopo R05–R08, UX01–UX07 e HK01 non sono tracciati. | Evidence: `.tmp/doc-audit/suite.txt`

Azione proposta: aggiornare conteggio (226 totali, 220 pass + 6 skip) e aggiungere i test mancanti alla tabella per ruolo. **Vedi decisioni #2** per valutare se questo file ha ancora un ruolo distinto da `docs/test/`.

---

## docs/reviews/ui-atleta-audit-2026-07-05.md

**Stato sintetico:** da archiviare

Snapshot storico (pre-UX02). Mantenere, non aggiornare.

---

## docs/reviews/ui-atleta-ergonomia-2026-07-05.md

**Stato sintetico:** da archiviare

Snapshot storico (post-UX02–UX05, 2026-07-05). Mantenere, non aggiornare.

---

## docs/reviews/ui-atleta-ergonomia-2026-07-06.md

**Stato sintetico:** da archiviare

Snapshot storico (UX05-B, 2026-07-06). Mantenere, non aggiornare.

---

## docs/reviews/ui-atleta-funzionale-2026-08-18.md

**Stato sintetico:** da archiviare

Snapshot storico (2026-08-18). Mantenere, non aggiornare.

---

## docs/README.md

**Stato sintetico:** da correggere (1 finding basso)

Finding:
- [BASSO] Indice non elenca: `docs/audit/hk01-report.md`, `docs/audit/hk01-report-v2.md`, `docs/review/audit-receptionist-2026-08-19.md`, `docs/reviews/ui-atleta-ergonomia-2026-07-05.md`, `docs/reviews/ui-atleta-funzionale-2026-08-18.md`. Link verificati con `test -f` — esistono tutti. | Evidence: `00-inventory.md`, `.tmp/doc-audit/md-files.txt`

Azione proposta: aggiungere le voci mancanti nella sezione "Review e audit".

---

## STRUTTURA.md (assente)

**Stato sintetico:** da chiarire

`STRUTTURA.md` citato nel prompt DOC01 come documento esistente — non esiste nel filesystem (confermato in fase 1). Non referenziato da `docs/README.md`. Citato solo in `docs/audit/hk01-report.md:85` come voce di dubbio già nota.

Azione proposta: vedi decisioni #4.

---

## CHANGELOG.md

**Stato sintetico:** allineato ✓

812 righe; storico da Step 01 a HK01. Sarà integrato con la narrativa da CLAUDE.md in fase 6.

---

## Piano di intervento

Ordine di esecuzione suggerito per fase 4 (escluso `docs/test/` che è fase 5):

| Priorità | File | Operazione |
|---|---|---|
| 1 | `.claude/docs/domain/glossary.md` | Correggere riga 28: `sessions` → `training_sessions` |
| 2 | `docs/domain/glossary.md` | Allineare a `.claude/` dopo correzione (o verificare che sia già OK — lo è) |
| 3 | `docs/architecture/component-map.md` | Fix: `{mesocycle}` → `{mesocycleId}`; namespace `PlateInventory` → `Admin`; rimuovere `Athlete\Progress`; aggiungere seeder mancanti (vedere decisioni) |
| 4 | `.claude/docs/README.md` | Invertire direzione sincronizzazione; aggiornare data e stato divergenze |
| 5 | `docs/domain/exercises-catalog.md` | Aggiungere sezione "Dati di riferimento (SQLite)" da `.claude/` |
| 6 | `docs/installation.md` | Aggiungere riga utente atleta nella tabella account |
| 7 | `docs/devops/go-live-checklist.md` | Aggiungere flag `financial_reports`; aggiornare nota staging |
| 8 | `docs/review/test-per-ruolo.md` | Aggiornare conteggio suite (dopo decisione #2) |
| 9 | `docs/README.md` | Aggiungere voci mancanti nella sezione "Review e audit" |
| 10 | `CLAUDE.md` | Aggiungere nota `DumbbellInventory` (fare in fase 6 insieme alla compressione "Stato sviluppo") |

---

## Decisioni che richiedono conferma

**#1 — DumbbellInventory come entità di dominio**
Il modello esiste e `PlateInventoryManager` lo usa per mostrare l'inventario manubri accanto ai dischi. CLAUDE.md non lo menziona tra le entità. Opzioni:
- (a) Aggiungere come entità secondaria sotto "Tracking e analytics" con una riga
- (b) Lasciarlo non documentato (implementazione interna, non esposta in aree critiche)

**#2 — docs/review/test-per-ruolo.md: aggiornare o archiviare**
Il file mappa i test Pest automatici per ruolo. `docs/test/` ha i piani di test manuali. Sono complementari ma il file è datato (177 → 220 test). Opzioni:
- (a) Aggiornare conteggio e tabella con i test nuovi
- (b) Archiviarlo (è uno snapshot utile ma ora incompleto) — i test attuali sono leggibili con `./vendor/bin/pest --list`

**#3 — Seeder table in component-map.md: quanti aggiungere**
Attualmente lista 6/16 seeders. I demo-seeder sono già descritti implicitamente da `DemoSeeder`. Opzioni:
- (a) Aggiungere tutti i 16 per completezza
- (b) Aggiungere solo `ExerciseDescriptionSeeder`, `CommunicationTemplateSeeder`, `OpeningHoursSeeder` (rilevanti per feature principali)

**#4 — STRUTTURA.md: creare o ignorare**
`STRUTTURA.md` non esiste. Il prompt DOC01 lo presuppone come indice di struttura del repo. Opzioni:
- (a) Crearlo in fase 6 (albero directory + data, come previsto dal prompt)
- (b) Non crearlo — `docs/README.md` già funge da indice documentale; una struttura directory non aggiunge molto

**#5 — GitHub Secrets staging nella go-live checklist**
Il deploy staging è commentato in CI. Opzioni:
- (a) Rimuovere la riga staging dalla checklist
- (b) Mantenere la riga con nota "non ancora configurato (staging futuro)"

---

## Finding sul codice emersi durante DOC01

Nessun finding sul codice identificato durante questa fase. Le divergenze trovate riguardano esclusivamente la documentazione.

---

*Generato da DOC01 fase 3 — 2026-08-23*

---

## Chiusura DOC01 (fase 6 — 2026-08-23)

**Finding totali applicati:**
- CRITICO risolti: 2 (glossary tabella sessions, direzione sync .claude/ vs docs/)
- MEDIO risolti: 7 (URL inesistenti docs/test/, feedback URL atleta, modifica membro receptionist)
- BASSO risolti: 13 (URL sbagliati, sezioni mancanti, nav labels, DumbbellInventory CLAUDE.md)
- DA ARCHIVIARE: 6 (dated reports — non modificati per design)

**File modificati in Fase 4 (architettura/dominio):** 8 file, 6 commit.  
**File modificati in Fase 5 (docs/test/):** 4 file, 5 commit.  
**File modificati in Fase 6 (CLAUDE.md, CHANGELOG.md, docs/README.md):** 3 file, 1 commit.

**Stato finale:** PHPStan 0 errori, Pint conforme, suite 220/226 (6 skip pre-esistenti invariati).
