# .claude/ — indice

Risorse AI-specifiche per Claude Code. Non caricate automaticamente: richiedile
esplicitamente quando servono per non saturare il contesto.

## docs/domain/

Documentazione di dominio. Questa cartella è la **fonte autoritativa**: `docs/domain/` ne è la copia pubblica sincronizzata.

| File | Contenuto | Quando caricarlo |
|---|---|---|
| `step-0-discovery.md` | ERD completo, schema SQL, regole progressione MEV→MRV, personas | Prima di toccare schema DB, servizi di progressione, logica allenamento |
| `exercises-catalog.md` | 83 esercizi con tassonomia, contribution_pct, descrizioni esecuzione, sezione SQLite | Prima di modificare seed, catalogo, ExerciseObserver, WeeklyVolumeCalculator |
| `glossary.md` | Terminologia BB, tecniche speciali, tassonomia esercizi | Riferimento rapido; corto, OK caricarlo sempre |

## agents/

Definizioni agenti specializzati disponibili per Claude Code. Ogni file descrive
un agente con le sue competenze e i tool disponibili.

## scripts/

| Script | Uso |
|---|---|
| `build_exercises_sqlite.py` | Rigenera `database/database.sqlite` da `exercises_seed.sql`. Stdlib Python, nessuna dipendenza. |

## Note di sincronizzazione

`.claude/docs/domain/` è la fonte autoritativa; `docs/domain/` è la copia pubblica.
Se modifichi un file qui, aggiorna anche `docs/domain/` e registra la data.
Ultima sincronizzazione verificata: 2026-08-23 (DOC01 — tutti e tre i file allineati).
