# Lista serwerów MPCforum 6.1.8.5

Autor: RafalR - https://rvcz.pl

Aplikacja dla IPS 5. Techniczny identyfikator pozostaje `mpcservers`. Wersja 6.1.8.5 ma wewnętrzny numer 60113. Pakiet służy do aktualizacji instalacji IPS 5. Nie odinstalowuj poprzedniej wersji przed aktualizacją.

Szczegóły rozdzielenia tagów, zabezpieczeń i testów: [README-6.1.8.1.md](README-6.1.8.1.md).

## Korekta 6.1.8.2

Styl i rozmiary badge dotyczą wyłącznie prefiksów publikacji serwerów, MPC Poleca i TOP. Systemowe oznaczenia IPS i badge innych aplikacji pozostają w stylu motywu. Ustawienie wewnętrzne shared_badges zachowuje klucz, ale dotyczy już wyłącznie prefiksów publikacji. Zasoby mają nowy numer cache 60110. Nie zmieniono danych ani zabezpieczeń z 6.1.8.1.

## Instalacja i aktualizacja

1. Wykonaj kopię plików aplikacji i bazy IPS.
2. W ACP wybierz aktualizację istniejącej aplikacji i wskaż archiwum TAR. Dla nowego forum wybierz instalację.
3. Sprawdź uprawnienia administratorów w ACP. Pięć modułów ma pięć osobnych ograniczeń `sls_*_manage`. Administratorzy z ograniczeniami wymagają jawnego nadania nowych uprawnień; stare ograniczenia nie nadają automatycznie szerszego dostępu.
4. W Metin2 lub Minecraft skonfiguruj działy publikacji i grupy prefiksów. Istniejące działy Metin2/Minecraft zostaną rozpoznane bez zmiany identyfikatorów.
5. Nierozpoznane stare gry pozostają w bazie. Można przypisać je do modułu w jego ustawieniach. Nie przypisuj innych gier do Metin2/Minecraft tylko w celu ukrycia ostrzeżenia.
6. Sprawdź listę, temat publikacji i panel właściciela na własnym motywie.

## Moduły

- [Metin2](sources/Modules/Metin2/README.md) - publikacje i proxy.
- [Listy](sources/Modules/Lists/README.md) - katalogi, filtry, podbicia.
- [MPC Poleca i TOP](sources/Modules/Highlights/README.md) - rekomendacje i zakończone okresy.
- [Osoby i właściciele](sources/Modules/Community/README.md) - zespoły, historia, panel prywatny.
- [Minecraft](sources/Modules/Minecraft/README.md) - publikacje standardowe i opcjonalne nagrody.
- [Rdzeń](sources/Modules/Core/README.md) - migracja i kontrakty.

Wyłączenie modułu nie usuwa danych. ACP pozostaje dostępne dla administratora posiadającego odpowiednie ograniczenie, aby umożliwić ponowne włączenie. Moduły gier korzystają ze wspólnego formularza i widoku tematu, ale mają odrębne polityki prezentacji. Moduły list i osób współdzielą komponenty wyglądu.

## Dane i zgodność

Tabele, ID tematów, osób, wpisów historii, głosów i klucze gier pozostają bez zmian. Migracja ustawień jest powtarzalna. Stare ścieżki klas to adaptery nowych implementacji. Stare adresy list, osób i panelu pozostają zgodne. Publiczny ranking podbić zastępuje strona TOP; jego adres przekierowuje do TOP. Historia głosów nadal służy statystykom i rozliczaniu wyróżnień.

Prefiksy zapisuje się nadal w zgodnym formacie `Nazwa grupy: opcja | opcja`. Dopisek `(kilka)` albo `(max 2)` określa wielokrotny wybór. Liczba grup nie ma sztucznego limitu aplikacji. Nazwy tworzą klucze atrybutów: zmiana nazwy istniejącej grupy/opcji nie przepisuje automatycznie starych deklaracji tematów. Zmiana kolejności jest bezpieczna dla tych kluczy.

