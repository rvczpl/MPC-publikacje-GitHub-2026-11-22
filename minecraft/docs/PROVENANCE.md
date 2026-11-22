# Pochodzenie źródeł

Data przygotowania kopii: 2026-11-22.

Projekt odzyskano z lokalnego, zachowanego wydania `release-mpcforum-beta5/minecraft` z prac nad systemem publikacji z 9 listopada 2026. Nazwa katalogu odnosi się do wydania aplikacji forum; numer samodzielnego pluginu Minecraft w `pom.xml` i `plugin.yml` pozostaje **3.0.0-beta.2**.

Oryginalny `MPCforum.jar` z tego wydania ma SHA-256:

```text
836bd4b58cc5a7502ac0dd9415dd2dae2801b321ce20727012a081eed222fcda
```

Jest identyczny bajtowo z JAR znajdującym się w lokalnym repozytorium `wtyczki-mpc/mpcservers/applications/mpcservers/interface/downloads/` przed synchronizacją oraz z aktualnym eksportem aplikacji `mpcservers` pobranym z serwera h7 22 listopada 2026. Potwierdza to, że zachowane wydanie JAR odpowiada komponentowi obecnemu w aktualnym eksporcie serwera.

Przeszukanie zachowanych projektów Minecraft wykazało tę samą treść `ServerRewards.java` w katalogu roboczym, pierwszym wydaniu oraz wydaniach beta2, beta3, beta4 i beta5. Nie znaleziono nowszego projektu źródłowego tej klasy w przeszukanych katalogach pulpitu i `Documents/Codex`.

`config.yml` i `plugin.yml` z tego projektu są identyczne bajtowo z zasobami wewnątrz oryginalnego JAR. JAR zawiera jedną klasę `pl/mpc/sps/ServerRewards.class`, skompilowaną do poziomu Java 8 (major version 52).

Nie użyto dekompilatora. Pliki Java, POM i zasoby skopiowano bez zmian; dodano wyłącznie dokumentację, ignorowanie plików lokalnych i reguły Git. Kopia nie zawiera danych działającego serwera Minecraft ani rzeczywistych tokenów.
