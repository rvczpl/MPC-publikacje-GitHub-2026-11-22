# Lista serwerów MPCforum 6.1.8.1 - rozdzielenie tagów i izolacja funkcji

Autor: RafalR - https://rvcz.pl

## Powtórna weryfikacja i poprawki 6.1.8.1

Zastępuje paczkę 6.1.8. Wykryto przechwytywanie błędów wewnątrz pętli archiwizacji, przez które BackgroundJobs nie widział awarii. Teraz pierwszy błąd przerywa partię i uruchamia przerwę; wykonane wcześniej przeniesienia pozostają zatwierdzone. Błąd pojedynczego tematu wymaga naprawienia przyczyny - nie jest pomijany kosztem dalszych prób.

Wyłączniki loader, badges i shared_badges kontrolują również przypisane JS/CSS. Testuje to rzeczywisty Loader IPS. Test regresyjny archiwizacji wstrzykuje RedisException w operacji przeniesienia i sprawdza jedną próbę oraz zapis stanu awarii w SQL. Migracje 60108 i 60109 uruchomiono po dwa razy bez zmian istniejących danych.

Paczka ma nowy numer 6.1.8.1 / 60109, aktualne metadane języka i osobny katalog migracji. Zachowuje dane i funkcje 6.1.8. Nie zawiera poprawki rdzenia IPS ani konfiguracji Redis.


## Zakres i przyczyna

Logi produkcji potwierdziły wcześniej odmowę zapisu Redis z powodu limitu pamięci. Nie mamy pomiarów potwierdzających, która aplikacja zajęła pamięć. Automatyczne tworzenie i przypisywanie tagów po zmianie prefiksów mogło zwiększać presję na globalne cache tagów IPS. Nie nazywamy tego udowodnioną jedyną przyczyną.

Diagnostyka produkcji z 21.11.2026 działała przez PHP CLI 8.0.30, ale nie mogła połączyć się z bazą (2002, timeout) ani Redis. Nie zmierzyła zużycia pamięci. Administrator musi uruchomić ją w środowisku mającym dostęp do tych usług, zgodnym ze środowiskiem PHP forum.

## Tagi i prefiksy

- Prefiksy, tryby, wersje, MPC Poleca i TOP pozostają danymi aplikacji, z zachowanym wyglądem i rotacją.
- Żaden prefiks nie tworzy już tagu IPS. Usunięto setTags, tworzenie natywnych Tag i indeksowanie posta ze ścieżki synchronizacji prefiksów.
- Stare wywołania sync/synchronize/batch oraz niewydana kolejka tagów są neutralnymi adapterami zgodności. Starsza migracja nie rozpocznie masowej synchronizacji.
- Ręczne tagowanie w skonfigurowanych działach jest wyłączone natywną polityką forum. Dla przeniesionej publikacji polityka działa w pamięci żądania, a osobna akcja editTags jest blokowana.
- Istniejące tagi i kopie w mpcservers_tag_backup pozostają. Aktualizacja nie kasuje wspólnego katalogu tagów, nie przebudowuje globalnego indeksu i nie czyści całego Redis.
- Zachowane stare tagi nadal mogą być odczytywane przez natywne wyszukiwanie IPS. Rozdzielenie zatrzymuje nowe zapisy aplikacji, nie usuwa kosztu istniejącego katalogu. Nie ma już automatycznych tagów SEO z nowych prefiksów.

## Niezależne punkty wykonania

Stabilne rozszerzenia IPS pozostają adapterami do mniejszych usług. Nie zmieniono nazw istniejących rozszerzeń ani identyfikatorów danych.

| Funkcja | Usługa / punkt wejścia | Wyłącznik |
| --- | --- | --- |
| Polityka tagów | PublicationTags | Stała ochrona publikacji, bez synchronizacji |
| Statystyki wejść | TopicViews::record | topic_views |
| Konwersja [serwery] przy zapisie | TagLinks::convertPost | link_conversion |
| Archiwizacja | Archive::runTask przez BackgroundJobs | archive oraz istniejący mpcs_mover_enabled |
| Rozliczanie TOP | Awards::settle przez BackgroundJobs | awards |
| Dostępność serwerów | StatusMonitor przez BackgroundJobs | status oraz sps_status_enabled |
| Sprzątanie cache i wygasłych danych | zadanie status przez BackgroundJobs | cleanup |
| Podglądy, SEO i tematy-spisy | Loader::onFinish | loader |
| Prefiksy tematów/list działów | UIItem::badges | badges |
| Wspólny CSS/JS badge innych aplikacji | Loader::css/js | shared_badges |

Wyłączniki są w ACP: Lista serwerów MPCforum - Listy serwerów - ustawienia modułu. Wymagane dotychczasowe uprawnienie administracyjne modułu list. Wyłączniki nie nadają uprawnień do danych i nie zastępują sprawdzania właściciela serwera. Istniejące przełączniki modułów gier, osób, list i TOP oraz ustawienia proxy nadal działają.

Awaryjnie, jeśli ACP jest niedostępne, administrator może w istniejącym constants.php IPS dopisać:

```php
\define('IPS\MPCS_DISABLED_HOOKS', 'archive,awards,topic_views');
```

