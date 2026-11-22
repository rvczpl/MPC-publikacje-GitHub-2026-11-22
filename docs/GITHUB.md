# Dodanie do nowego repozytorium GitHub

Ta samodzielna kopia obejmuje źródła aplikacji IPS oraz projekt Java w `minecraft/`. Nie zawiera historii `.git` ani skonfigurowanego `origin`; nie będzie automatycznie wysyłać zmian do repozytorium wtyczki-mpc.

Utwórz puste repozytorium docelowe. Następnie w katalogu głównym tej kopii:

```sh
git init -b main
git add .
git diff --cached --stat
git commit -m "Import publications 6.1.8.5 and Minecraft plugin sources"
git remote add origin ADRES_NOWEGO_REPOZYTORIUM
git push -u origin main
```

Zastąp `ADRES_NOWEGO_REPOZYTORIUM` adresem swojego repozytorium. Nie dołączaj konfiguracji działającego forum/Minecraft, tokenów, plików `claims.yml`, baz ani katalogu `target/`.

Sprawdzenie eksportu IPS: `python scripts/validate.py`. Projekt Java buduje się poleceniem `mvn -B -ntp clean package` uruchomionym w katalogu `minecraft`. Wynik to `minecraft/target/mpcforum-3.0.0-beta.2.jar`; oryginalny plik wydania zachowano w `minecraft/releases/`. Szczegóły w [README](../README.md) i [instrukcji Java](../minecraft/README.md).

Nie nadano nowej licencji open source ani nie utworzono repozytorium zdalnego w ramach przygotowania tej kopii.