## Bezpieczeństwo i eksploatacja

IPS sprawdza sesje, widoczność tematów i ograniczenia ACP. Właściciel musi mieć aktualną rolę owner połączoną z kontem forum; autorstwo tematu nie wystarcza. Dane wejściowe są walidowane, wyjścia HTML kodowane, zapytania korzystają z parametrów IPS. Zewnętrzne skrypty prezentacji działają w izolowanym iframe, bez dostępu do sesji forum. Szczegóły ograniczeń proxy opisano w module Metin2.

Zadania IPS obsługują statusy, archiwizację, wygaszanie pamięci proxy i rozliczanie okresów. Polecane serwery nie są archiwizowane, dopóki oznaczenie jest aktywne. Monitoruj dziennik aplikacji i zadania IPS. Cofnięcie migracji wymaga przywrócenia razem plików i kopii bazy; instalacja starszego TAR nie jest rollbackiem.

## Ograniczenia

Proxy PHP nie jest pełną przeglądarką i nie gwarantuje zgodności każdej witryny. Generator zrzutów wymaga osobnego silnika renderowania; pozostaje wyłączony. Ustawienia starej integracji i tokeny nie są drukowane w dokumentacji ani logach diagnostycznych. Nie wdrażano tej przebudowy na produkcji.

## Diagnostyka zasobów prezentacji

Błędy zasobów proxy są zapisywane w logu IPS `mpcservers_proxy_asset`. HTTP 429 oznacza limit pobrań (ponów po 60 sekundach), 503 zajętą blokadę (5 sekund), 502 problem pobrania lub walidacji zasobu, a 500 błąd przekształcania. Nagłówek `X-MPC-Proxy-Error` podaje kategorię. Log nie zawiera podpisów dostępu, parametrów URL ani treści zasobów. Limity rozmiaru, liczby pobrań i izolacja prezentacji pozostają aktywne.

Wydanie 6.0.4 usuwa potwierdzone na ipstesty wyczerpywanie limitu przez obrazy tła CSS w prezentacji Ravii. Tła są pobierane bezpośrednio ze źródła. Jeżeli problem na produkcji powróci, odczytaj odpowiadający mu wpis logu.

## Wersja 6.0.4 - konfiguracja

- Kolory: ACP -> Metin2 lub Minecraft -> działy publikacji -> grupy prefiksów. Przy każdym prefiksie jest próbnik RGB i przycisk przywracania koloru grupy. Format tekstowy: `Tryb: Easy {#008800} | Hard {#bb2200}`. Kolor nie zmienia klucza opcji.
- Wersje: ACP -> Minecraft -> ustawienia -> Wersje Minecraft (także w edycji działu). Lista wspólna dla działów tej samej gry. Jedna wersja w wierszu. Publikacja Minecraft wymaga co najmniej jednego wyboru. Zakres sortowany numerycznie: `(1.7 - 1.10)`. Zapisujemy wszystkie wybrane wersje, także przy przerwach w wyborze. Pełny wybór widać w edytorze. Zapisane wersje usunięte z ACP pozostają dostępne w edycji danej publikacji.
- Dotychczasowe prefiksy wersji nie są automatycznie kasowane ani przenoszone. Najpierw uzupełnij nowe pole w istniejących publikacjach, potem usuń niepotrzebną grupę wersji. Starsze publikacje pozostają widoczne bez nowego pola; wybór jest wymagany przy następnym zapisie publikacji.
- Nagrody testowe: wyłącz Nagrody za podbijanie i wybierz publikacje w polu Wyjątki - serwery z nagrodami. Poza wyjątkami opcja nagród, pluginu i generowania tokenu jest ukryta, a bezpośrednie wywołania są blokowane. Wyłączenie nie kasuje tokenów ani historii. Po usunięciu wyjątku odbiór kodów jest blokowany. Ponowne włączenie pozwala odebrać nadal ważne kody. Włączony przełącznik udostępnia nagrody wszystkim serwerom tej gry.
- Aktualizacja dodaje jedną opcjonalną kolumnę `mc_versions`. Nie zmienia wcześniejszych danych, konfiguracji nagród ani kont właścicieli. Uprawnienia ACP pozostają przypisane do modułów.

