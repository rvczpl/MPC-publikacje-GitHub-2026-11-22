# Zmiany w projekcie

1. Przeczytaj README i [warsztat rozwoju](docs/DEVELOPMENT.md).
2. Utwórz osobną gałąź. Zapisz problem, oczekiwane zachowanie i zakres danych, które zmiana dotyka.
3. Zachowaj kontrolę uprawnień, CSRF oraz zgodność migracji. Nie zmieniaj identyfikatora aplikacji/wtyczki bez jawnego planu migracji.
4. Uzupełnij dokumentację ustawień, endpointów i zależności, których dotyczy zmiana.
5. Uruchom walidację źródeł i odpowiednie testy na kopii IPS. W opisie PR podaj rzeczywiście sprawdzone scenariusze oraz ograniczenia testów.
6. Przejrzyj `git diff` przed commitem. Nie dołączaj sekretów, konfiguracji serwera, danych użytkowników ani zrzutów produkcyjnej bazy.

Publikowanie i ustawianie zdalnego repozytorium opisuje [GITHUB.md](docs/GITHUB.md).
