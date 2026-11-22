# System publikacji serwerów MPCforum

> Samodzielna kopia źródeł przygotowana 22 listopada 2026 do nowego repozytorium GitHub. Zawiera aplikację IPS **6.1.8.5** i oryginalny projekt Java **3.0.0-beta.2**.

`mpcservers` obsługuje publikacje serwerów powiązane z tematami forum: listy i filtry, Metin2 i Minecraft, prezentacje, statusy, podbicia, wyróżnienia oraz rejestr ekip i panel właściciela. Aktualny wpis metadanych to **6.1.8.5 / 60113**, a kod eksportowano z IPS 5.0.21 22 listopada 2026.

## Szybki start repozytorium

- `applications/mpcservers/` — aktualny kod aplikacji IPS.
- `minecraft/` — pełne oryginalne źródła Java, Maven, zasoby i oryginalny JAR.
- `CHANGELOG.md` — wszystkie zmiany publikacji od poprzedniej kopii.
- `docs/` — konfiguracja, architektura, migracje, manifest i wyniki sprawdzeń.

Projekt nie zawiera starego `.git`, zdalnego `origin`, danych forum ani danych serwera Minecraft. Instrukcję publikacji do wybranego repozytorium znajdziesz w [docs/GITHUB.md](docs/GITHUB.md). To kopia źródeł; instalator IPS TAR przygotowuje się oddzielnie.

## Zakres repozytorium

Repozytorium zawiera dostępne na serwerze źródła aplikacji IPS i zasoby jej wydania. `interface/downloads/MPCforum.jar` jest gotowym komponentem integracji Minecraft. Oryginalne źródła Java, projekt Maven, przykładowa konfiguracja i oryginalny JAR są teraz w [minecraft/](minecraft/README.md). Potwierdzono zgodność JAR z aktualnym eksportem i udane budowanie; nowy plik wynikowy nie musi być identyczny bajtowo z oryginałem.

Wewnątrz katalogu aplikacji znajduje się aktualny [README wydania 6.1.8.5](applications/mpcservers/README.md), dokumentacja modułów i zachowane opisy wcześniejszych aktualizacji. Historyczne informacje należy odczytywać wraz z bieżącym [changelogiem](CHANGELOG.md), który wskazuje również rozwiązania później wycofane.

Pełna historia zmian 6.0.1 → 6.1.8.5: [CHANGELOG.md](CHANGELOG.md). Bieżące prefiksy publikacji są niezależne od tagów IPS. Archiwizacja działa w zadaniach w tle, z blokadami, przerwą po błędzie i kontrolą dostępności pamięci Redis.

## Moduły produktu

| Moduł | Funkcje | Uprawnienie ACP |
|---|---|---|
| Metin2 | Działy publikacji, prefiksy, prezentacje zwykłe i zdalne, zaawansowane proxy | `sls_metin2_manage` |
| Minecraft | Działy, prezentacje standardowe, status serwera i opcjonalne nagrody | `sls_minecraft_manage` |
| Listy | Katalogi, filtry, podbicia, ustawienia list i dziennik | `sls_lists_manage` |
| MPC Poleca i TOP | Rekomendacje, TOP i rozliczane okresy wyróżnień | `sls_highlights_manage` |
| Osoby i właściciele | Tożsamości, role ekip, historia, powiązania, oceny i prywatny panel | `sls_community_manage` |

Przełączniki i przypisania gier obsługuje `Registry`; wyłączenie modułu zachowuje jego dane. Kontrolery `owners`, `people`, `rankings` i `servers` w części ścieżek są adapterami zachowującymi zgodność adresów. Nie oznacza to istnienia dodatkowych niezależnych aplikacji.

## Wymagania i pierwsza konfiguracja

Aplikacja wymaga aktywnego **Forums**: bazuje na tematach, postach, działach, tagach, grupach i uprawnieniach widoczności. Korzysta z harmonogramu, magazynu plików i bazy IPS. Sprawdzanie statusu serwerów oraz zdalne prezentacje wymagają odpowiednich połączeń wychodzących. Wydanie nie jest samodzielnym katalogiem WWW.

