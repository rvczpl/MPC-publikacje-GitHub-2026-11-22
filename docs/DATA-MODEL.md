# Model danych

Deklaracje schematu `mpcservers`. Nazwy tabel podano bez prefiksu konfigurowanego przez IPS. To dokumentacja struktury, bez danych użytkowników. Źródło: [`schema.json`](../applications/mpcservers/data/schema.json). Dodatkowe migracje lub instalatory PHP mogą uzupełniać deklaracje; ich listę zawiera [architektura](ARCHITECTURE.md).

## `mpcservers_forums`

| Kolumna | Typ | NULL | Auto increment | Komentarz |
|---|---|---|---|---|
| `forum_id` | `INT(10) UNSIGNED` | nie | nie | — |
| `enabled` | `TINYINT(1) UNSIGNED` | nie | nie | — |
| `description` | `TEXT` | tak | nie | — |
| `defs` | `MEDIUMTEXT` | tak | nie | — |
| `archive_id` | `INT(10) UNSIGNED` | nie | nie | — |
| `days` | `INT(10) UNSIGNED` | nie | nie | — |
| `activity` | `VARCHAR(10)` | nie | nie | — |
| `lock_moved` | `TINYINT(1) UNSIGNED` | nie | nie | — |
| `skip_pinned` | `TINYINT(1) UNSIGNED` | nie | nie | — |
| `since` | `INT(10) UNSIGNED` | nie | nie | — |
| `updated` | `INT(10) UNSIGNED` | nie | nie | — |
| `updated_by` | `BIGINT(20) UNSIGNED` | nie | nie | — |
| `checked` | `INT(10) UNSIGNED` | nie | nie | — |

Indeksy:

- `PRIMARY` (primary): `forum_id`.

## `mpcservers_topics`

| Kolumna | Typ | NULL | Auto increment | Komentarz |
|---|---|---|---|---|
| `tid` | `BIGINT(20) UNSIGNED` | nie | nie | — |
| `forum_id` | `INT(10) UNSIGNED` | nie | nie | — |
| `attrs` | `TEXT` | tak | nie | — |
| `updated` | `INT(10) UNSIGNED` | nie | nie | — |
| `restored` | `INT(10) UNSIGNED` | nie | nie | — |

Indeksy:

- `PRIMARY` (primary): `tid`.
- `forum_id` (key): `forum_id`.

## `mpcservers_log`

| Kolumna | Typ | NULL | Auto increment | Komentarz |
|---|---|---|---|---|
| `id` | `BIGINT(20) UNSIGNED` | nie | tak | — |
| `tid` | `BIGINT(20) UNSIGNED` | nie | nie | — |
| `title` | `VARCHAR(255)` | nie | nie | — |
| `forum_from` | `INT(10) UNSIGNED` | nie | nie | — |
| `forum_to` | `INT(10) UNSIGNED` | nie | nie | — |
| `author_id` | `BIGINT(20) UNSIGNED` | nie | nie | — |
| `last_activity` | `INT(10) UNSIGNED` | nie | nie | — |
| `moved_at` | `INT(10) UNSIGNED` | nie | nie | — |
| `imported` | `TINYINT(1) UNSIGNED` | nie | nie | — |

Indeksy:

- `PRIMARY` (primary): `id`.
- `tid` (key): `tid`.
- `moved_at` (key): `moved_at`.

## `mpcservers_presentations`

| Kolumna | Typ | NULL | Auto increment | Komentarz |
|---|---|---|---|---|
| `tid` | `INT(10) UNSIGNED` | nie | nie | — |
| `category` | `VARCHAR(40)` | nie | nie | — |
| `mode` | `VARCHAR(10)` | nie | nie | — |
| `url` | `VARCHAR(2000)` | nie | nie | — |
| `host` | `VARCHAR(255)` | nie | nie | — |
| `port` | `INT(10) UNSIGNED` | nie | nie | — |
| `protocol` | `VARCHAR(16)` | nie | nie | — |
| `status` | `VARCHAR(16)` | nie | nie | — |
| `checked` | `INT(10) UNSIGNED` | nie | nie | — |
| `players` | `INT(10) UNSIGNED` | nie | nie | — |
| `capacity` | `INT(10) UNSIGNED` | nie | nie | — |
| `bumped` | `INT(10) UNSIGNED` | nie | nie | — |
| `votes` | `INT(10) UNSIGNED` | nie | nie | — |
| `api_hash` | `VARCHAR(64)` | nie | nie | — |
| `is_server` | `TINYINT(1) UNSIGNED` | nie | nie | — |
| `icon` | `VARCHAR(2000)` | nie | nie | — |
| `featured` | `TINYINT(1) UNSIGNED` | nie | nie | — |
| `open_date` | `VARCHAR(10)` | nie | nie | — |
| `api_owner` | `INT(10) UNSIGNED` | nie | nie | — |
| `mc_versions` | `TEXT` | tak | nie | Wybrane wersje Minecraft jako JSON. |

Indeksy:

