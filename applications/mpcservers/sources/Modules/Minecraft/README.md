# Moduł Minecraft

ACP: Minecraft. Uprawnienie: sls_minecraft_manage.

Działy publikacji, prefiksy i grafiki konfiguruje się niezależnie od Metin2. Temat używa standardowej treści posta i wspólnej stopki informacji o serwerze. HTML/proxy nie jest dostępnym trybem, również przy ręcznie zmienionym formularzu. Zachowane wcześniej adresy pozostają w danych.

Status Minecraft Java korzysta z adresu i portu serwera (domyślnie 25565). Wyłączenie statusu nie blokuje publikacji ani podbić. Nagrody za podbicia są opcjonalnym przełącznikiem w ustawieniach gry. Po wyłączeniu nie wydaje się ani nie odbiera kodów; głosowanie działa dalej.

Oznaczony właściciel pobiera plugin MPCforum i instrukcję w Moje serwery. Panel podaje właściwy endpoint forum i identyfikator serwera. Token jest osobny dla serwera, przechowywany po stronie aplikacji jako hash i możliwy do resetu. Nie używaj przykładowego forum.example.org na działającym serwerze. Nie publikuj konfiguracji z tokenem.

Endpoint nagród używa POST i sekretu serwera, nie sesji przeglądarki. Kod jest jednorazowy, a uprawnienie aktualnego właściciela jest ponownie sprawdzane. Komendę nagrody ustawia operator Minecraft. Reset tokenu wymaga aktualizacji konfiguracji pluginu. Moduł korzysta ze wspólnego panelu Community; jego wyłączenie blokuje dostęp właściciela do panelu.

## 6.0.4 - wersje i nagrody testowe

Wersje konfiguruje `Versions` w ACP per gra. `Presentation::normalize` wymaga jednego lub wielu wyborów dla publikacji Minecraft; zwykłe tematy nie wymagają wersji. Baza zachowuje pełną listę JSON w `mc_versions`, a widoki pokazują numerycznie uporządkowany zakres. Usunięte z konfiguracji wersje mogą pozostać w publikacjach, które już je miały. Starych grup prefiksów nie kasujemy automatycznie.

`GameRewards::enabled(game, tid)` jest wspólną bramką dla formularza podbicia, generowania kodu, odbioru API, tokenu i pobrania pluginu. Przy wyłączonych nagrodach pozwala wyłącznie na publikacje z listy wyjątków dla tej gry. Dotychczasowe wartości boolean pozostają zgodne. Testy nie nadają dostępu autorom nieoznaczonym jako właściciele i nie znoszą weryfikacji tokenów.
