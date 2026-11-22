# Walidacja bieżącego eksportu — 22 listopada 2026

- Źródła: 295 plików; kompletność, SHA-256 i lokalne etykiety przygotowania zgodne z manifestem.
- PHP: 193 plików poprawnych składniowo na PHP 8.3.37; bez błędów i ostrzeżeń. Wykonano `php -l`, bez uruchamiania aplikacji.
- JSON: 67; XML: 4 — poprawnie sparsowane.
- Lokalny `scripts/validate.py`: zakończony powodzeniem; skan sygnatur sekretów bez zgłoszeń.

Nie wykonywano nowych instalacji, migracji ani testów zapisujących dane podczas tego odświeżenia. Historia wcześniejszych kontroli znajduje się niżej.

---

# Weryfikacja eksportu — 19 listopada 2026

- Pliki źródłowe: 245; kompletność i sumy źródłowego snapshotu sprawdzone.
- Sprawdzenie składni PHP: **177 plików, PHP 8.3.37, bez błędów**. Polecenie `php -l` nie uruchamia aplikacji. Sumy sprawdzonych plików porównano z pobranym snapshotem.
- JSON: 44 plików poprawnie sparsowanych.
- XML: 4 plików poprawnie sparsowanych.
- Skan znanych sygnatur kluczy, tokenów i webhooków nie wykrył poświadczeń w źródłach. Dwa ogólne dopasowania w całym eksporcie zweryfikowano jako definicję przełącznika oraz nazwę klucza stanu; nie były sekretami.
- Lokalne przygotowanie źródeł: 1 plików z jawnie odnotowaną zmianą etykiet menu; szczegóły w [SOURCE-MANIFEST.json](SOURCE-MANIFEST.json).

Nie uruchamiano nowej instalacji, migracji, testów przeglądarkowych, zadań integracji ani operacji na danych użytkowników w ramach tego eksportu. Poprawna składnia nie dowodzi zgodności funkcjonalnej z inną wersją IPS. Punktem odniesienia jest działająca instalacja IPS 5.0.21, z której pobrano źródła.

Walidator lokalny i plan odbioru opisują [DEVELOPMENT.md](DEVELOPMENT.md) oraz README. Brak poświadczeń w skanie sygnatur nie zastępuje przeglądu zmian przed publikacją.