- `PRIMARY` (primary): `tid`.
- `ranking` (key): `bumped, tid`.
- `checking` (key): `checked`.

## `mpcservers_votes`

| Kolumna | Typ | NULL | Auto increment | Komentarz |
|---|---|---|---|---|
| `tid` | `INT(10) UNSIGNED` | nie | nie | — |
| `member_id` | `INT(10) UNSIGNED` | nie | nie | — |
| `created` | `INT(10) UNSIGNED` | nie | nie | — |

Indeksy:

- `PRIMARY` (primary): `tid, member_id`.

## `mpcservers_rewards`

| Kolumna | Typ | NULL | Auto increment | Komentarz |
|---|---|---|---|---|
| `code_hash` | `VARCHAR(64)` | nie | nie | — |
| `tid` | `INT(10) UNSIGNED` | nie | nie | — |
| `member_id` | `INT(10) UNSIGNED` | nie | nie | — |
| `player` | `VARCHAR(16)` | nie | nie | — |
| `created` | `INT(10) UNSIGNED` | nie | nie | — |
| `expires` | `INT(10) UNSIGNED` | nie | nie | — |
| `claimed` | `INT(10) UNSIGNED` | nie | nie | — |

Indeksy:

- `PRIMARY` (primary): `code_hash`.
- `expiry` (key): `expires`.

## `mpcservers_people`

| Kolumna | Typ | NULL | Auto increment | Komentarz |
|---|---|---|---|---|
| `id` | `INT(10) UNSIGNED` | nie | tak | — |
| `identity_key` | `VARCHAR(80)` | nie | nie | — |
| `member_id` | `INT(10) UNSIGNED` | nie | nie | — |
| `name` | `VARCHAR(120)` | nie | nie | — |
| `created_by` | `INT(10) UNSIGNED` | nie | nie | — |
| `created_at` | `INT(10) UNSIGNED` | nie | nie | — |
| `merged_into` | `INT(10) UNSIGNED` | nie | nie | — |

Indeksy:

- `PRIMARY` (primary): `id`.
- `identity` (unique): `identity_key`.
- `member` (key): `member_id`.
- `member_lookup` (key): `member_id, merged_into`.

## `mpcservers_crew`

| Kolumna | Typ | NULL | Auto increment | Komentarz |
|---|---|---|---|---|
| `id` | `INT(10) UNSIGNED` | nie | tak | — |
| `tid` | `INT(10) UNSIGNED` | nie | nie | — |
| `person_id` | `INT(10) UNSIGNED` | nie | nie | — |
| `game` | `VARCHAR(40)` | nie | nie | — |
| `role` | `VARCHAR(40)` | nie | nie | — |
| `role_label` | `VARCHAR(120)` | nie | nie | — |
| `since_date` | `VARCHAR(10)` | nie | nie | — |
| `created_at` | `INT(10) UNSIGNED` | nie | nie | — |
| `ended_at` | `INT(10) UNSIGNED` | nie | nie | — |
| `created_by` | `INT(10) UNSIGNED` | nie | nie | — |
| `ended_by` | `INT(10) UNSIGNED` | nie | nie | — |

Indeksy:

- `PRIMARY` (primary): `id`.
- `server` (key): `tid, ended_at`.
- `person` (key): `person_id, game`.
- `owner_lookup` (key): `tid, role, ended_at, person_id`.

## `mpcservers_crew_audit`

| Kolumna | Typ | NULL | Auto increment | Komentarz |
|---|---|---|---|---|
| `id` | `INT(10) UNSIGNED` | nie | tak | — |
| `tid` | `INT(10) UNSIGNED` | nie | nie | — |
| `actor` | `INT(10) UNSIGNED` | nie | nie | — |
| `created_at` | `INT(10) UNSIGNED` | nie | nie | — |
| `before_data` | `MEDIUMTEXT` | nie | nie | — |
| `after_data` | `MEDIUMTEXT` | nie | nie | — |

Indeksy:

- `PRIMARY` (primary): `id`.
- `server` (key): `tid, created_at`.

## `mpcservers_boost_events`

| Kolumna | Typ | NULL | Auto increment | Komentarz |
|---|---|---|---|---|
| `id` | `INT(10) UNSIGNED` | nie | tak | — |
| `tid` | `INT(10) UNSIGNED` | nie | nie | — |
| `member_id` | `INT(10) UNSIGNED` | nie | nie | — |
| `created` | `INT(10) UNSIGNED` | nie | nie | — |
| `game` | `VARCHAR(40)` | nie | nie | — |
| `mode_data` | `MEDIUMTEXT` | tak | nie | — |

Indeksy:

- `PRIMARY` (primary): `id`.
- `period` (key): `created, tid`.
- `server` (key): `tid, created`.
- `member_day` (key): `member_id, created, tid`.
- `history_lookup` (key): `created, game, tid`.

## `mpcservers_awards`

