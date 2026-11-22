# Changelog — System publikacji serwerów MPCforum

## 2026-11-22 — synchronizacja 6.0.1 → 6.1.8.5

Aktualna wersja aplikacji: **6.1.8.5 / 60113**. Ten wpis opisuje różnice między kodem w repozytorium na commicie `996b5d3` a eksportem aktualnej aplikacji z 22 listopada 2026. Obejmuje 50 nowych i 36 zmienionych plików; nie usunięto żadnego pliku źródłowego. Metadane językowe są częścią tego zestawienia. Numery wersji pochodzą z [data/versions.json](applications/mpcservers/data/versions.json).

### Listy serwerów i filtry

- Główne wejście do spisu pokazuje wybór gry. Kafelki prowadzą do katalogu właściwej gry, a listy i spisy w tematach mają odnośnik „Wybierz grę”. Zachowano grafiki gier i sprawdzanie dostępności publikacji.
- Domyślne sortowanie opiera się na liczbie podbić w bieżącym tygodniu; remisy rozstrzyga data ostatniego podbicia, a następnie identyfikator publikacji. Serwery polecane zachowują pierwszeństwo. Stare adresy `sort=bump` przechodzą na sortowanie tygodniowe, również w odpowiedziach AJAX.
- Rozbudowane grupy filtrów są zwijane, gdy występuje więcej niż jedna grupa albo grupa ma ponad osiem opcji. Nagłówek pokazuje aktualny wybór i liczbę opcji. Odświeżenie AJAX zachowuje otwarte sekcje i fokus.
- Kafelki filtrów mają kompaktową siatkę i ograniczoną wysokość sekcji, z dostosowaniem do małych ekranów. Małe pojedyncze grupy zachowują prosty układ.
- Kolumna statusu i filtr dostępności są wyświetlane tylko wtedy, gdy monitoring jest włączony i dostępny dla działów danej listy. Metin2 bez kontroli TCP nie pokazuje pustej funkcji. Parametr statusu z adresu URL jest ignorowany, gdy filtr jest niedostępny.
- Tabela rezerwuje więcej miejsca na nazwę i prefiksy serwera, wyrównuje pozostałe kolumny oraz poprawnie oblicza `colspan` pustej listy i podium TOP. Długie nazwy mają wielokropek i pełny tekst w podpowiedzi; na wąskim ekranie treść się zawija.
- Wiersz pokazuje aktualne TOP, a nie całą historyczną listę nagród. Wiele jednoczesnych wyróżnień może być zebranych w jeden badge „TOP”. Historia nagród pozostaje w danych.

### Prefiksy, tryby i wersje Minecrafta

- Dodano indywidualny kolor RGB dla opcji prefiksu: edytor koloru oraz zapis `Nazwa {#rrggbb}`. Ten sam kolor trafia do formularzy i odznak IPS, a kolor tekstu jest dobierany do jasności tła.
- Dodano osobny edytor trybów serwera w ustawieniach modułów gier: jeden tryb w wierszu, opcjonalny kolor i wielokrotny wybór w publikacji. Zachowanie nazwy zachowuje identyfikator; pusta lista nie usuwa istniejących trybów.
- Obecny układ to wersja Minecrafta i MPC Poleca / TOP przed tytułem, a tryby i pozostałe prefiksy za tytułem. Dotyczy listy tematów, nagłówka tematu i tabeli serwerów. Skrypty obsługują zmiany AJAX, przenoszą istniejące węzły i nie powielają oznaczeń.
- Tryby rotują w jednym miejscu, a zwykłe prefiksy we wspólnej puli trzech miejsc. Rotacja następuje co 2,5 sekundy, zatrzymuje się po najechaniu lub ustawieniu fokusu i nie działa przy preferencji ograniczonego ruchu. Wersja gry i wyróżnienia pozostają stałe.
- ACP umożliwia zdefiniowanie listy wersji Minecrafta. Formularz publikacji wymaga co najmniej jednej wersji i pozwala wybrać kilka; widok pokazuje pojedynczą wersję albo zakres od najniższej do najwyższej bez nawiasów. Sortowanie wersji jest numeryczne. Zapisane wybory pozostają dostępne po usunięciu wersji z listy ACP.
- Końcowy styl 6.1.8.5 ogranicza wspólną geometrię do prefiksów publikacji, MPC Poleca i TOP. Zwykłe badge mają minimum 96 px szerokości i wysokość 22 px; wersje Minecrafta minimum 36 px. Prefiksy w tabeli mają promień 6 px, a w tytułach zachowują kształt kapsułki. Dłuższy tekst otrzymuje podpowiedź.
- Usunięto szerokie reguły stylowania standardowego `ipsBadge`, Metin2 News i prefiksów innych aplikacji. Ikony przypięcia, zamknięcia, ukrycia i wyróżnienia pozostają pod kontrolą motywu. Wersjonowane adresy CSS/JS wymuszają pobranie właściwych zasobów po aktualizacji.
- Widget tematów publikacji ukrywa drugi blok autora i czasu tylko wtedy, gdy powtarza widoczny pierwszy blok. Nowa odpowiedź, inny autor albo inny czas pozostają widoczne. Liczniki odpowiedzi i wyświetleń są zachowane; kontrola działa po AJAX i zmianie szerokości.