Menu: **Wtyczki MPCforum → System publikacji**. Dwanaście pozycji prowadzi do pięciu modułów; linki nadal obsługują własne kontrolery aplikacji.

1. Skonfiguruj grupy mogące korzystać z publikacji i praw moderacyjnych, a następnie przełączniki modułów.
2. W Metin2 i Minecraft wskaż właściwe działy, przypisz gry i przygotuj grupy prefiksów. Nie przypisuj innych gier wyłącznie po to, aby usunąć ostrzeżenie migracji.
3. Ustal archiwum, okresy i automatyczne przenoszenie nieaktywnych tematów; skonfiguruj listy i limity.
4. Skonfiguruj polecane publikacje i zasady TOP. Uprawnienia do rekomendacji są niezależne od edycji ustawień konkretnej gry.
5. Zdefiniuj role ekip i powiąż właścicieli z właściwymi kontami forum. Skontroluj dostęp do prywatnego panelu.
6. Dopiero po testach funkcji podstawowych włącz zewnętrzne prezentacje, statusy i nagrody Minecraft.

Pełne 43 klucze ustawień, w tym agregaty JSON `sls_modules`, `sls_game_bindings`, `sls_game_features`, opisuje [CONFIGURATION.md](docs/CONFIGURATION.md). Nie wszystkie wartości mają prosty pojedynczy formularz — część zapisują wyspecjalizowane edytory.

## Publikacje i prefiksy

Publikacja jest związana z tematem w skonfigurowanym dziale. `TopicForm`, rozszerzenie `UIItem` i listenery łączą formularz tematu, pierwszy post i widok prezentacji. Dodatkowe pola oraz dozwolone tryby pochodzą z polityki gry. Wyłączenie modułu gry nie usuwa pierwotnych postów.

Format grup prefiksów zachowuje postać `Nazwa grupy: opcja | opcja`; dopiski `(kilka)` i `(max 2)` określają wielokrotność wyboru. Nazwy tworzą klucze istniejących deklaracji. Zmiana nazwy grupy/opcji nie przepisuje automatycznie już zapisanych danych; samo przestawienie kolejności jest inną operacją.

Lista filtruje treści według dostępności tematu dla odwiedzającego. Publiczne podstawowe wejście to `index.php?app=mpcservers&module=directory&controller=browse`; panel właściciela to `module=owners&controller=panel`, osoby `module=people&controller=browse`, wyróżnienia `module=highlights&controller=browse`. Stary kontroler rankingów zachowuje przekierowanie do TOP.

## Właściciele, ekipy i statystyki

Osoba w rejestrze może być powiązanym kontem IPS albo nazwą bez konta. Role ekip nie nadają automatycznie grup forum. Zakończenie współpracy zachowuje historię. Role, współpraca i oceny mają odrębne rekordy.

Dostęp do prywatnych danych serwera wymaga aktualnej roli **owner** połączonej z zalogowanym kontem. Samo autorstwo tematu ani rola technician nie zastępują owner. Wspólną kontrolę wykonuje `OwnerAccess`. Statystyki obejmują dostępne okresy podbić, pozycji i wejść; dane sprzed rozpoczęcia pomiaru nie powstają wstecz.

Oceny ekip mieszczą się w skali 1–5; głos jest aktualizowalny, a ocenianie siebie jest blokowane. Konfiguracja ustala, czy głosować mogą wszyscy zalogowani, czy osoby z historią współpracy. Blokada moderacyjna może chronić skład ekipy przed edycją autora.

## Metin2: prezentacje zdalne

Moduł proxy pobiera zdalną stronę i prezentuje ją w izolowanym iframe. Nie jest pełną przeglądarką i nie gwarantuje działania zewnętrznych skryptów, logowania lub usług. Kod dopuszcza publiczne HTTPS na porcie 443, waliduje cele i przekierowania oraz blokuje adresy prywatne i własne forum. Istnieją limity czasu, wielkości odpowiedzi i pamięć podręczna.

