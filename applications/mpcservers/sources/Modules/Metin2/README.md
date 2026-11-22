# Moduł Metin2

ACP: Metin2. Uprawnienie: sls_metin2_manage.

Obejmuje przypisanie działów, prefiksy, ikonę i datę otwarcia, zwykłe posty oraz podglądy stron przez proxy. Zachowuje dotychczasowy wygląd tematu: osobny podgląd, zwinięty standardowy post i stopka informacji. Zaawansowane ustawienia proxy mają osobny ekran ACP. Wyłączenie gry ukrywa jej publikacje w katalogu; posty forum pozostają zapisane.

Działy konfiguruj w Działy i prefiksy. Każda grupa ma nazwę, listę prefiksów i regułę wyboru. Grafiki są opcjonalne. Ustawienia wyróżnień wymagają także uprawnienia modułu Highlights i jego aktywności. Ustawienia ekip pozostają zależne od uprawnień Community.

Proxy dopuszcza tylko publiczny HTTPS na porcie 443, waliduje każdy cel i przekierowanie, blokuje adresy prywatne i własne forum. Pobrania mają limity czasu, rozmiaru i pamięć podręczną. Skrypty nie dostają cookies forum. Iframe celowo nie ma allow-same-origin. Nie usuwaj izolacji, aby obejść błędy zewnętrznego kodu.

AJAX jest opcjonalny: podpisane żądania GET ograniczone do źródłowej domeny i katalogu, wygasający podpis, limit nowych pobrań, brak cookies i autoryzacji. Statyczne fonty i CSS są obsługiwane osobno. CSP nie dopuszcza dowolnych zewnętrznych usług. POST, logowanie i zewnętrzne API mogą nie działać. Link do oryginału jest zawsze dostępny.

Klasy Policy, PresentationProxy, ProxyAjax, ProxyAssets i ProxySecret należą do tego modułu. ProxySecret zachowuje istniejący sekret i nie tworzy nowego podczas weryfikacji podpisu. Diagnostyka: mpcservers_proxy oraz mpcservers_proxy_fetch. Cache i podpisy mogą wymagać odświeżenia po aktualizacji; nie ujawniaj podpisanych adresów ani sekretów w publicznych logach.
