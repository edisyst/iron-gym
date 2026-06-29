# Test per tipologia utente — iron-gym

Suite: **106 test** in 29 file. Organizzati per ruolo/contesto di esecuzione.

---

## Atleta

| File | Test |
|---|---|
| `SmokeTest` | la dashboard atleta risponde 200 per atleta autenticato |
| `AthleteHistoryTest` | l'atleta non può accedere al profilo backoffice |
| `WorkoutSessionTest` | il completamento del primo set porta la sessione in in_progress |
| `WorkoutSessionTest` | il completamento di tutti i set working abilita il completamento sessione |
| `WorkoutSessionTest` | il feedback post-sessione viene salvato correttamente |
| `BodyMeasurementTest` | una misurazione corporea viene salvata correttamente |
| `BodyMeasurementTest` | l'atleta non può vedere le misurazioni di un altro atleta |
| `QueryCountTest` | WorkoutSession carica sessione completa in <= 5 query |
| `MesocycleInstantiationTest` | l'istanziamento crea il numero corretto di settimane |
| `MesocycleInstantiationTest` | l'istanziamento segna l'ultima settimana come deload |
| `MesocycleInstantiationTest` | l'istanziamento crea le sessioni nelle settimane giuste |
| `MesocycleInstantiationTest` | l'istanziamento crea le sessioni con la scheduled_date corretta |
| `MesocycleInstantiationTest` | l'istanziamento crea i set con i parametri planned corretti |
| `TrainingFlowTest` | flusso training completo: instantiate → log → volume → progressione |

---

## Trainer

| File | Test |
|---|---|
| `SmokeTest` | la homepage del backoffice risponde 200 per utente autenticato |
| `AthleteHistoryTest` | il trainer vede lo storico sessioni di un suo atleta |
| `AthleteHistoryTest` | il trainer ottiene 403 sul profilo di un atleta non suo |
| `AthleteHistoryTest` | il gestore vede lo storico di qualsiasi atleta *(anche gestore)* |
| `ExerciseDetailPageTest` | risponde 200 per un esercizio esistente tramite slug |
| `ExerciseDetailPageTest` | risponde 404 per uno slug inesistente |
| `ExerciseDetailPageTest` | la view contiene il nome dell'esercizio |
| `ExerciseDetailPageTest` | la view contiene almeno un muscolo primary |
| `WorkoutBuilderTest` | un template viene creato con le settimane corrette |
| `WorkoutBuilderTest` | aggiungere un esercizio a una template session lo persiste |
| `WorkoutBuilderTest` | il riordino degli esercizi aggiorna order_in_session |
| `WorkoutBuilderTest` | due esercizi possono essere raggruppati in superset |
| `MemberFormTest` | il trainer può aggiornare un tesserato |
| `TrainingFlowTest` | flusso training completo: instantiate → log → volume → progressione |

---

## Gestore

| File | Test |
|---|---|
| `SmokeTest` | la homepage del backoffice risponde 200 per utente autenticato |
| `AthleteHistoryTest` | il gestore vede lo storico di qualsiasi atleta |
| `MemberFormTest` | il gestore crea un tesserato senza account |
| `MemberFormTest` | il gestore crea un tesserato con account atleta |
| `MemberFormTest` | account creato ha email_verified_at impostato |
| `MemberFormTest` | create_account false non crea User |
| `MemberFormTest` | create_account true richiede password minimo 8 caratteri |
| `MemberFormTest` | email duplicata in members viene rifiutata |
| `MemberFormTest` | first_name e last_name sono obbligatori |
| `MemberFormTest` | update non crea account anche se create_account era true |
| `AuthenticationTest` | test_navigation_menu_can_be_rendered |

---

## Receptionist

| File | Test |
|---|---|
| `MemberFormTest` | il receptionist non può aggiornare un tesserato |

---

## Guest (non autenticato)

| File | Test |
|---|---|
| `SmokeTest` | l'endpoint health risponde 200 |
| `SmokeTest` | il seed esercizi ha caricato 83 esercizi |
| `SmokeTest` | i 27 movement pattern sono presenti |
| `SmokeTest` | i quattro ruoli spatie esistono |
| `AuthenticationTest` | test_login_screen_can_be_rendered |
| `AuthenticationTest` | test_users_can_authenticate_using_the_login_screen |
| `AuthenticationTest` | test_users_can_not_authenticate_with_invalid_password |
| `RegistrationTest` | test_registration_screen_can_be_rendered |
| `RegistrationTest` | test_new_users_can_register |
| `PasswordResetTest` | test_reset_password_link_screen_can_be_rendered |
| `PasswordResetTest` | test_reset_password_link_can_be_requested |
| `PasswordResetTest` | test_reset_password_screen_can_be_rendered |
| `PasswordResetTest` | test_password_can_be_reset_with_valid_token |

---

## Utente autenticato (ruolo non specifico)

| File | Test |
|---|---|
| `AuthenticationTest` | test_users_can_logout |
| `QueryCountTest` | MemberList non genera N+1 su 15 membri con subscription |
| `PasswordUpdateTest` | test_password_can_be_updated |
| `PasswordUpdateTest` | test_correct_password_must_be_provided_to_update_password |
| `PasswordConfirmationTest` | test_confirm_password_screen_can_be_rendered |
| `PasswordConfirmationTest` | test_password_can_be_confirmed |
| `PasswordConfirmationTest` | test_password_is_not_confirmed_with_invalid_password |
| `ProfileTest` | test_profile_page_is_displayed |
| `ProfileTest` | test_profile_information_can_be_updated |
| `ProfileTest` | test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged |
| `ProfileTest` | test_user_can_delete_their_account |
| `ProfileTest` | test_correct_password_must_be_provided_to_delete_account |

