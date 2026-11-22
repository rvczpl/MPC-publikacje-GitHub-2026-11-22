# Moduł list

ACP: Listy serwerów. Uprawnienie: sls_lists_manage.

Wspólna lista i spisy wskazane w tematach ACP używają tego samego rendereru. Filtry uwzględniają aktywne gry, grupy prefiksów, status, datę otwarcia i nazwę. Lokalny spis ogranicza wyniki do swojego działu i gry. Prywatne oraz ukryte tematy nie są ujawniane przez listę.

Podbicia wymagają konta. Użytkownik podbija maksymalnie pięć różnych serwerów dziennie, każdy raz. Licznik tygodniowy odnawia się w niedzielę o 23:59; zdarzenia pozostają do statystyk. Temat jest odświeżany dopiero po siedmiu dniach bez aktywności. Wyłączenie modułu blokuje jego kontroler i usuwa spisy z widoku.

Directory odpowiada za listę, ModeFilters za kafelki, Boost za podbicia, Status za kontrolę dostępności, TopicSeo i TopicPreview za podglądy. Renderowanie korzysta z grupowego pobierania tematów. Statusy i cache odświeżają zadania IPS, nie pojedyncze wyświetlenia listy.

Rekomendacje i zwycięzcy są pobierani przez kontrakty Highlights. Gdy moduł wyróżnień jest wyłączony, katalog nie wyświetla ani nie promuje jego oznaczeń. Historia podbić nie jest publicznym niezależnym rankingiem.
