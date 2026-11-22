# Weryfikacja - 2026-11-22

- Oryginalne źródła i zasoby: cztery pliki, zachowane bez zmian.
- Oryginalny JAR: porównano SHA-256 z wydaniem, wcześniejszym repozytorium wtyczek oraz aktualnym eksportem serwera h7 z 22 listopada 2026 - zgodność.
- Zasoby `plugin.yml` i `config.yml`: porównanie bajtowe z oryginalnym JAR - zgodność.
- Kompilacja: `mvn -B -ntp package`, Apache Maven 3.9.11, JDK 21.0.13 - **BUILD SUCCESS**.
- Wynik budowania zawiera `pl/mpc/sps/ServerRewards.class`, `plugin.yml` i `config.yml`; klasa ma major version 52 (Java 8).
- Zależność Spigot API jest oznaczona `provided`, a wynikowy JAR nie zawiera klas `org.bukkit`.
- Zapisane przykłady konfiguracji zawierają pusty `api-key`, `server-id: 0` i domenę przykładową.

Kompilator zgłosił ostrzeżenia o poziomie `source/target` 8 na nowym JDK oraz użyciu przestarzałego API. Oryginalnego kodu ani POM nie zmieniano w ramach eksportu.

Projekt nie zawiera testów automatycznych (`No tests to run`). Nie uruchamiano serwera Minecraft, pluginu ani żądań odbioru nagrody do forum. Kompilacja nie jest potwierdzeniem testu integracyjnego. Nie deklarujemy identyczności bajtowej JAR zbudowanego Mavenem z oryginalnym archiwum wydania.