Wartość `*` wyłącza wszystkie funkcje z powyższej listy wyłączników. Nie wyłącza całej aplikacji, ochrony tagowania ani rdzenia IPS. Nie naprawi błędu, który występuje w bootstrapie IPS przed uruchomieniem rozszerzeń. Wyłączone przez stałą funkcje nie włączą się przez ACP do czasu zmiany stałej.

## Współbieżność i limity

- Oglądanie tematu, działu lub spisu nigdy nie archiwizuje publikacji i nie synchronizuje tagów.
- Każde z czterech zadań ma własną nieblokującą blokadę MySQL GET_LOCK. Drugi proces opuszcza zajęte zadanie. Blokada używa stałego SUITE_UNIQUE_KEY instalacji IPS i wspólnego serwera bazy. Różne ścieżki plików na węzłach nie rozdzielają już blokady. Klaster z osobnymi serwerami zapisującymi bazy wymaga osobnej weryfikacji.
- Sukces oznacza co najmniej 60 sekund przerwy. Błąd powoduje 5 minut przerwy, rosnącej do godziny. Stan znajduje się w SQL, najwyżej cztery wiersze, bez zapisu do globalnych ustawień IPS.
- Jedno zadanie nie blokuje uruchomienia pozostałych, jeśli jego callback zgłosi wyjątek. Awaria samej bazy może uniemożliwić działanie całego harmonogramu.
- Przed zadaniem i przed każdym przeniesieniem wymagającym natywnych zapisów IPS odczytywane jest Redis INFO memory. Przy wykorzystaniu co najmniej 90% maxmemory lub braku dostępu do statystyk zadanie jest wstrzymane. To próg ostrożności, nie gwarancja wystarczającego miejsca na dowolny zapis. maxmemory=0 oznacza brak skonfigurowanego limitu Redis, nie nieskończoną pamięć hosta.
- Status: do 20 rekordów, budżet 20 sekund sprawdzany między próbami. Pojedyncza próba może przekroczyć pozostały budżet do swojego timeoutu.
- Archiwizacja zachowuje limit partii z ACP. Polecane serwery zachowują dotychczasową ochronę przed archiwizacją.
- Liczenie wyświetleń nadal wykonuje jeden atomowy UPSERT SQL na wejście. Można je wyłączyć. To nie jest dowód wydajności przy tysiącach jednoczesnych wejść w jeden temat.
- LogError aplikacji ogranicza logowanie do jednego wpisu na minutę na host. Pełne wiadomości nie są powielane przy każdym wejściu. Stan blokady jest na dysku, nie Redis. Nie dotyczy wszystkich logów rdzenia IPS ani osobnych diagnostyk proxy.
- Proxy używa cache dyskowego; po zdobyciu blokady ponownie sprawdza cache, aby uniknąć niepotrzebnego pobrania po drugim procesie. Polityka domen, sandbox i kontrola adresów pozostają bez zmian.

## Testy

- Składnia wszystkich 189 plików PHP.
- 10 000 wywołań wycofanych funkcji synchronizacji: bez zmian core_tags, core_tags_data, core_sys_conf_settings i kopii tagów.
- Brak archiwizacji podczas przeglądania; wyłączniki funkcji; renderowanie kontrolek ACP.
- Natywne canTag oraz formularz tworzenia publikacji w obu skonfigurowanych działach testowych.
- Przerwa po symulowanym RedisException, powrót po odblokowaniu, odstęp po sukcesie, próg pamięci.
- Blokada między dwoma rzeczywistymi procesami PHP: drugi nie wykonuje callbacku ani nie zmienia stanu.
- Wszystkie 5 zarejestrowanych rozszerzeń istnieje, w tym Profile/Servers.
- Przeglądarka: kompaktowe badge, rotacja, pozycja wersji i prefiksów w tytule i liście, wariant mobilny.

Testowy IPS korzysta z FileSystem, nie Redis. Nie wykonano testu obciążeniowego tysięcy użytkowników, odtworzenia produkcyjnego zbioru tagów ani pełnej aktualizacji przez ACP z produkcyjnym Redis. Testowa instalacja została zaktualizowana plikami, schematem i metadanymi. Paczka wymaga weryfikacji na kopii produkcji przed aktualizacją działającego forum.

## Aktualizacja i diagnostyka

Pakiet 6.1.8.1 / 60109 aktualizuje istniejącą aplikację. Nie odinstalowywać. Migracja dodaje tabelę mpcservers_job_state, nie zmienia prefiksów, tematów, przypisań osób ani historii podbić. Stare eksperymentalne tabele kolejki, jeśli istnieją, pozostają niewykorzystywane.

Przed aktualizacją produkcji: wykonać kopię plików i bazy, uzyskać działający odczyt diagnostyki Redis z właściwego środowiska, sprawdzić aktualizację na kopii produkcji. Nie wykonywać FLUSHALL ani nie zmieniać polityki eviction bez ustalenia roli Redis w IPS. Usunięcie zapisów aplikacji nie przywraca miejsca ani dostępności już uszkodzonego globalnego datastore.

Stan zadań można odczytać w tabeli mpcservers_job_state: job, failures, blocked_until, last_error (wyłącznie klasa i kod). Po błędzie zadanie wraca automatycznie po upływie przerwy, jeśli nadal jest włączone.