## 6.0.5 - duże zestawy filtrów

Przy wielu grupach prefiksów albo ponad 8 opcjach w jednej grupie filtry mają domyślnie zwinięte sekcje. Nagłówek pokazuje nazwę grupy, liczbę opcji i bieżący wybór. Po rozwinięciu widoczne są kompaktowe kafelki o równej szerokości, z ograniczeniem wysokości i przewijaniem wewnątrz sekcji. Na telefonie kafelki mają dwie kolumny. Jedna grupa do 8 opcji zachowuje duże ilustracje. Zmiana nie usuwa grup, prefiksów, grafik ani wyborów użytkowników.

## 6.0.6 - prefiksy przed tytułem tematu

W nagłówku tematu prefiksy aplikacji i MPC Poleca są przenoszone przed tytuł. Kolory pozostają zachowane; MPC Poleca jest pierwsze. Pozostałe odznaki IPS pozostają w swoim bloku. Zmiana obsługuje edytowalny tytuł oraz odświeżenia elementów strony. Nie zmienia zapisanych tytułów ani prefiksów.

## 6.0.8 - ekran wyboru gry

Główny adres listy pokazuje menu i kafelki dostępnych gier, bez wspólnej tabeli i bez kafelka Wszystkie gry. Kliknięcie gry otwiera jej tabelę i filtry. Przycisk Wybierz grę wraca do ekranu głównego. Kafelki wykorzystują tła skonfigurowane w ACP i uwzględniają uprawnienia do działów. Ekran wyboru działa również przy jednej grze; listy osadzone w tematach zachowują swój zakres.

## Tagi publikacji - 6.0.8

Moduł Core/PublicationTags blokuje ręczne tagowanie we wszystkich skonfigurowanych działach publikacyjnych, także nieaktywnych. Korzysta z natywnej blokady tagowania IPS i blokuje osobną akcję editTags. Prefiksy zachowują własne kolory i widoczność, a ich nazwy oraz wybrane wersje Minecraft są synchronizowane z natywnymi tagami i indeksem wyszukiwania IPS. Tagi są ukryte w nagłówku tematu. Nie gwarantuje to wzrostu pozycji w wyszukiwarkach.

Migracja uzupełnia dotychczasowe publikacje partiami. Tabela mpcservers_tag_backup przechowuje oryginalne tagi przed pierwszą zmianą. Publikacje, podbicia i powiązania pozostają zachowane. Zadanie status synchronizuje także późniejsze zmiany definicji prefiksów. Usunięcie konfiguracji działu przywraca jego poprzednią flagę tagowania; nie przywraca automatycznie dawnych tagów.

Blokada dotyczy formularzy i akcji użytkowników IPS. Uprzywilejowane integracje zapisujące bezpośrednio do bazy lub przez serwerowy klucz API muszą respektować tę politykę; synchronizacja naprawia zmienione tagi przy odczycie publikacji i w zadaniu cyklicznym.

## Rotacja prefiksów - 6.0.9

Przy co najmniej trzech wybranych prefiksach w jednej grupie trybów widoczny jest jeden, zmieniany co 2,5 sekundy. Grupy są rozpoznawane po nazwie zawierającej tryb, game mode lub server mode. Pozostałe grupy i wersja gry pozostają stałe. Pełna lista trybów znajduje się w podpowiedzi. Najechanie kursorem lub fokus w obszarze prefiksów zatrzymuje rotację. Preferencja ograniczonego ruchu wyłącza animację i pokazuje komplet prefiksów. Obsługiwane są nowe elementy dodane przez AJAX. Bez JavaScript wszystkie prefiksy pozostają widoczne. Zmiana nie modyfikuje zapisanych danych ani tagów. Zakres wersji Minecraft nie ma nawiasów.