### Tagi i wykonanie funkcji w tle

- Prefiksy, tryby, wersje, MPC Poleca i TOP są niezależne od natywnych tagów IPS. W aktualnym kodzie synchronizacja, kolejka i stare metody zgodności nie tworzą tagów, nie przypisują ich i nie przeindeksowują postów.
- Ręczne tagowanie jest wyłączane w skonfigurowanych działach publikacji. Po usunięciu działu z konfiguracji odtwarzane jest jego poprzednie ustawienie. Przeniesiona publikacja nadal ma ochronę w bieżącym żądaniu, a bezpośrednia akcja `editTags` jest blokowana.
- Zachowano istniejące tagi i ich wcześniejsze kopie. Aktualizacja nie usuwa wspólnego katalogu tagów, nie przebudowuje globalnego indeksu i nie czyści całego Redis. Wyszukiwarka IPS może nadal odczytywać stare tagi.
- Oglądanie tematu, działu lub spisu nie wykonuje archiwizacji. Przenoszenie nieaktywnych publikacji odbywa się wyłącznie przez zadanie w tle.
- Dodano niezależne wyłączniki: statystyki wejść, konwersja znaczników spisu, archiwizacja, TOP, monitoring statusu, sprzątanie, loader oraz dwa zestawy stylów/skryptów prefiksów. Dostępne są w ACP modułu Listy; awaryjna stała `IPS\MPCS_DISABLED_HOOKS` może je wyłączyć bez ACP. Wyłączniki nie zmieniają uprawnień do danych.
- Archiwizacja, TOP, status i sprzątanie mają osobne nieblokujące blokady SQL oraz stan w `mpcservers_job_state`. Nazwa blokady opiera się na stałym identyfikatorze instalacji IPS, dzięki czemu różne ścieżki plików na węzłach nie tworzą niezależnych blokad.
- Po sukcesie obowiązuje co najmniej 60 sekund odstępu. Błąd uruchamia przerwę od pięciu minut do godziny. Stan błędu przechowuje klasę i kod wyjątku, bez pełnej treści, zapytania SQL czy danych uwierzytelniających.
- Błąd przeniesienia przerywa partię archiwizacji i trafia do mechanizmu przerw; nie jest traktowany jak udane wykonanie. Zakończone wcześniej przeniesienia pozostają zapisane.
- Przed archiwizacją i przed każdym przeniesieniem sprawdzana jest pamięć Redis. Brak statystyk lub wykorzystanie co najmniej 90% skonfigurowanego limitu wstrzymuje zadanie. Nie jest to naprawa konfiguracji Redis ani gwarancja wolnej pamięci dla dowolnego obciążenia.
- Monitoring statusu przeniesiono do osobnej usługi. Zachowuje limit 20 rekordów i budżet około 20 sekund sprawdzany między próbami; aktualizacja wyniku nadal sprawdza, czy adres serwera nie zmienił się równolegle.
- Powtarzające się błędy aplikacji są ograniczane do jednego wpisu na minutę na host. Kontrola wykorzystuje plik blokady, bez zwiększania zapisów do Redis. Pomiar wejść nadal jest jednym atomowym UPSERT-em SQL i może być osobno wyłączony.

### Zdalne prezentacje Metin2 i proxy

