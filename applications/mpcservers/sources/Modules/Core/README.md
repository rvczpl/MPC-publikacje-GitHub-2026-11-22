# Rdzeń i zgodność

Registry definiuje pięć modułów, ich przełączniki, ograniczenia ACP i przypisania zapisanych kluczy gier. Moduły nie mogą samodzielnie omijać Registry przy sprawdzaniu aktywności. ModuleAccess zapewnia adapter starych identyfikatorów uprawnień. ModuleSettings buduje natywne formularze IPS.

Presentation, TopicForm i PresentationView to współdzielona orkiestracja formularza i widoku; polityka gry pochodzi z modułu Metin2/Minecraft. Servers zachowuje format konfiguracji prefiksów, Archive archiwizację, Media magazyn IPS.

Migracja nie zmienia technicznego app_directory ani kluczy obcych. Nowe ustawienia: sls_modules, sls_game_bindings, sls_game_features, sls_migrated. Domyślnie pięć modułów jest aktywne. Migracja rozpoznaje nazwy i istniejące identyfikatory Metin2/Minecraft, ewentualnie nazwę powiązanego działu. Niejednoznaczne stare gry wymagają jawnego przypisania w ACP. Danych takich gier nie usuwa się.

Katalogi Directory, Owners, People i Rankings zawierają adaptery zgodności, nie odrębne moduły produktu. Kontrolery pod starymi adresami są utrzymywane dla linków i integracji. Nowy kod powinien odwoływać się do implementacji w pięciu modułach lub do publicznych adapterów sources/Nazwa/Nazwa.php.

Przed wydaniem sprawdź składnię PHP, schematy JSON, XML tłumaczeń, natywne formularze ACP, publiczne listy, uprawnienia właściciela i izolację proxy. Test migracji porównuje całe rekordy tabel aplikacji z kopią. Nie testuj operacji modyfikujących historię na produkcji.