Opcjonalne AJAX używa podpisanych żądań GET ograniczonych do dozwolonego źródła. Cookies forum i autoryzacja nie są przekazywane. `allow-same-origin` celowo nie jest częścią izolacji; nie należy usuwać ograniczeń, aby wymusić zgodność obcej strony. Sekret `sps_proxy_secret` jest lokalnym ustawieniem i nie należy do konfiguracji przeznaczonej do publikacji na GitHub.

Szczegóły: [moduł Metin2](applications/mpcservers/sources/Modules/Metin2/README.md). Diagnostyka używa m.in. kanałów `mpcservers_proxy` i `mpcservers_proxy_fetch`. Generator zrzutów wymaga osobnego silnika renderowania i nie jest dostarczonym działającym backendem tej paczki.

## Minecraft i nagrody

Minecraft używa standardowej treści posta; tryb HTML/proxy nie jest dostępny. Status Java korzysta z adresu i portu (zwykle 25565). Wyłączenie statusu nie wyłącza publikacji i podbić.

Opcjonalna integracja nagród udostępnia właścicielowi instrukcję, endpoint, ID serwera i pobranie `MPCforum.jar`. Każdy serwer ma osobny token; aplikacja przechowuje jego hash, a reset wymaga zmiany konfiguracji po stronie Minecraft. Endpoint używa POST i sekretu serwera, a kody nagrody są jednorazowe. Operator Minecraft ustala wykonywaną komendę. Dokładny adres należy skopiować z działającego panelu właściciela, a nie przepisywać przykładową domenę.

Rozwój samego pluginu Minecraft opisuje [projekt Java](minecraft/README.md). Wersja pluginu to 3.0.0-beta.2, niezależna od numeracji aplikacji IPS. Sprawdzono kompilację, bez uruchamiania serwera Minecraft i odbioru rzeczywistych nagród.

## Dane i zadania

Aplikacja deklaruje 17 tabel obejmujących konfigurację działów, tematy/prezentacje, głosy i podbicia, nagrody, osoby/ekipy/audyt, wyróżnienia/okresy, blokady, wyświetlenia i oceny. Schemat kolumn/indeksów: [DATA-MODEL.md](docs/DATA-MODEL.md).

- `mover`, co 5 minut: wywołuje `Archive::runTask()` i obsługuje przenoszenie nieaktywnych publikacji zgodnie z konfiguracją.
- `status`, co minutę: sprząta cache proxy/AJAX, rozlicza wyróżnienia, usuwa stare dane przejściowe nagród/głosów i sprawdza statusy kwalifikujących się serwerów w ograniczonych partiach. Część porządkowa działa także przy wyłączonym samym pomiarze statusu.

Migracje nie powinny wymagać zmiany `app_directory` ani przepisywania identyfikatorów tematów. Historia numeracji zawiera `50000 → 1.0.0` oraz późniejsze `60000 → 6.0.0`; nie sortuj wersji migracji według tekstu wyświetlanej nazwy.

## Scenariusze odbioru i diagnoza

- Utwórz publikację obu gier, sprawdź pola, prefiksy, widok tematu, katalog i filtry dla gościa oraz użytkownika z ograniczeniami.
- Wyłącz pojedynczy moduł i potwierdź ukrycie funkcji z zachowaniem danych.
- Zweryfikuj role owner/technician/autor bez powiązania oraz bezpośrednie adresy statystyk i tokenów.
- Przy braku katalogu sprawdź przypisania gry/działu, zatwierdzenie tematu i widoczność dla bieżącej osoby.
- Przy nieznanym statusie sprawdź protokół, adres/port, przełącznik i zadanie `status`; nie pomijaj ograniczeń bezpieczeństwa połączeń.
- Przy niezgodnej prezentacji użyj linku do oryginału i diagnostyki proxy; zewnętrzne logowanie i żądania POST nie stanowią gwarantowanej funkcji.
- Nagrody testuj na osobnym serwerze: pojedyncze odebranie kodu, ponowna próba, błędny token, reset i utrata roli właściciela.
- Migrację przetestuj na kopii bazy, porównując identyfikatory i historię ekip, głosów i wyróżnień przed/po. Nie uruchamiaj jej eksperymentalnie na danych produkcyjnych.