- Dodano obsługę podpisanych zasobów JS/MJS oraz przepisywanie statycznych importów modułów, metadanych startowych i skryptów `type="module"`. Dowolne dynamicznie wyliczane importy nie są obsługiwane.
- Dodano walidację i obsługę modeli GLB oraz obrazów używanych jako tekstury WebGL. CSP dopuszcza obrazy `blob:`, przy zachowaniu izolowanego iframe i dotychczasowych kontroli celu.
- Most przeglądarkowy obsługuje lokalne zmiany fragmentu przez History API bez przekierowania na ścieżki forum. Moduły, fetch i XHR do podpisanej bramki mogą korzystać z uwierzytelnienia forum/testowego hosta; te dane nie są przekazywane do zewnętrznej prezentacji.
- Specjalna obsługa prezentacji Ravii pomija jej zbiorcze wstępne pobieranie biblioteki multimediów. Zwykłe obrazy i filmy ładuje przeglądarka ze źródła; podpisane proxy obsługuje wymagające CORS tekstury WebGL i fonty.
- Obrazy tła CSS są pobierane bezpośrednio, więc nie wyczerpują wspólnego limitu pobrań proxy. Fonty zachowują odrębną obsługę.
- Normalizowane są nieszkodliwe segmenty `/./` i wielkość liter hosta przed zbudowaniem adresu/cache; blokada przechodzenia do katalogu nadrzędnego pozostaje.
- Po uzyskaniu blokady ponownie sprawdzany jest cache prezentacji, aby równoległe żądania nie pobierały ponownie gotowego wyniku.
- Rozróżniono błędy dostępu, limitu, zajętej blokady, źródła i przekształcania zasobu: odpowiednio 403, 429, 503, 502 i 500, nagłówek `X-MPC-Proxy-Error`, a przy limicie/blokadzie także `Retry-After`.
- Diagnostyka `mpcservers_proxy_asset` wskazuje etap, typ problemu i ścieżkę zasobu. Nie zapisuje podpisu dostępowego, cookies, parametrów zapytania ani treści odpowiedzi źródła. Błąd pobrania zawiera status HTTP źródła, także informację o wykrytym challenge Cloudflare.

### Nagrody Minecrafta i panel właściciela

- Ustawienie nagród obsługuje listę wyjątków dla konkretnych publikacji. Pozwala pozostawić nagrody na wybranych serwerach przy wyłączonym ogólnym przełączniku gry.
- Ta sama decyzja jest sprawdzana w panelu właściciela, pobieraniu integracji, generowaniu/resetowaniu tokenu, formularzu podbicia, generowaniu kodu i odbiorze przez API. Usunięcie wyjątku blokuje także odbiór wcześniej wydanych kodów; tokeny i historia pozostają zapisane.
- Nieaktywna integracja nie jest pokazywana w panelu właściciela jako gotowa do użycia. Podbijanie bez nagród nadal działa.
- Role owner/technician, rejestr osób, skład ekip, oceny i historia współpracy nie zostały przebudowane w tej synchronizacji. Zmiany panelu dotyczą dostępności nagród; pomiar wejść otrzymał osobny wyłącznik.

### Schemat, ustawienia i migracje

Schemat zwiększył się z 15 do **17 tabel**. Dodano `mpcservers_tag_backup`, `mpcservers_job_state` i kolumnę `mpcservers_presentations.mc_versions` z wyborem wersji w JSON. Nowe klucze ustawień to `sps_minecraft_versions`, `sps_tag_policy_forums`, `sps_tag_sync_cursor` oraz `mpcs_feature_hooks`. `sps_tag_sync_cursor` pozostał metadanym historycznym wycofanego procesu synchronizacji.

| Wersja / krok | Działanie aktualnego kodu migracji |
|---|---|
| 6.0.4 / `60004` | Dodaje brakującą kolumnę `mc_versions`; nie nadpisuje istniejących publikacji. |
| 6.0.8 / `60008` | Tworzy brakującą tabelę kopii tagów i ustawia politykę ręcznego tagowania. Drugi krok wywołuje obecnie neutralny adapter `batch`, więc przy aktualizacji do 6.1.8.5 nie synchronizuje prefiksów. Historyczny opis kroku nadal odnosi się do tagów. |
| 6.1.8 / `60108` i 6.1.8.1 / `60109` | Idempotentnie tworzą brakującą tabelę stanu zadań. |
| 6.1.8.2–6.1.8.5 / `60110`–`60113` | Kroki bez zmian danych, związane z kolejnymi wydaniami interfejsu. |
| Pozostałe nowe katalogi `setup/upg_*` | Zawierają puste `queries.json`; nie deklarują dodatkowych migracji danych. |

W źródłach istnieje pusty katalog `upg_60106`, ale **6.1.6 nie jest wpisem w `versions.json`**. Nie dopisano fikcyjnej wydanej wersji. Nowa instalacja rejestruje stan zadań i politykę tagów również przez `Application::installOther()`.

### Historia wydań zawarta w aktualnym pakiecie

Poniższe wpisy porządkują dostarczoną [historię aplikacji](applications/mpcservers/CHANGELOG.md). Opisują kolejne etapy rozwoju; późniejsze poprawki zastępują część wcześniejszych decyzji, szczególnie synchronizację tagów, kolejność oznaczeń i zasięg CSS. Bieżące zachowanie opisano powyżej.

