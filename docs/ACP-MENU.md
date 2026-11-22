# Wspólna zakładka ACP

Manifest `applications/mpcservers/data/acpmenu.json` kieruje pozycje tej aplikacji do zakładki `mpcplugins`, wyświetlanej jako **Wtyczki MPCforum**. Kontrolery, moduły i ograniczenia pozostają przypisane do własnej aplikacji. Wspólna zakładka nie oznacza połączenia baz danych ani kodu dodatków.

Do lokalnego `data/lang.xml` dołączono (lub zachowano) etykiety `menutab__mpcplugins` i `menutab__mpcplugins_icon`, aby sama nazwa zakładki nie zależała od zainstalowania `mpcwtyczki`. Różnice względem pobranego źródła rejestruje [SOURCE-MANIFEST.json](SOURCE-MANIFEST.json). W istniejącym forum frazy muszą zostać wczytane do bazy językowej podczas poprawnej aktualizacji/importu; samo wgranie XML nie wystarcza.

Kolejność całego menu jest preferencją ACP, zapisywaną przez IPS osobno dla administratorów i pamiętaną także w przeglądarce. Eksport aplikacji nie zawiera rekordów tych preferencji. Po instalacji można ustawić kolejność natywnym narzędziem zmiany kolejności menu w ACP. Kategorie i poszczególne linki opisuje README.

Po aktualizacji aplikacji sprawdź, czy jej paczka zachowuje wartości `tab`, `activemenuitem`, `module_url`, `restriction_module` i `menu_checks`. Nie usuwaj ograniczeń, aby naprawić widoczność linku: najpierw sprawdź uprawnienia administratora, włączenie aplikacji oraz import fraz.