## Instalacja, aktualizacja i wycofanie

Repozytorium przechowuje kod w `applications/mpcservers/`. Nie jest gotowym archiwum instalacyjnym TAR i nie zawiera platformy IPS, jej klucza licencji, `conf_global.php` ani kopii bazy. Nie uruchamia się jako samodzielny projekt PHP.

1. Przygotuj testową instalację IPS zgodną z używaną wersją. Potwierdzone środowisko eksportu to **IPS 5.0.21 / PHP 8.3**; nie jest to deklaracja minimalnej wersji wspieranej przez każdy historyczny krok aktualizacji.
2. Przed instalacją lub aktualizacją zachowaj pliki, bazę i powiązane pliki przesłane do magazynu IPS.
3. Przygotuj paczkę instalacyjną przez właściwy dla IPS Developer Center proces budowania aplikacji. W razie braku zasobów `dev/` odtwórz/importuj zasoby deweloperskie z metadanych i eksportów aplikacji; repozytorium nie deklaruje, że taki pełny projekt deweloperski został już odtworzony. Nie zastępuj tego procesu zwykłym spakowaniem katalogu poleceniem `tar`.
4. Nową aplikację zainstaluj przez instalator aplikacji ACP. Sama kopia katalogu nie tworzy tabel, ustawień, modułów ani rejestracji rozszerzeń. Istniejącą aplikację aktualizuj pod tym samym technicznym identyfikatorem `mpcservers`, z wykonaniem jej kroków `setup/upg_*` przez IPS.
5. Skonfiguruj aplikację i uprawnienia, następnie przejdź scenariusze odbioru opisane poniżej. Sprawdź [wspólne menu ACP](docs/ACP-MENU.md).

Podmiana plików na istniejącej instalacji jest dopuszczalnym etapem wdrożenia kodu tylko wtedy, gdy osobno obsłużono zmiany schematu i zasobów. Nie wykonuj ponownej instalacji zamiast aktualizacji. Wycofanie po migracji polega na przywróceniu zgodnego zestawu plików i bazy, a gdy aplikacja zapisuje media — także odpowiedniego magazynu plików. Instalacja starszego TAR sama nie cofa migracji.

## Praca nad kodem i GitHub

- [Rozwój, walidacja i przygotowanie wydania](docs/DEVELOPMENT.md).
- [Dodanie tego lokalnego repozytorium do GitHub](docs/GITHUB.md).
- [Zasady współpracy](CONTRIBUTING.md) i [zgłaszanie problemów bezpieczeństwa](SECURITY.md).
- [Manifest pobranych plików](docs/SOURCE-MANIFEST.json), [pełna konfiguracja](docs/CONFIGURATION.md), [schemat danych](docs/DATA-MODEL.md), [architektura i lista migracji](docs/ARCHITECTURE.md).

Dokumentację opracowano na podstawie eksportu źródeł. Przy jej pisaniu nie wykonywano instalacji od zera, migracji danych ani testów funkcjonalnych zewnętrznych usług. Scenariusze odbioru są listą do wykonania w testowym środowisku, a nie deklaracją zakończonych testów.

## Autorstwo i licencja

Metadane aplikacji wskazują autora RafalR. Gotowy JAR Minecraft jest osobnym dostarczonym komponentem. Zachowano oznaczenia autorów w źródłach. Repozytorium nie nadaje nowej licencji otwartego oprogramowania. Prawa do aplikacji i do platformy IPS pozostają rozdzielone; istniejące noty licencyjne bibliotek i zasobów nadal obowiązują.