## Kolejność oznaczeń na liście działu - 6.0.10

Na liście tematów działu MPC Poleca i aktualne wyróżnienia TOP oraz prefiksy trybów są umieszczane przed tytułem. Wersja Minecraft jest umieszczana po tytule. Zachowane są kolory i rotacja trybów oraz obsługa AJAX. Wyróżnienia TOP korzystają z istniejących ustawień modułu i zakończonych okresów. Serwery polecane nadal nie otrzymują TOP. Ta zmiana kolejności dotyczy listy tematów działu; nie zmienia układu nagłówka otwartego tematu.

## Nagłówek tematu - 6.1.0

Układ z listy działu obowiązuje również w nagłówku otwartego tematu: MPC Poleca i aktualne TOP, prefiksy trybów, tytuł, wersja gry. Rotacja od trzech trybów pozostaje aktywna co 2,5 sekundy. Prefiksy wersji są stałe i bez nawiasów. Obsługiwane są odświeżenia AJAX i edytowalny nagłówek. Zmiana nie modyfikuje zapisanych tytułów, prefiksów ani tagów.

## Wszystkie grupy prefiksów - 6.1.1

Rotacja obejmuje każdą grupę skonfigurowaną w ACP, niezależnie od jej nazwy. Każda grupa z co najmniej trzema wybranymi prefiksami rotuje niezależnie co 2,5 sekundy. Grupy z jednym lub dwoma wyborami pozostają stałe. Dotyczy listy działu, nagłówka tematu oraz odznak w spisie serwerów. Zakres wersji Minecraft pozostaje stały za tytułem na liście działu i w nagłówku tematu; MPC Poleca i TOP nie rotują. Zachowane są kolory, pauza po najechaniu i preferencja ograniczonego ruchu. Bez zmian zapisanych danych.

## Tabela serwerów - 6.1.2

Tabela zachowuje rotację każdej grupy od trzech wybranych prefiksów. Kolejność w komórce nazwy: MPC Poleca lub aktualne TOP, prefiksy, nazwa, wersja gry bez nawiasów. Wersja nie rotuje. Odznaki TOP w tabeli dotyczą aktualnych zwycięzców, zamiast historycznej listy nagród pod nazwą. Historyczne nagrody pozostają zapisane. Zachowane podświetlenia, podium, filtrowanie i AJAX. Dane publikacji nie są migrowane.

## Układ prefiksów i konfiguracja trybów - 6.1.3

Ta wersja zastępuje wcześniejszą rotację osobno dla każdej zwykłej grupy. Układ w liście działu, nagłówku tematu i tabeli serwerów: jedno MPC Poleca lub TOP, jedno miejsce na tryby serwera, maksymalnie trzy zwykłe prefiksy, nazwa, wersja gry. Bez aktywnego wyróżnienia nie ma pustego miejsca. Kilka aktywnych nagród jest oznaczane jednym TOP; dane nagród i historia są zachowane.

Dwa lub więcej wybranych trybów rotują w jednym miejscu co 2,5 sekundy. Wszystkie zwykłe prefiksy stanowią wspólną pulę: do trzech są stałe, powyżej trzech rotują po trzy. Kolory są zachowane. Po najechaniu kursorem lub fokusie w obszarze prefiksów rotacja jest wstrzymana. Pełna lista jest w podpowiedzi. Preferencja ograniczonego ruchu oraz brak JavaScript pokazują komplet oznaczeń.