---

## Email non verificata

| File | Test |
|---|---|
| `EmailVerificationTest` | test_email_verification_screen_can_be_rendered |
| `EmailVerificationTest` | test_email_can_be_verified |
| `EmailVerificationTest` | test_email_is_not_verified_with_invalid_hash |

---

## Unit / Service (nessun utente — test puri di logica)

| File | Test |
|---|---|
| `DeloadEvaluatorTest` | il deload è suggerito se MRV è raggiunto per due muscoli principali |
| `DeloadEvaluatorTest` | il deload è suggerito con joint pain persistente su due settimane |
| `DeloadEvaluatorTest` | deload suggerito per RIR drift su 3 set consecutivi |
| `DeloadEvaluatorTest` | deload suggerito per fine programmata mesociclo |
| `DeloadEvaluatorTest` | nessun deload se tutti i segnali sono nella norma |
| `WeeklyProgressionServiceTest` | la progressione aggiunge un set se il feedback è positivo e si è sotto MRV |
| `WeeklyProgressionServiceTest` | la progressione mantiene il volume se due o più metriche peggiorano |
| `WeeklyProgressionServiceTest` | la settimana deload dimezza il volume |
| `WeeklyVolumeCalculatorTest` | un set di panca piana contribuisce 0.60 hard set al pettorale sternale |
| `WeeklyVolumeCalculatorTest` | i set warmup non vengono contati nel volume |
| `WeeklyVolumeCalculatorTest` | il volume è zero se non ci sono sessioni completed |
| `QueryCountTest` | WeeklyVolumeCalculator esegue <= 5 query con 30 set |
| `MemberTest` | un tesserato può essere creato con dati validi |
| `MemberTest` | un tesserato con email duplicata viene rifiutato |
| `MemberTest` | la registrazione accesso incrementa accesses_used sull'abbonamento |
| `MemberTest` | la registrazione accesso fallisce se non c'è abbonamento attivo |
| `NotificationTest` | un membro con certificato in scadenza riceve la notifica |
| `NotificationTest` | un membro con abbonamento scaduto non riceve la notifica di scadenza imminente |
| `NotificationTest` | un messaggio inviato incrementa il contatore non letti del destinatario |
| `ExportTest` | l'export finanziario CSV contiene le righe corrette |
| `ExportTest` | l'export anagrafica CSV contiene le colonne corrette |
| `BookingTest` | una prenotazione PT viene confermata se lo slot è disponibile |
| `BookingTest` | una prenotazione PT fallisce se lo slot è già occupato |
| `BookingTest` | un membro viene messo in waitlist se il corso è pieno |
| `BookingTest` | cancellare una prenotazione confirmed promuove il primo in waitlist |
| `BookingTest` | la cancellation_deadline è 24 ore prima dell'orario prenotato |
| `BookingTest` | iscriversi due volte allo stesso corso lancia BookingException |
| `BookingTest` | canBeCancelledFree restituisce true se now è prima della deadline |
| `BookingTest` | cancellare una prenotazione in stato cancelled lancia BookingException |
| `FlareTest` | dispatch di un job viene registrato nella coda |
| `FlareTest` | le eccezioni di validazione non vengono segnalate a Flare |
| `ExerciseSeedTest` | seed popola le tabelle lookup con i conteggi corretti |
| `ExerciseSeedTest` | ogni esercizio rispetta il vincolo XOR sui pattern |
| `ExerciseSeedTest` | i pattern FK puntano alla category corretta |
| `E1rmCalculationTest` | epley restituisce il valore corretto per 100kg x 5 reps |
| `E1rmCalculationTest` | epley restituisce null se actual_reps è zero |
| `E1rmCalculationTest` | epley restituisce il valore corretto se actual_reps è 1 |
| `E1rmCalculationTest` | epley restituisce null se weight è null |
| `E1rmCalculationTest` | epley restituisce null se reps è null |
| `KpiServiceTest` | il fatturato del periodo somma solo le subscription del periodo |
| `KpiServiceTest` | la retention rate è 100% se tutti gli iscritti hanno rinnovato |
| `KpiServiceTest` | la churn rate è 0% se nessun abbonamento scaduto è stato rinnovato entro 30 giorni |
| `KpiServiceTest` | la churn rate è 100% se nessun abbonamento scaduto è stato rinnovato |
| `CommunicationTemplateTest` | il template sostituisce correttamente le variabili con i dati del membro |
| `CommunicationTemplateTest` | una variabile non riconosciuta viene lasciata intatta |

---

## Riepilogo

| Ruolo / contesto | Test |
|---|---:|
| Atleta | 14 |
| Trainer | 14 |
| Gestore | 11 |
| Receptionist | 1 |
| Guest | 13 |
| Autenticato (ruolo generico) | 12 |
| Email non verificata | 3 |
| Unit / Service | 46 |
| **Totale** | **114** |

> Nota: alcuni test compaiono in più ruoli (es. TrainingFlowTest usa sia trainer che atleta, MesocycleInstantiation idem) — il totale della tabella supera i 106 effettivi per questo motivo.