| Wersja | Data | Poprawki wydania |
|---|---|---|
| 6.0.2 / 60002 | 2026-11-21 | Rozpoznawanie powielonego autora/czasu w widżecie, sortowanie według tygodniowych podbić, proxy Ravii: moduły JS, History API, GLB, tekstury i natywne multimedia. |
| 6.0.3 / 60003 | 2026-11-21 | Tła CSS poza limitem proxy, normalizacja `/./`, rozróżnienie błędów i nagłówki diagnostyczne. |
| 6.0.4 / 60004 | 2026-11-21 | Kolory RGB prefiksów, wybór wersji Minecrafta, wyjątki nagród per publikacja oraz kolumna `mc_versions`. |
| 6.0.5 / 60005 | 2026-11-21 | Zwijane filtry, kompaktowe kafelki i układ mobilny. |
| 6.0.6 / 60006 | 2026-11-21 | Prefiksy przed tytułem tematu IPS5 i odświeżanie bez duplikatów; kolejność później zmieniono. |
| 6.0.7 / 60007 | 2026-11-21 | Główne menu wyboru gry i odnośniki powrotu. |
| 6.0.8 / 60008 | 2026-11-21 | Blokada ręcznych tagów; historycznie synchronizacja prefiksów z tagami i kopie tagów. Synchronizacja została wycofana w 6.1.8. |
| 6.0.9 / 60009 | 2026-11-21 | Rotacja co 2,5 sekundy od trzech trybów, pauza, ograniczony ruch i wersje bez nawiasów. Obecny podział miejsc wprowadzono w 6.1.3. |
| 6.0.10 / 60010 | 2026-11-21 | MPC Poleca, aktualne TOP i tryby przed tytułem listy, wersja za tytułem; kolejność później zmieniono. |
| 6.1.0 / 60100 | 2026-11-21 | Odpowiednia kolejność oznaczeń także w nagłówku otwartego tematu. |
| 6.1.1 / 60101 | 2026-11-21 | Niezależna rotacja prefiksów grup ACP, nie tylko grupy nazwanej „Tryby gry”. |
| 6.1.2 / 60102 | 2026-11-21 | Spójna kolejność w tabeli i wyświetlanie aktualnych TOP z zachowaniem historii. |
| 6.1.3 / 60103 | 2026-11-21 | Jedno miejsce na tryby, trzy na pozostałe prefiksy, jedno na wyróżnienie; osobny edytor trybów i wielokrotny wybór. |
| 6.1.4 / 60104 | 2026-11-21 | Wersja gry i wyróżnienie przed nazwą; tryby i zwykłe prefiksy za nazwą we wszystkich trzech widokach. |
| 6.1.5 / 60105 | 2026-11-21 | Historyczna geometria 136 × 28 px i wspólne stylowanie badge, później ograniczone do publikacji. |
| 6.1.7 / 60107 | 2026-11-21 | Kompaktowe badge 96 × 22 px i krótsze wersje gry; historyczne szersze reguły CSS usunięto w 6.1.8.2. |
| 6.1.8 / 60108 | 2026-11-21 | Rozdzielenie tagów od prefiksów, archiwizacja tylko w tle, wyłączniki funkcji, osobne zadania z blokadą/przerwami, próg pamięci Redis i ograniczenie logowania. |
| 6.1.8.1 / 60109 | 2026-11-21 | Propagowanie błędu archiwizacji, kontrola Redis przed każdym przeniesieniem, wyłączniki CSS/JS, stabilne identyfikatory blokad i idempotentna migracja. |
| 6.1.8.2 / 60110 | 2026-11-21 | Stylowanie ograniczone do własnych prefiksów publikacji, MPC Poleca i TOP; natywne oznaczenia IPS zachowują motyw. |
| 6.1.8.3 / 60111 | 2026-11-21 | Kapsułki z promieniem 20 px, minimalną szerokością 96 px i wysokością 22 px. |
| 6.1.8.4 / 60112 | 2026-11-21 | Wersje Minecrafta minimum 36 px, prefiksy tabeli z promieniem 6 px. |
| 6.1.8.5 / 60113 | 2026-11-22 | Warunkowy status, szersza kolumna nazwy, zawijanie na małym ekranie i poprawne `colspan`. |

### Zakres sprawdzenia

Treść changelogu sprawdzono na podstawie różnic źródeł, bieżących manifestów i migracji. Nie oznacza to ponownego wykonania wszystkich testów funkcjonalnych historycznych wydań. Wcześniejsze testy 6.1.8.1 oraz ich ograniczenia opisuje [README-6.1.8.1.md](applications/mpcservers/README-6.1.8.1.md), a sprawdzenia aktualizacji repozytorium — [docs/VALIDATION.md](docs/VALIDATION.md).
