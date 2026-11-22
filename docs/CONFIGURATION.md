# Konfiguracja — `mpcservers`

Stan źródeł: 22 listopada 2026. Pełny rejestr 43 kluczy z `data/settings.json`; wartości bieżącej instalacji i sekrety z bazy nie należą do repozytorium. Typ pola jest podany tylko wtedy, gdy wynika bezpośrednio z konstruktora formularza. Klucze techniczne mogą nie mieć osobnego pola ACP.

| Klucz | Etykieta / znaczenie z zasobów aplikacji | Pole / format | Wskazówki |
|---|---|---|---|
| `mpcs_tag_groups` | Grupy, których posty mogą zawierać spis | Select | Znacznik [serwery] działa tylko w postach osób z tych grup (grupa główna albo dodatkowa). Administratorzy mogą zawsze. |
| `mpcs_tag_mods` | Także każdy moderator | YesNo | — |
| `mpcs_mod_bypass` | Moderatorzy i administratorzy mogą pominąć prefiksy | YesNo | Moderator działu może wtedy założyć temat bez prefiksów, np. regulamin działu. |
| `mpcs_mover_enabled` | Przenoś nieaktywne publikacje | YesNo | Wyłącznik dla całego forum. |
| `mpcs_default_archive` | Domyślny dział-archiwum | Node | Dla działów publikacji, które nie mają własnego archiwum. |
| `mpcs_batch` | Tematów na jedno uruchomienie zadania | Number | Zadanie działa co 5 minut. Niezależnie od niego pierwsze wejście do działu po terminie przenosi od razu do 5 tematów. |
| `mpcs_per_page` | Serwerów na stronę spisu | Number | — |
| `mpcs_list_pinned` | Pokazuj w spisie przypięte tematy | YesNo | — |
| `mpcs_list_old` | Pokazuj w spisie tematy sprzed dodania działu | YesNo | — |
| `mpcs_list_limit` | Najwięcej tematów branych do spisu | Number | Liczone od ostatnio odświeżonych. Większa liczba to wolniejszy spis w dużych działach. |
| `sps_forums` | Działy prezentacji - ID oddzielone przecinkami | Ustawienie wewnętrzne / odczytaj opis w kodzie | — |
| `sps_categories` | Kategorie - po jednej w wierszu: identyfikator=Nazwa | Ustawienie wewnętrzne / odczytaj opis w kodzie | — |
| `sps_cooldown` | Odstęp pomiędzy głosami użytkownika na serwer (sekundy) | Ustawienie wewnętrzne / odczytaj opis w kodzie | — |
| `sps_paid_enabled` | Płatne podbijanie | Ustawienie wewnętrzne / odczytaj opis w kodzie | Tymczasowo wyłączone. Włączenie wymaga podłączenia operatora płatności, więc przełącznik jest zablokowany. |
| `sps_embed_hosts` | Dozwolone domeny prezentacji HTML - po jednej w wierszu | Ustawienie wewnętrzne / odczytaj opis w kodzie | — |
| `sps_status_enabled` | Włącz cykliczne sprawdzanie statusu | Ustawienie wewnętrzne / odczytaj opis w kodzie | — |
| `sps_forum_options` | sps_forum_options | JSON / zapis wewnętrzny | — |
| `sps_plain_groups` | Grupy uprawnione do tworzenia zwykłych tematów | Select | — |
| `sps_rewards_enabled` | sps_rewards_enabled | Ustawienie wewnętrzne / odczytaj opis w kodzie | — |
| `sps_game_images` | sps_game_images | JSON / zapis wewnętrzny | — |
| `sps_crew_roles` | sps_crew_roles | JSON / zapis wewnętrzny | — |
| `sps_rankings_started` | sps_rankings_started | Ustawienie wewnętrzne / odczytaj opis w kodzie | — |
| `sps_awards_started` | sps_awards_started | Ustawienie wewnętrzne / odczytaj opis w kodzie | — |
| `sps_highlights` | sps_highlights | JSON / zapis wewnętrzny | — |
| `sps_game_icons` | sps_game_icons | JSON / zapis wewnętrzny | — |
| `sps_views_started` | sps_views_started | Ustawienie wewnętrzne / odczytaj opis w kodzie | — |
| `sps_crew_rating_access` | Kto może oceniać osoby | Select | — |
| `sps_game_rewards` | sps_game_rewards | JSON / zapis wewnętrzny | — |
| `mpcs_modular_migration` | mpcs_modular_migration | Ustawienie wewnętrzne / odczytaj opis w kodzie | — |
| `sps_link_mode_enabled` | Udostępnij tryb prezentacji z linku | YesNo | Wyłączenie usuwa ten tryb z formularza i ukrywa podgląd w istniejących publikacjach. Treść posta pozostaje widoczna. Zapisane adresy zostają zachowane, także przy edycji publikacji. |
| `sps_presentation_renderer` | Sposób generowania podglądu | Radio | Proxy PHP jest domyślne i przechowuje kopię przez godzinę. Skrypty mogą działać w izolowanym podglądzie. Generator screenshotów pozostaje niedostępny do czasu wdrożenia osobnej usługi. |
| `sps_proxy_js` | JavaScript w podglądzie proxy | YesNo | Włącza skrypty prezentacji w izolowanym podglądzie, bez dostępu do sesji, cookies i dokumentu forum. Zewnętrzne API nadal wymagają zgodnej konfiguracji CORS. Wyłączenie przywraca statyczny podgląd. |
| `sps_proxy_ajax` | Dociąganie treści prezentacji przez AJAX | YesNo | Przekazuje wyłącznie publiczne odczyty GET w obrębie adresu prezentacji. Nie przesyła cookies, haseł ani danych sesji. Wymaga włączonego JavaScript. Limit: 30 nowych pobrań na minutę dla prezentacji, pozostałe odczyty korzystają z cache. |
| `sps_proxy_secret` | sps_proxy_secret | Ustawienie wewnętrzne / odczytaj opis w kodzie | Wartość poufna; ustaw lokalnie w ACP. Nie dodawaj jej do repozytorium.  |
| `sps_restrict_domains` | Ogranicz prezentacje do dozwolonych domen | YesNo | Po wyłączeniu można podać prezentację HTTPS spoza listy domen działu. Blokady prywatnych adresów, lokalnego forum, przekierowań oraz limity pobierania nadal obowiązują. |
| `sls_modules` | sls_modules | JSON / zapis wewnętrzny | — |
| `sls_game_bindings` | sls_game_bindings | JSON / zapis wewnętrzny | — |
| `sls_game_features` | sls_game_features | JSON / zapis wewnętrzny | — |
| `sls_migrated` | sls_migrated | Ustawienie wewnętrzne / odczytaj opis w kodzie | — |

