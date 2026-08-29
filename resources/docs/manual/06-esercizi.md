# Esercizi e catalogo

## A cosa serve

La libreria esercizi (`/backoffice/exercises`) contiene il catalogo completo
degli esercizi disponibili per costruire le schede di allenamento. Ogni esercizio
e' classificato per pattern motorio, muscoli coinvolti (con ruolo e percentuale
di contribuzione), attrezzatura, meccanica e livello di competenza richiesto.
La libreria e' condivisa tra tutti i template e i mesocicli della palestra.

## Chi la vede

La lista esercizi e' accessibile a gestore e trainer. Il receptionist non ha
accesso. La creazione e la modifica degli esercizi sono aperte a entrambi i
ruoli. L'eliminazione e' riservata al solo gestore.

## Filtri disponibili

La lista supporta i seguenti filtri combinabili:

- **Ricerca per nome**: filtra per nome italiano dell'esercizio (ricerca
  parziale, case-insensitive).
- **Gruppo muscolare**: filtra per il gruppo del muscolo primario
  (ad esempio petto, schiena, gambe). Solo i muscoli con ruolo `primary`
  vengono considerati.
- **Meccanica**: filtra per il tipo di meccanica (`compound`, `isolation`,
  `carry`, ecc.).
- **Livello**: filtra per `skill_level` (`beginner`, `intermediate`,
  `advanced`).
- **Attrezzatura**: filtraggio multiplo, permette di selezionare piu'
  attrezzature contemporaneamente.

I filtri si combinano con AND: ogni filtro aggiuntivo restringe ulteriormente
i risultati. La lista e' paginata a 20 elementi per pagina.

## Dettaglio esercizio

Cliccando il nome di un esercizio si apre la pagina di dettaglio. Mostra:
descrizione dell'esecuzione, muscoli coinvolti con ruolo e percentuale di
contribuzione, attrezzatura, pattern motorio, meccanica e livello.

## Creare un nuovo esercizio

1. Vai alla libreria esercizi e clicca **Nuovo esercizio**.
2. Compila i campi obbligatori: nome italiano, pattern motorio (o azione
   articolare), meccanica, livello, tipo di misurazione (`weight_reps`,
   `bodyweight_reps`, `time`, `distance`).
3. Aggiungi almeno un muscolo con ruolo `primary`. Puoi aggiungere muscoli
   secondari con ruolo `secondary` e impostare la percentuale di contribuzione
   per ciascuno.
4. Aggiungi l'attrezzatura necessaria.
5. Salva.

**Vincolo architetturale**: ogni esercizio deve avere esattamente uno tra
`compound_pattern_id` e `joint_action_id` valorizzato, non entrambi. Il form
impone questo vincolo: selezionando un pattern composito si disabilita il
campo azione articolare e viceversa.

## Eliminare un esercizio (solo gestore)

Il pulsante elimina e' disponibile nella riga della lista. L'eliminazione e'
un soft-delete: l'esercizio non compare piu' nella libreria ma rimane nei
mesocicli e nelle sessioni storiche gia' assegnate.

## Cache esercizi

La lista esercizi usa una cache con tag `exercises`. La cache viene invalidata
automaticamente ogni volta che si crea, modifica o elimina un esercizio.
In caso di comportamento anomalo dopo una modifica, svuotare la cache con
`php artisan cache:clear` dall'ambiente di sviluppo.

## Errori comuni

**Il filtro per gruppo muscolare non trova l'esercizio atteso**: il filtro
considera solo i muscoli con ruolo `primary`. Se il muscolo e' associato
all'esercizio con ruolo `secondary`, non sara' incluso nel risultato. Apri
il dettaglio dell'esercizio e verifica il ruolo assegnato.

**Impossibile eliminare: errore di integrita' referenziale**: se l'esercizio
e' usato in sessioni attive o in template, il sistema potrebbe impedire
l'eliminazione a seconda delle vincoli del database. Usa il soft-delete
(il bottone standard elimina) invece di tentare eliminazioni manuali da
database.

**Il form rifiuta il salvataggio con "pattern motorio richiesto"**: ogni
esercizio deve avere o `compound_pattern_id` o `joint_action_id`. Seleziona
uno dei due prima di salvare.
