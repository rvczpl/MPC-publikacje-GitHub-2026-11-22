# 6.1.8.5 - 22.11.2026

- Status i jego filtr są widoczne tylko przy włączonym monitoringu obsługiwanym przez działy danej listy. Metin2 bez kontroli TCP nie pokazuje pustej funkcji.
- Poszerzona kolumna nazwy, wyrównane szerokości pozostałych kolumn. Nazwa i prefiksy w jednym wierszu na szerokim ekranie; bardzo długa nazwa ma wielokropek i pełny tekst po najechaniu. Na wąskim ekranie tekst zawija się bez utraty treści.
- Poprawny colspan pustej listy i TOP-ów; ukryty filtr statusu jest ignorowany również w URL i AJAX.
- Bez zmian danych, monitoringu i globalnego czyszczenia cache.

# 6.1.8.4 - 21.11.2026

- Badge wersji Minecrafta: minimalna szerokość 36 px zamiast 96 px, wysokość nadal 22 px.
- Prefiksy tabeli serwerów: radius 6 px zamiast 20 px.
- Pozostałe prefiksy i inne aplikacje bez zmian. Bez migracji danych.

# 6.1.8.3 - 21.11.2026

- Kształt kapsułki jak GORĄCY na scrum: radius 20 px.
- Prefiksy mają minimum 96 px szerokości i wysokość 22 px; dłuższe etykiety poszerzają badge do szerokości kontenera.
- Styl ograniczony do własnych prefiksów aplikacji; ikony i statusy IPS nie są modyfikowane.
- Bez zmian danych. Nowy numer cache CSS.

# Historia wydań

## 6.1.8.2 - 21.11.2026

- CSS i JS rozmiarów badge dotyczą wyłącznie prefiksów publikacji, MPC Poleca i TOP.
- Usunięto globalny selektor ipsBadge i selektory Metin2 News / Prefiksów tematów.
- Ikony przypięcia, zamknięcia, wyróżnienia i ukrycia zachowują styl motywu oraz natywny DOM.
- Nowe adresy wersjonowanych zasobów zapobiegają użyciu poprzedniego CSS/JS po odświeżeniu.
- Brak migracji danych; zachowane zabezpieczenia 6.1.8.1.

## 6.1.8.1 - 21.11.2026

- Błąd archiwizacji przerywa partię i uruchamia przerwę w BackgroundJobs; nie jest raportowany jako sukces.
- Kontrola pamięci Redis przed każdym przeniesieniem, nie tylko na początku zadania.
- Wyłączniki prefiksów i loadera odcinają też właściwe CSS i JavaScript.
- Blokady zadań oparte o stały identyfikator instalacji IPS, niezależnie od ścieżki plików na węźle.
- Zachowane rozdzielenie prefiksów i tagów z 6.1.8. Migracja nowej tabeli jest idempotentna.

## 6.1.8 - 21.11.2026

- Prefiksy niezależne od tagów IPS: usunięta synchronizacja, tworzenie tagów i przeindeksowanie po zmianie prefiksów.
- Ręczne tagowanie publikacji pozostaje zablokowane; zachowane istniejące dane i kopie tagów.
- Archiwizacja wyłącznie w zadaniu w tle, bez modyfikacji przy przeglądaniu.
- Osobne wyłączniki diagnostyczne w ustawieniach modułu Listy oraz awaryjny przełącznik w constants.php.
- Niezależne zadania: archiwizacja, TOP, status i sprzątanie. Blokady SQL, odstępy i przerwa po błędzie.
- Archiwizacja wstrzymywana przy braku informacji o Redis lub zużyciu co najmniej 90% limitu pamięci.
- Ograniczenie powtarzanych logów aplikacji i ponowne sprawdzenie cache proxy pod blokadą.
- Nie naprawia konfiguracji Redis ani braku pamięci w datastore IPS. Szczegóły testów i ograniczeń: README-6.1.8.md.

## 6.1.7 - 21.11.2026

- Wydanie oparte na 6.1.5: kompaktowe badge 96 x 22 px.
- Styl modlogs_score_value w CSS aplikacji publikacyjnej; krótsze wersje gry.
- Bez migracji danych; nie stanowi naprawy incydentu Redis OOM z produkcji.

## 6.1.5 - 21.11.2026

- Równa geometria badge: 136 x 28 px, zaokrąglenie 9 px, obramowanie i delikatny cień.
- Wspólny styl dla listy serwerów, Metin2 News, Prefiksów tematów i standardowych badge IPS.
- Zachowane kolory, ikony i rotacja; skrócone długie nazwy z pełnym tekstem w podpowiedzi.

## 6.1.4 - 21.11.2026

- Wersja gry i MPC Poleca / TOP przed nazwą; tryby i zwykłe prefiksy za nazwą.
- Spójna kolejność na liście działu, w nagłówku i tabeli serwerów.
- Zachowana rotacja, AJAX i dane publikacji.

## 6.1.3 - 21.11.2026

- Jedno miejsce na MPC Poleca / TOP, jedno na rotujące tryby i trzy na wspólną pulę zwykłych prefiksów.
- Klawiaturowy edytor trybów w ACP modułów gier, wielokrotny wybór w publikacji.
- Zachowane identyfikatory, wybory, kolory i historia; wersja gry pozostaje za nazwą.
- Obsługa listy działu, nagłówka, tabeli serwerów, AJAX i małych ekranów.

## 6.1.2 - 21.11.2026