ACP: moduł Metin2 lub Minecraft > ustawienia > Tryby serwera. Wpisz jeden tryb w wierszu; opcjonalny kolor zapisuj jako Survival {#22aa55}. Limit to 50 trybów, po 40 znaków nazwy. Nie używaj separatorów | , : w nazwach. Formularz publikacji umożliwia wybór jednego lub kilku trybów. Pola są rozdzielone według działów, aby zachować niezależną konfigurację.

Dedykowany edytor korzysta z istniejących definicji trybów i zachowuje identyfikatory grup, opcji, wybory publikacji, filtry i powiązania historycznych rankingów. Grupy trybów są rozpoznawane po nazwie zawierającej tryb, game mode, server mode lub starej nazwie Easy. Jeśli dział nie ma takiej grupy, zapis niepustej listy tworzy Tryby serwera. Puste pole pozostawia dotychczasową konfigurację. Zmiana nazwy opcji ma te same skutki co zmiana zwykłego prefiksu; identyfikator pozostaje stabilny przy zachowaniu nazwy. Nie dodajemy drugiej kopii wyborów do publikacji. Nie jest wymagana migracja danych.

## Kolejność oznaczeń - 6.1.4

Nowa kolejność zastępuje poprzedni układ: wersja gry, MPC Poleca lub TOP, nazwa tematu, tryby serwera, zwykłe prefiksy. Obowiązuje na liście działu, w nagłówku tematu i w tabeli serwerów. Rotacja pozostaje bez zmian: jedno miejsce dla trybów oraz trzy dla zwykłych prefiksów. Wersja gry i wyróżnienie są stałe. Zachowane AJAX, kolory, mobilny układ i zapisane dane.

## Wspólne badge - 6.1.5

Wspólna warstwa wyglądu na stronach forum nadaje badge rozmiar 136 x 28 px, promień 9 px, delikatne obramowanie, gradient i cień. Zachowuje kolory oraz ikony aplikacji. Długie etykiety są skracane wizualnie, a pełny tekst pozostaje w podpowiedzi. Wąskie ekrany zawijają badge do następnych wierszy bez zmiany wymiarów.

Obsługiwane klasy: ipsBadge, mpcs-b, sps-recommended-title, mpcwt-prefix-badge (Prefiksy tematów), newsyLabel i newsyHot (Metin2 News). Inne aplikacje korzystające ze standardowego ipsBadge otrzymują tę samą geometrię. Odrębne, niestandardowe oznaczenia wymagają dodania ich klasy do selektorów shared-badges.css i shared-badges.js. Kolory nie są nadpisywane. Reguły ukrywania rotowanych prefiksów i duplikatów pozostają aktywne.

Pliki shared-badges.css i shared-badges.js są ładowane przez Loader aplikacji mpcservers na froncie forum. Nie wymagają podmiany plików Metin2 News ani Prefiksów tematów, ale wspólny wygląd zależy od aktywnej aplikacji mpcservers. ACP pozostaje bez zmian. Zmiana nie modyfikuje zapisanych etykiet ani danych publikacji.

## Kompaktowe badge - 6.1.8.1

Wydanie oparte na 6.1.5. CSS jest w applications/mpcservers/interface/shared-badges.css. Zwykłe badge mają 96 x 22 px, płaskie tło, promień 6 px, pogrubienie 700 i cień tekstu jak modlogs_score_value. Wersje gry z klasą mpcs-version mają szerokość dopasowaną do treści (36-96 px) i tę samą wysokość 22 px. Nie wymaga zmian we wtyczce rankingu moderacji ani migracji danych.

Awaria produkcji po 6.1.6: log potwierdził Redis OOM i odmowę zapisu datastore IPS. Przyczyna wzrostu pamięci i błędu ładowania rozszerzenia profilu na produkcji nie została jeszcze ustalona. 6.1.8.1 jest zmianą wyglądu, nie naprawą tego incydentu. Przed aktualizacją produkcji administrator powinien potwierdzić sprawność Redis oraz możliwość zapisu. Testy na ipstesty nie odtwarzają warunków produkcyjnego Redis.
