# MPCforum - nagrody Minecraft

Oryginalne źródła wtyczki **MPCforum 3.0.0-beta.2**, powiązanej z aplikacją IPS `mpcservers`. Gracz odbiera jednorazową nagrodę za podbicie publikacji serwera poleceniem `/nagroda KOD`. Forum potwierdza kod, a wtyczka uruchamia lokalnie skonfigurowaną komendę konsoli.

Projekt przygotowano do osobnego repozytorium 22 listopada 2026. Nie zmieniono kodu Java, konfiguracji przykładowej ani numeru wydania. Źródła pochodzą z zachowanego projektu wydania, a nie z dekompilacji JAR.

## Zawartość

- `src/main/java/pl/mpc/sps/ServerRewards.java` - pełny kod wtyczki.
- `src/main/resources/plugin.yml` - nazwa, wersja, komenda i uprawnienie Bukkit.
- `src/main/resources/config.yml` - przykład bez tokenu i danych konkretnego serwera.
- `pom.xml` - oryginalny projekt Maven.
- `releases/MPCforum-3.0.0-beta.2-original.jar` - niezmieniony JAR z wydania używanego przez aplikację forum.
- `docs/PROVENANCE.md`, `docs/VALIDATION.md`, `docs/SOURCE-MANIFEST.json` - pochodzenie i wyniki weryfikacji.

Ten katalog jest samodzielnym projektem wtyczki Minecraft. W pełnej kopii systemu publikacji kod aplikacji IPS znajduje się w sąsiadującym `../applications/mpcservers/`; można również przechowywać go w oddzielnym repozytorium.

## Budowanie

Wymagane są JDK i Maven 3. Projekt kompiluje kod zgodny z Java 8 i deklaruje Bukkit API `1.16`; zależność to `org.spigotmc:spigot-api:1.16.5-R0.1-SNAPSHOT` w zakresie `provided`. Pierwsze budowanie pobiera zależności z Maven Central i oficjalnego repozytorium Spigot.

```sh
mvn -B -ntp clean package
```

Wynik: `target/mpcforum-3.0.0-beta.2.jar`. Dla serwera można nazwać go `MPCforum.jar`. Nie umieszczaj równocześnie oryginalnego i nowo zbudowanego JAR w `plugins/`.

Sprawdzono kompilację na JDK 21.0.13 i Maven 3.9.11. Oryginalny `pom.xml` używa `source`/`target` 8, więc na nowym JDK występują ostrzeżenia o starym poziomie źródeł. Projekt nie zawiera testów automatycznych. Szczegóły i ograniczenia: [VALIDATION.md](docs/VALIDATION.md).

Nowy plik z Maven nie musi mieć identycznej sumy kontrolnej jak oryginał: ma inne metadane pakowania i może powstać innym kompilatorem. Sumy oryginalnego wydania są w `releases/SHA256SUMS.txt`.

## Instalacja i konfiguracja

1. Przygotuj testowy serwer Bukkit/Spigot/Paper zgodny z użytym API. Faktyczny zakres zgodności z wersjami serwera wymaga testu uruchomieniowego.
2. Umieść jeden JAR w `plugins/` i uruchom serwer, aby utworzyć konfigurację.
3. W panelu właściciela publikacji Minecraft na forum wygeneruj konfigurację i token integracji. Ustaw `endpoint`, `server-id` i `api-key` w `plugins/MPCforum/config.yml`.
4. Ustal `reward-command`, czyli jedną komendę konsoli bez początkowego `/`. `%player%` zostanie zastąpione nickiem gracza.
5. Uruchom ponownie serwer i sprawdź log. Nieprawidłowa konfiguracja wyłącza wtyczkę.
6. Sprawdź odbiór kodu oraz odrzucenie ponownego odbioru na środowisku testowym.

`endpoint` musi używać HTTPS. Token ma 64 znaki szesnastkowe i jest przypisany do danego serwera na forum. Wtyczka wymaga `online-mode`; `trusted-proxy: true` jest przeznaczone wyłącznie dla backendu za prawidłowo zabezpieczonym proxy weryfikującym konta.

Uprawnienie `sps.reward` jest domyślnie dostępne dla graczy. Limit prób wynosi 10 sekund na gracza. Wywołanie HTTP jest asynchroniczne, a wykonanie komendy odbywa się w głównym wątku serwera.

## Dziennik nagród

`claims.yml` zapisuje stany odbioru i wykonania komendy. Stany `requesting`, `executing` i `uncertain` wymagają ręcznej kontroli po przerwaniu operacji lub niejednoznacznej odpowiedzi. Nie usuwaj dziennika, aby wymusić ponowne wydanie nagrody. Plik zawiera dane graczy i pozostaje poza repozytorium.

## Dodanie do GitHub

Ten katalog nie zawiera poprzedniej historii `.git` ani ustawionego `origin`. Po utworzeniu pustego repozytorium docelowego:

```sh
git init -b main
git add .
git diff --cached --stat
git commit -m "Import MPCforum Minecraft 3.0.0-beta.2 sources"
git remote add origin ADRES_NOWEGO_REPOZYTORIUM
git push -u origin main
```

Przed dodaniem plików sprawdź, czy nie ma rzeczywistego `config.yml`, tokenów ani dzienników serwera. Przykład w `src/main/resources/config.yml` zawiera pusty token i domenę `forum.example.org`.

## Autorstwo i prawa

Zachowano oryginalne oznaczenia w źródłach. Przygotowanie tej kopii nie nadaje nowej licencji open source. Dostęp do repozytorium i ewentualna dalsza publikacja powinny odpowiadać posiadanym prawom do kodu. Bukkit/Spigot API jest zależnością `provided` i nie jest pakowane do JAR.