- Spójna kolejność oznaczeń w tabeli serwerów: MPC Poleca / aktualne TOP, prefiksy, nazwa, wersja.
- Zachowana rotacja wszystkich grup, stała wersja bez nawiasów, AJAX i układ mobilny.
- Historyczne nagrody pozostają w danych; tabela pokazuje aktywne wyróżnienia.

## 6.1.1 - 21.11.2026

- Niezależna rotacja od trzech wybranych prefiksów w każdej grupie ACP, bez ograniczenia do nazwy Tryby gry.
- Wspólne działanie na liście i w nagłówku tematu; wersja gry i wyróżnienia pozostają stałe.
- Bez migracji danych publikacji.

## 6.1.0 - 21.11.2026

- Kolejność MPC Poleca / TOP, tryby, tytuł, wersja także w nagłówku otwartego tematu.
- Zachowana rotacja od trzech trybów, obsługa AJAX i układ mobilny.
- Bez migracji danych publikacji.

## 6.0.10 - 21.11.2026

- MPC Poleca, aktualne TOP i tryby przed tytułem na liście tematów działu.
- Wersja gry za tytułem; zachowana rotacja trybów i obsługa AJAX.
- Bez zmian zapisanych danych publikacji.

## 6.0.9 - 21.11.2026

- Rotacja co 2,5 sekundy od trzech prefiksów w grupie trybów, bez przeładowania strony.
- Stałe pozostałe prefiksy, pauza po najechaniu, obsługa AJAX i ograniczonego ruchu.
- Wersja Minecraft i zakres wersji bez nawiasów.

## 6.0.8 - 21.11.2026

- Wyłączone ręczne tagowanie we wszystkich działach publikacyjnych, również podczas edycji i przez bezpośrednią akcję edycji tagów.
- Prefiksy i wybrane wersje Minecraft synchronizowane z natywnymi tagami IPS.
- Ukryte tagi w nagłówku publikacji; widoczne prefiksy pozostają przed tytułem.
- Migracja istniejących publikacji i kopia poprzednich tagów przed pierwszą zmianą.

## 6.0.7 - 21.11.2026

- Główny spis jako menu wyboru gry zamiast wspólnej tabeli.
- Kafelki prowadzą do list danej gry; link powrotu Wybierz grę.
- Zachowane uprawnienia, grafiki, filtry i dane publikacji.

## 6.0.6 - 21.11.2026

- Prefiksy publikacji przed tytułem w nagłówku tematu IPS5, z MPC Poleca na początku.
- Zachowane kolory, edycja tytułu i pozostałe odznaki; obsługa odświeżeń AJAX bez powielania prefiksów.
- Bez migracji danych.

## 6.0.5 - 21.11.2026

- Zwijane grupy filtrów przy wielu grupach lub więcej niż 8 opcjach. Wybrany filtr pozostaje widoczny w nagłówku.
- Kompaktowe kafelki w równej siatce, ograniczona wysokość sekcji i układ mobilny. Małe pojedyncze grupy zachowują dotychczasowy wygląd.
- Bez zmian danych i zasad filtrowania.

## 6.0.4 - 21.11.2026

- Indywidualne kolory RGB prefiksów w grupach Minecraft i Metin2, także na odznakach IPS5.
- Lista wersji Minecraft w ACP, wymagany wielokrotny wybór w publikacji oraz pojedynczy prefiks zakresu przy tytule.
- Wyjątki publikacji dla wyłączonych nagród Minecraft, sprawdzane w panelu właściciela, generowaniu kodów i odbiorze przez API.
- Migracja zachowuje dane; istniejące prefiksy wersji pozostają bez zmian.

## 6.0.3 - 21.11.2026

- Naprawa wyczerpywania limitu proxy przez obrazy tła CSS. Tła pobierane bezpośrednio ze źródła; fonty i tekstury WebGL zachowują odrębną obsługę proxy.

- Normalizacja /./ w adresach zasobów przed utworzeniem odnośnika i klucza cache. Zachowana blokada przechodzenia do katalogu nadrzędnego.
- Rozróżnienie limitu pobrań, zajętej blokady, problemu źródła i przekształcania zasobu. Nagłówki Retry-After oraz X-MPC-Proxy-Error.
- Log mpcservers_proxy_asset z etapem i przyczyną błędu, bez tokenów i parametrów zapytania. Dodatkowa informacja o statusie HTTP źródła.
- Bez zmian danych i zwiększania limitów pobierania.

## 6.0.2 - 21.11.2026

- Widżet tematów publikacji: drugi blok autora i czasu jest ukrywany tylko wtedy, gdy powtarza widoczny pierwszy blok. Nowa odpowiedź, inny autor albo inny czas pozostają widoczne. Zachowane liczniki odpowiedzi i wyświetleń. Obsługa odświeżania AJAX i zmiany szerokości.
- Domyślne sortowanie list według podbić z bieżącego tygodnia. Ostatnie podbicie rozstrzyga remis. Dawne sort=bump również prowadzi do kolejności według liczby podbić.
- Proxy Ravii: zachowanie metadanych startowych, podpisane moduły JS, History API, modele GLB i tekstury. Multimedia ładowane natywnie zamiast zbiorczego pobierania filmów przez forum. Zachowana izolacja iframe i limity zasobów.
- Aktualizacja nie zmienia danych publikacji ani historii. Pełen pakiet zawiera wcześniejsze poprawki plikowe z 21.11.2026.

## Numerowanie

Każde następne wydanie otrzymuje nowy numer oraz rosnący long_version. Poprawki zgodne wstecz zwiększają ostatnią część numeru. Nazwa archiwum zawiera wersję. Przed wydaniem sprawdzana jest bieżąca wersja serwera.