| Kolumna | Typ | NULL | Auto increment | Komentarz |
|---|---|---|---|---|
| `id` | `INT(10) UNSIGNED` | nie | tak | — |
| `tid` | `INT(10) UNSIGNED` | nie | nie | — |
| `period` | `VARCHAR(10)` | nie | nie | — |
| `starts` | `INT(10) UNSIGNED` | nie | nie | — |
| `ends` | `INT(10) UNSIGNED` | nie | nie | — |
| `game` | `VARCHAR(40)` | nie | nie | — |
| `mode_key` | `VARCHAR(160)` | nie | nie | — |
| `mode_label` | `VARCHAR(120)` | nie | nie | — |
| `points` | `INT(10) UNSIGNED` | nie | nie | — |
| `title` | `VARCHAR(255)` | nie | nie | — |

Indeksy:

- `PRIMARY` (primary): `id`.
- `unique_period` (unique): `tid, period, starts, game, mode_key`.

## `mpcservers_award_periods`

| Kolumna | Typ | NULL | Auto increment | Komentarz |
|---|---|---|---|---|
| `id` | `INT(10) UNSIGNED` | nie | tak | — |
| `period` | `VARCHAR(10)` | nie | nie | — |
| `starts` | `INT(10) UNSIGNED` | nie | nie | — |
| `ends` | `INT(10) UNSIGNED` | nie | nie | — |

Indeksy:

- `PRIMARY` (primary): `id`.
- `unique_period` (unique): `period, starts`.

## `mpcservers_crew_locks`

| Kolumna | Typ | NULL | Auto increment | Komentarz |
|---|---|---|---|---|
| `tid` | `INT(10) UNSIGNED` | nie | nie | — |
| `locked` | `INT(10) UNSIGNED` | nie | nie | — |
| `actor` | `INT(10) UNSIGNED` | nie | nie | — |
| `changed_at` | `INT(10) UNSIGNED` | nie | nie | — |
| `reason` | `VARCHAR(500)` | nie | nie | — |

Indeksy:

- `PRIMARY` (primary): `tid`.

## `mpcservers_topic_views`

| Kolumna | Typ | NULL | Auto increment | Komentarz |
|---|---|---|---|---|
| `tid` | `INT(10) UNSIGNED` | nie | nie | — |
| `view_day` | `DATE` | nie | nie | — |
| `views` | `BIGINT(20) UNSIGNED` | nie | nie | — |

Indeksy:

- `PRIMARY` (primary): `tid, view_day`.

## `mpcservers_crew_ratings`

| Kolumna | Typ | NULL | Auto increment | Komentarz |
|---|---|---|---|---|
| `person_id` | `INT(10) UNSIGNED` | nie | nie | — |
| `member_id` | `INT(10) UNSIGNED` | nie | nie | — |
| `score` | `INT(10) UNSIGNED` | nie | nie | — |
| `updated` | `INT(10) UNSIGNED` | nie | nie | — |

Indeksy:

- `PRIMARY` (primary): `person_id, member_id`.


## Uzupełnienie 6.1.8.5 — 22 listopada 2026

Aktualny `data/schema.json` opisuje 17 tabel. Dodano `mpcservers_tag_backup` (historyczne kopie klasyfikacji) i `mpcservers_job_state` (stan i przerwy zadań). Do `mpcservers_presentations` dodano `mc_versions`. Bieżący kod nie prowadzi synchronizacji prefiksów z natywnymi tagami; kopie zachowano. Szczegółowy przebieg migracji: [changelog](../CHANGELOG.md).

### `mpcservers_tag_backup`

Zachowane kopie tagów z wcześniejszej implementacji. Bieżąca aplikacja ich nie synchronizuje.

| Kolumna | Typ | NULL | Auto increment | Znaczenie |
|---|---|---|---|---|
| `tid` | `INT(10) UNSIGNED` | nie | nie | Identyfikator tematu. |
| `tags_json` | `TEXT` | nie | nie | Historyczny zapis tagów. |
| `created` | `INT(10) UNSIGNED` | nie | nie | Czas wykonania kopii. |

Indeks: `PRIMARY` (`tid`).

### `mpcservers_job_state`

Stan czterech zadań: `archive`, `awards`, `status`, `cleanup`; silnik InnoDB. Nazwa zadania jest kluczem, a blokada wykonania korzysta osobno z doradczej blokady SQL.

| Kolumna | Typ | NULL | Auto increment | Znaczenie |
|---|---|---|---|---|
| `job` | `VARCHAR(24)` | nie | nie | Nazwa zadania. |
| `failures` | `INT(10) UNSIGNED` | nie | nie | Liczba kolejnych błędów. |
| `blocked_until` | `INT(10) UNSIGNED` | nie | nie | Koniec odstępu lub przerwy po błędzie. |
| `last_error` | `VARCHAR(190)` | nie | nie | Klasa i kod wyjątku, bez pełnej treści. |

Indeks: `PRIMARY` (`job`). Instalator tworzy brakującą tabelę idempotentnie. Wpisy nie zawierają tokenów, zapytań SQL ani pełnych adresów źródłowych.
