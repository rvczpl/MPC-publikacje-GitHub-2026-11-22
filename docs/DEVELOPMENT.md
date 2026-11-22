# Rozwój i wydawanie

## Zawartość eksportu

Źródła pochodzą z katalogu `applications/mpcservers` na serwerze, ze stanu z 22 listopada 2026. Każdy pobrany plik, jego rozmiar i suma SHA-256 znajdują się w [SOURCE-MANIFEST.json](SOURCE-MANIFEST.json). Sekcja `local_preparation_changes` wskazuje jawne lokalne różnice przygotowujące repozytorium. Dane kont, zawartość bazy, katalog uploads, datastore, klucz SSH i konfiguracja połączenia z bazą nie są częścią repozytorium.

To źródła zainstalowanej aplikacji IPS 5.0.21 działającej na PHP 8.3.37. Nie jest to deklaracja minimalnej wersji PHP ani dowód zgodności z każdą wersją IPS 5. Framework Invision Community nie jest dołączony.

## Środowisko

Pracuj na licencjonowanej kopii odpowiedniej wersji IPS i osobnej bazie testowej. Wymagania konkretnych rozszerzeń, usług i zależności opisuje README. Dla integracji używaj testowych kont i sekretów zapisanych w konfiguracji środowiska/ACP. Nie uruchamiaj kontrolerów PHP jako samodzielnych skryptów, jeśli nie są jawnie przeznaczone dla CLI.

## Walidacja źródeł

Skrypt wymaga Python 3.10+. Parsuje JSON i XML, sprawdza obecność pobranych plików oraz znane sygnatury przypadkowo umieszczonych kluczy. Nie jest audytem bezpieczeństwa ani testem działania IPS.

```powershell
python scripts/validate.py
python scripts/validate.py --php C:/sciezka/do/php.exe
```

Opcja `--php` uruchamia wyłącznie `php -l`, bez wykonywania kodu aplikacji. Użyj interpretera właściwego dla docelowego IPS. Raport bez `--php` nie oznacza wykonania sprawdzania składni PHP. Ewentualne już istniejące problemy są opisane w [VALIDATION.md](VALIDATION.md).

## Instalacja i aktualizacja

Nowa instalacja wymaga poprawnego pakietu aplikacji IPS, który instaluje moduły, języki, schemat i migracje. Nie wystarczy skopiowanie katalogu. Przygotowanie TAR wykonuj w Developer Center odpowiedniej wersji IPS, z dostępnymi zasobami developerskimi danej aplikacji. Eksport wdrożenia może zawierać XML wynikowy i nie zawierać kompletnego dev/; wtedy najpierw odtwórz/importuj zasoby zgodnie z warsztatem IPS i sprawdź rezultat. Nie traktuj zwykłego tarowania repozytorium jako poprawnego instalatora. Aktualizację z danej wersji trzeba sprawdzić na kopii bazy; nie odinstalowuj aplikacji przed aktualizacją.

Przed zmianą produkcyjną zrób spójną kopię bazy i plików. Weryfikuj instalację od zera, aktualizację istniejącej wersji, ograniczonego administratora oraz zwykłego użytkownika. Włączaj taski, synchronizacje i operacje zewnętrzne dopiero po konfiguracji środowiska testowego. Do README wpisuj faktycznie wykonane testy i ich wynik.

## Wycofanie

Przywróć razem właściwą kopię plików i bazy; samo wgranie starszego kodu może być niezgodne z migracją. Cofnięcie commita Git przywraca źródła, ale nie cofa zmian danych ani zewnętrznych operacji wykonanych przez integracje.

## Autorstwo i licencje

Zachowano metadane autorów, nagłówki źródeł oraz dołączone licencje bibliotek, fontów i pozostałych zasobów. Nie dodano licencji MIT/GPL ani innej nowej licencji do kodu aplikacji. Brak pliku LICENSE nie oznacza domeny publicznej. Informacje szczegółowe i zależności znajdują się w README oraz plikach źródłowych.

Git normalizuje końce linii plików tekstowych zgodnie z `.gitattributes`. Sumy źródłowe w manifeście dotyczą bajtów pobranych z serwera przed taką normalizacją.