## Ekrany ACP

| Moduł / pozycja | Etykieta | Parametry adresu po `admin/?` | Ograniczenie ACP |
|---|---|---|---|
| `metin2/settings` | Metin2 — Ustawienia modułu | `app=mpcservers&module=metin2&controller=settings` | `sls_metin2_manage` |
| `metin2/publications` | Metin2 — Działy i prefiksy | `app=mpcservers&module=metin2&controller=publications` | `sls_metin2_manage` |
| `metin2/advanced` | Metin2 — Zaawansowane podglądy prezentacji | `app=mpcservers&module=metin2&controller=advanced` | `sls_metin2_manage` |
| `minecraft/settings` | Minecraft — Ustawienia modułu | `app=mpcservers&module=minecraft&controller=settings` | `sls_minecraft_manage` |
| `minecraft/publications` | Minecraft — Działy i prefiksy | `app=mpcservers&module=minecraft&controller=publications` | `sls_minecraft_manage` |
| `lists/settings` | Listy serwerów — Ustawienia modułu | `app=mpcservers&module=lists&controller=settings` | `sls_lists_manage` |
| `lists/options` | Listy serwerów — Ustawienia list | `app=mpcservers&module=lists&controller=options` | `sls_lists_manage` |
| `lists/log` | Listy serwerów — Dziennik | `app=mpcservers&module=lists&controller=log` | `sls_lists_manage` |
| `highlights/settings` | MPC Poleca i TOP — Ustawienia modułu | `app=mpcservers&module=highlights&controller=settings` | `sls_highlights_manage` |
| `highlights/recommended` | MPC Poleca i TOP — Polecane publikacje | `app=mpcservers&module=highlights&controller=recommended` | `sls_highlights_manage` |
| `community/settings` | Właściciele i konta — Ustawienia modułu | `app=mpcservers&module=community&controller=settings` | `sls_community_manage` |
| `community/identities` | Właściciele i konta — Konta i osoby | `app=mpcservers&module=community&controller=identities` | `sls_community_manage` |

## Ograniczenia administratorów

Poniższe identyfikatory pochodzą z `data/acprestrictions.json`. Dostęp do aplikacji oraz konkretnego modułu nadal podlega natywnym uprawnieniom IPS.

```json
{
  "metin2": {
    "sls_metin2_options": {
      "sls_metin2_manage": "sls_metin2_manage"
    }
  },
  "minecraft": {
    "sls_minecraft_options": {
      "sls_minecraft_manage": "sls_minecraft_manage"
    }
  },
  "lists": {
    "sls_lists_options": {
      "sls_lists_manage": "sls_lists_manage"
    }
  },
  "highlights": {
    "sls_highlights_options": {
      "sls_highlights_manage": "sls_highlights_manage"
    }
  },
  "community": {
    "sls_community_options": {
      "sls_community_manage": "sls_community_manage"
    }
  }
}
```

## Ustawienia zależne od instalacji

ID działów, grup, ról i kanałów należy dobrać w docelowym środowisku. Domyślne wartości z paczki nie dowodzą obecności wskazanych rekordów na innym forum. Nie przenoś ustawień z bazy razem z publicznym repozytorium.


## Uzupełnienie eksportu z 22 listopada 2026

Nowe klucze względem poprzedniego eksportu; szczegóły zachowania i migracji opisuje [changelog](../CHANGELOG.md).

| Klucz | Domyślnie |
|---|---|
| `sps_minecraft_versions` | `{}` |
| `sps_tag_policy_forums` | `{}` |
| `sps_tag_sync_cursor` | `0` |
| `mpcs_feature_hooks` | `{}` |

Bieżące wydanie nie synchronizuje prefiksów z tagami IPS. `sps_tag_policy_forums` i `sps_tag_sync_cursor` zachowano dla zgodności historycznej. `mpcs_feature_hooks` grupuje wyłączniki diagnostyczne, a `sps_minecraft_versions` konfiguruje wersje gry.
