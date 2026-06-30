# .claude/ — indice

Risorse AI-specifiche per Claude Code. Non caricate automaticamente: richiedile
esplicitamente quando servono per non saturare il contesto.

## docs/domain/

Documentazione di dominio. Sono copie sincronizzate di `docs/domain/` mantenute
qui per caricamento diretto da Claude Code.

| File | Contenuto | Quando caricarlo |
|---|---|---|
| `step-0-discovery.md` | ERD completo, schema SQL, regole progressione MEV→MRV, personas | Prima di toccare schema DB, servizi di progressione, logica allenamento |
| `exercises-catalog.md` | 83 esercizi con tassonomia, contribution_pct, descrizioni esecuzione | Prima di modificare seed, catalogo, ExerciseObserver, WeeklyVolumeCalculator |
| `glossary.md` | Terminologia BB, tecniche speciali, tassonomia esercizi | Riferimento rapido; corto, OK caricarlo sempre |

## agents/

Definizioni agenti specializzati disponibili per Claude Code. Ogni file descrive
un agente con le sue competenze e i tool disponibili.

## scripts/

| Script | Uso |
|---|---|
| `build_exercises_sqlite.py` | Rigenera `database/database.sqlite` da `exercises_seed.sql`. Stdlib Python, nessuna dipendenza. |

## Note di sincronizzazione

`docs/domain/` (cartella pubblica) e `.claude/docs/domain/` (questa cartella) devono
restare allineate. Ultima sincronizzazione: 2026-06-25. Se modifichi un file in una
delle due posizioni, aggiorna anche l'altra e registra la data qui.
