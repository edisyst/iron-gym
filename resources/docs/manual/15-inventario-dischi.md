# Inventario dischi e manubri

## A cosa serve

La pagina inventario attrezzatura (`/backoffice/admin/plate-inventory`)
gestisce i dischi del bilanciere e i manubri disponibili in palestra. Questi
dati alimentano il calcolatore di carico del bilanciere usato nell'app atleta
durante le sessioni: il sistema suggerisce la combinazione di dischi ottimale
per raggiungere il peso pianificato.

## Chi la vede

Solo il gestore ha accesso. Trainer e receptionist non hanno accesso.

## Dischi (PlateInventory)

La tabella dischi mostra tutti i pesi disponibili con il numero di coppie
(`quantity_pairs`), il colore (opzionale, utile per palestre con dischi
colorati olimpionici) e il flag `is_active`.

Per modificare un disco:
1. Clicca il pulsante matita nella riga del peso desiderato.
2. Aggiorna il numero di coppie, il colore e lo stato attivo/non attivo.
3. Salva.

Solo i dischi con `is_active = true` vengono usati dal calcolatore di carico.
Disattivare un disco non lo elimina: e' utile per segnalare attrezzatura
temporaneamente fuori uso.

## Manubri (DumbbellInventory)

La seconda tabella gestisce i manubri per peso. Come per i dischi, ogni riga
rappresenta un peso specifico con numero di coppie e stato attivo. La modifica
avviene con lo stesso pattern inline (matita → salva).

## Calcolatore di carico

Il `PlateLoadoutCalculator` usa la lista dei dischi attivi per suggerire la
combinazione di dischi per lato del bilanciere. L'algoritmo e' greedy
decrescente: parte dai dischi piu' pesanti disponibili e riempie fino al
target. Se la combinazione esatta non e' raggiungibile, usa la combinazione
piu' vicina per difetto.

## Errori comuni

**Il calcolatore suggerisce un peso inferiore a quello richiesto**: non esiste
una combinazione di dischi attivi che raggiunga esattamente il peso target.
Verifica il numero di coppie disponibili e lo stato `is_active` dei dischi.

**I manubri non appaiono nell'app atleta**: il calcolatore bilanciere e
l'inventario manubri sono due funzionalita' separate. L'inventario manubri
e' attualmente un registro interno; l'integrazione nell'app atleta e'
prevista in rilasci futuri.
