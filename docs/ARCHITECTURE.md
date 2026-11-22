# Architektura i inwentarz integracji

Inwentarz wynika z kodu `mpcservers` w wersji 6.1.8.5 / 60113, dostępnego w eksporcie z 22 listopada 2026. Metadane IPS są źródłem rejestracji aplikacji; istnienie pliku klasy nie dowodzi, że jest ona aktywnie rejestrowana.

## Bieżący podział odpowiedzialności

`FeatureHooks` udostępnia wyłączniki funkcji, a `BackgroundJobs` oddzielne blokady SQL, odstępy i przerwy po błędach dla archiwizacji, TOP, statusu i sprzątania. `Archive::onView()` jest neutralnym adapterem zgodności: przeglądanie forum nie archiwizuje publikacji. `StatusMonitor` wykonuje monitoring w tle. `PublicationTags` pilnuje polityki ręcznego tagowania; jego metody synchronizacji i `TagQueue` nie zapisują natywnych tagów IPS.

`PrefixBadge`, `ServerModes` oraz `Minecraft/Versions` obsługują kolory, tryby i wybrane wersje gry. Loader dołącza odpowiednie CSS/JS wyłącznie przy aktywnych wyłącznikach; `topic-title.js`, `list-prefixes.js`, `prefix-rotation.js` i `shared-badges.js` obsługują wygląd i aktualizacje AJAX. Nagrody Minecrafta sprawdzają przełącznik gry oraz wyjątki konkretnych publikacji. Szczegóły zachowania i zmian: [changelog](../CHANGELOG.md).

## Moduły

```json
{
  "admin": {
    "servers": {
      "default_controller": "forums",
      "protected": false
    },
    "owners": {
      "default_controller": "settings",
      "protected": false
    },
    "rankings": {
      "default_controller": "settings",
      "protected": false
    },
    "people": {
      "default_controller": "settings",
      "protected": false
    },
    "metin2": {
      "default_controller": "settings",
      "protected": false
    },
    "minecraft": {
      "default_controller": "settings",
      "protected": false
    },
    "lists": {
      "default_controller": "settings",
      "protected": false
    },
    "highlights": {
      "default_controller": "settings",
      "protected": false
    },
    "community": {
      "default_controller": "settings",
      "protected": false
    }
  },
  "front": {
    "directory": {
      "default_controller": "browse",
      "protected": false
    },
    "owners": {
      "default_controller": "panel",
      "protected": false
    },
    "rankings": {
      "default_controller": "browse",
      "protected": false
    },
    "people": {
      "default_controller": "browse",
      "protected": false
    },
    "highlights": {
      "default_controller": "browse",
      "protected": false
    }
  }
}
```

## Rozszerzenia i listenery

### `extensions.json`

```json
{
  "core": {
    "Loader": {
      "Servers": "IPS\\mpcservers\\extensions\\core\\Loader\\Servers"
    },
    "UIItem": {
      "TopicForm": "IPS\\mpcservers\\extensions\\core\\UIItem\\TopicForm"
    },
    "UserMenu": {
      "OwnerPanel": "IPS\\mpcservers\\extensions\\core\\UserMenu\\OwnerPanel"
    },
    "Profile": {
      "Servers": "IPS\\mpcservers\\extensions\\core\\Profile\\Servers"
    },
    "FileStorage": {
      "Graphics": "IPS\\mpcservers\\extensions\\core\\FileStorage\\Graphics"
    }
  }
}
```

### `listeners.json`

```json
{
  "PostTags": {
    "type": "ContentListenerType",
    "classname": "IPS\\mpcservers\\listeners\\PostTags",
    "extends": "IPS\\forums\\Topic\\Post"
  },
  "TopicView": {
    "type": "ContentListenerType",
    "classname": "IPS\\mpcservers\\listeners\\TopicView",
    "extends": "IPS\\forums\\Topic"
  }
}
```

### `widgets.json`

```json
[]
```

### `tasks.json`

```json
{
  "mover": "PT5M",
  "status": "PT1M"
}
```

### `furl.json`

```json
{}
```

## Kontrolery i klasy źródłowe

Poniższa lista ułatwia odnalezienie implementacji. Metody pomocnicze nie są deklarowane jako publiczne API HTTP. Adresy menu znajdują się w [konfiguracji](CONFIGURATION.md).

### `modules/`

- [`modules/admin/community/identities.php`](../applications/mpcservers/modules/admin/community/identities.php)
- [`modules/admin/community/settings.php`](../applications/mpcservers/modules/admin/community/settings.php)
- [`modules/admin/highlights/recommended.php`](../applications/mpcservers/modules/admin/highlights/recommended.php)
- [`modules/admin/highlights/settings.php`](../applications/mpcservers/modules/admin/highlights/settings.php)
- [`modules/admin/lists/log.php`](../applications/mpcservers/modules/admin/lists/log.php)
- [`modules/admin/lists/options.php`](../applications/mpcservers/modules/admin/lists/options.php)
- [`modules/admin/lists/settings.php`](../applications/mpcservers/modules/admin/lists/settings.php)
- [`modules/admin/metin2/advanced.php`](../applications/mpcservers/modules/admin/metin2/advanced.php)
- [`modules/admin/metin2/publications.php`](../applications/mpcservers/modules/admin/metin2/publications.php)
- [`modules/admin/metin2/settings.php`](../applications/mpcservers/modules/admin/metin2/settings.php)
- [`modules/admin/minecraft/publications.php`](../applications/mpcservers/modules/admin/minecraft/publications.php)
- [`modules/admin/minecraft/settings.php`](../applications/mpcservers/modules/admin/minecraft/settings.php)
- [`modules/admin/owners/settings.php`](../applications/mpcservers/modules/admin/owners/settings.php)
- [`modules/admin/people/identities.php`](../applications/mpcservers/modules/admin/people/identities.php)
- [`modules/admin/people/settings.php`](../applications/mpcservers/modules/admin/people/settings.php)
- [`modules/admin/rankings/settings.php`](../applications/mpcservers/modules/admin/rankings/settings.php)
- [`modules/admin/servers/advanced.php`](../applications/mpcservers/modules/admin/servers/advanced.php)
- [`modules/admin/servers/featured.php`](../applications/mpcservers/modules/admin/servers/featured.php)
- [`modules/admin/servers/forums.php`](../applications/mpcservers/modules/admin/servers/forums.php)
- [`modules/admin/servers/log.php`](../applications/mpcservers/modules/admin/servers/log.php)
- [`modules/admin/servers/people.php`](../applications/mpcservers/modules/admin/servers/people.php)
- [`modules/admin/servers/presentation.php`](../applications/mpcservers/modules/admin/servers/presentation.php)
- [`modules/admin/servers/settings.php`](../applications/mpcservers/modules/admin/servers/settings.php)
- [`modules/front/directory/browse.php`](../applications/mpcservers/modules/front/directory/browse.php)
- [`modules/front/directory/owners.php`](../applications/mpcservers/modules/front/directory/owners.php)
- [`modules/front/directory/people.php`](../applications/mpcservers/modules/front/directory/people.php)
- [`modules/front/directory/proxy.php`](../applications/mpcservers/modules/front/directory/proxy.php)
- [`modules/front/directory/proxyajax.php`](../applications/mpcservers/modules/front/directory/proxyajax.php)
- [`modules/front/directory/rankings.php`](../applications/mpcservers/modules/front/directory/rankings.php)
- [`modules/front/directory/rewards.php`](../applications/mpcservers/modules/front/directory/rewards.php)
- [`modules/front/highlights/browse.php`](../applications/mpcservers/modules/front/highlights/browse.php)
- [`modules/front/owners/panel.php`](../applications/mpcservers/modules/front/owners/panel.php)
- [`modules/front/owners/rewards.php`](../applications/mpcservers/modules/front/owners/rewards.php)
- [`modules/front/people/browse.php`](../applications/mpcservers/modules/front/people/browse.php)
- [`modules/front/rankings/browse.php`](../applications/mpcservers/modules/front/rankings/browse.php)

### `sources/`

- [`sources/Archive/Archive.php`](../applications/mpcservers/sources/Archive/Archive.php)
- [`sources/Awards/Awards.php`](../applications/mpcservers/sources/Awards/Awards.php)
- [`sources/Boost/Boost.php`](../applications/mpcservers/sources/Boost/Boost.php)
- [`sources/Crew/Crew.php`](../applications/mpcservers/sources/Crew/Crew.php)
- [`sources/CrewDirectory/CrewDirectory.php`](../applications/mpcservers/sources/CrewDirectory/CrewDirectory.php)
- [`sources/CrewForm/CrewForm.php`](../applications/mpcservers/sources/CrewForm/CrewForm.php)
- [`sources/CrewIdentity/CrewIdentity.php`](../applications/mpcservers/sources/CrewIdentity/CrewIdentity.php)
- [`sources/CrewLock/CrewLock.php`](../applications/mpcservers/sources/CrewLock/CrewLock.php)
- [`sources/CrewProfile/CrewProfile.php`](../applications/mpcservers/sources/CrewProfile/CrewProfile.php)
- [`sources/CrewRating/CrewRating.php`](../applications/mpcservers/sources/CrewRating/CrewRating.php)
- [`sources/Directory/Directory.php`](../applications/mpcservers/sources/Directory/Directory.php)
- [`sources/GameNames/GameNames.php`](../applications/mpcservers/sources/GameNames/GameNames.php)
- [`sources/GameRewards/GameRewards.php`](../applications/mpcservers/sources/GameRewards/GameRewards.php)
- [`sources/Highlights/Highlights.php`](../applications/mpcservers/sources/Highlights/Highlights.php)
- [`sources/Listing/Listing.php`](../applications/mpcservers/sources/Listing/Listing.php)
- [`sources/Media/Media.php`](../applications/mpcservers/sources/Media/Media.php)
- [`sources/ModeFilters/ModeFilters.php`](../applications/mpcservers/sources/ModeFilters/ModeFilters.php)
- [`sources/ModuleAccess/ModuleAccess.php`](../applications/mpcservers/sources/ModuleAccess/ModuleAccess.php)
- [`sources/ModuleAdmin/ModuleAdmin.php`](../applications/mpcservers/sources/ModuleAdmin/ModuleAdmin.php)
- [`sources/Modules/Community/Crew.php`](../applications/mpcservers/sources/Modules/Community/Crew.php)
- [`sources/Modules/Community/CrewDirectory.php`](../applications/mpcservers/sources/Modules/Community/CrewDirectory.php)
- [`sources/Modules/Community/CrewForm.php`](../applications/mpcservers/sources/Modules/Community/CrewForm.php)
- [`sources/Modules/Community/CrewIdentity.php`](../applications/mpcservers/sources/Modules/Community/CrewIdentity.php)
- [`sources/Modules/Community/CrewLock.php`](../applications/mpcservers/sources/Modules/Community/CrewLock.php)
- [`sources/Modules/Community/CrewProfile.php`](../applications/mpcservers/sources/Modules/Community/CrewProfile.php)
- [`sources/Modules/Community/CrewRating.php`](../applications/mpcservers/sources/Modules/Community/CrewRating.php)
- [`sources/Modules/Community/Owner.php`](../applications/mpcservers/sources/Modules/Community/Owner.php)
- [`sources/Modules/Community/OwnerAccess.php`](../applications/mpcservers/sources/Modules/Community/OwnerAccess.php)
- [`sources/Modules/Community/OwnerStats.php`](../applications/mpcservers/sources/Modules/Community/OwnerStats.php)
- [`sources/Modules/Community/TopicViews.php`](../applications/mpcservers/sources/Modules/Community/TopicViews.php)
- [`sources/Modules/Core/Archive.php`](../applications/mpcservers/sources/Modules/Core/Archive.php)
- [`sources/Modules/Core/BackgroundJobs.php`](../applications/mpcservers/sources/Modules/Core/BackgroundJobs.php)
- [`sources/Modules/Core/FeatureHooks.php`](../applications/mpcservers/sources/Modules/Core/FeatureHooks.php)
- [`sources/Modules/Core/GameNames.php`](../applications/mpcservers/sources/Modules/Core/GameNames.php)
- [`sources/Modules/Core/Listing.php`](../applications/mpcservers/sources/Modules/Core/Listing.php)
- [`sources/Modules/Core/Media.php`](../applications/mpcservers/sources/Modules/Core/Media.php)
- [`sources/Modules/Core/ModuleAccess.php`](../applications/mpcservers/sources/Modules/Core/ModuleAccess.php)
- [`sources/Modules/Core/ModuleAdmin.php`](../applications/mpcservers/sources/Modules/Core/ModuleAdmin.php)
- [`sources/Modules/Core/ModuleSettings.php`](../applications/mpcservers/sources/Modules/Core/ModuleSettings.php)
- [`sources/Modules/Core/OrdinaryTopic.php`](../applications/mpcservers/sources/Modules/Core/OrdinaryTopic.php)
- [`sources/Modules/Core/PrefixBadge.php`](../applications/mpcservers/sources/Modules/Core/PrefixBadge.php)
- [`sources/Modules/Core/Presentation.php`](../applications/mpcservers/sources/Modules/Core/Presentation.php)
- [`sources/Modules/Core/PresentationView.php`](../applications/mpcservers/sources/Modules/Core/PresentationView.php)
- [`sources/Modules/Core/PublicationTags.php`](../applications/mpcservers/sources/Modules/Core/PublicationTags.php)
- [`sources/Modules/Core/Registry.php`](../applications/mpcservers/sources/Modules/Core/Registry.php)
- [`sources/Modules/Core/ServerModes.php`](../applications/mpcservers/sources/Modules/Core/ServerModes.php)
- [`sources/Modules/Core/Servers.php`](../applications/mpcservers/sources/Modules/Core/Servers.php)
- [`sources/Modules/Core/StatusMonitor.php`](../applications/mpcservers/sources/Modules/Core/StatusMonitor.php)
- [`sources/Modules/Core/SystemMigration.php`](../applications/mpcservers/sources/Modules/Core/SystemMigration.php)
- [`sources/Modules/Core/TagLinks.php`](../applications/mpcservers/sources/Modules/Core/TagLinks.php)
- [`sources/Modules/Core/TagQueue.php`](../applications/mpcservers/sources/Modules/Core/TagQueue.php)
- [`sources/Modules/Core/TemplateHooks.php`](../applications/mpcservers/sources/Modules/Core/TemplateHooks.php)
- [`sources/Modules/Core/TopicForm.php`](../applications/mpcservers/sources/Modules/Core/TopicForm.php)
- [`sources/Modules/Directory/Directory.php`](../applications/mpcservers/sources/Modules/Directory/Directory.php)
- [`sources/Modules/Directory/ModeFilters.php`](../applications/mpcservers/sources/Modules/Directory/ModeFilters.php)
- [`sources/Modules/Directory/OrdinaryTopic.php`](../applications/mpcservers/sources/Modules/Directory/OrdinaryTopic.php)
- [`sources/Modules/Directory/Presentation.php`](../applications/mpcservers/sources/Modules/Directory/Presentation.php)
- [`sources/Modules/Directory/PresentationProxy.php`](../applications/mpcservers/sources/Modules/Directory/PresentationProxy.php)
- [`sources/Modules/Directory/PresentationView.php`](../applications/mpcservers/sources/Modules/Directory/PresentationView.php)
- [`sources/Modules/Directory/ProxyAjax.php`](../applications/mpcservers/sources/Modules/Directory/ProxyAjax.php)
- [`sources/Modules/Directory/ProxyAssets.php`](../applications/mpcservers/sources/Modules/Directory/ProxyAssets.php)
- [`sources/Modules/Directory/ProxySecret.php`](../applications/mpcservers/sources/Modules/Directory/ProxySecret.php)
- [`sources/Modules/Directory/Recommended.php`](../applications/mpcservers/sources/Modules/Directory/Recommended.php)
- [`sources/Modules/Directory/Status.php`](../applications/mpcservers/sources/Modules/Directory/Status.php)
- [`sources/Modules/Directory/TopicForm.php`](../applications/mpcservers/sources/Modules/Directory/TopicForm.php)
- [`sources/Modules/Directory/TopicPreview.php`](../applications/mpcservers/sources/Modules/Directory/TopicPreview.php)
- [`sources/Modules/Directory/TopicSeo.php`](../applications/mpcservers/sources/Modules/Directory/TopicSeo.php)
- [`sources/Modules/Highlights/Awards.php`](../applications/mpcservers/sources/Modules/Highlights/Awards.php)
- [`sources/Modules/Highlights/Highlights.php`](../applications/mpcservers/sources/Modules/Highlights/Highlights.php)
- [`sources/Modules/Highlights/Rankings.php`](../applications/mpcservers/sources/Modules/Highlights/Rankings.php)
- [`sources/Modules/Highlights/Recommended.php`](../applications/mpcservers/sources/Modules/Highlights/Recommended.php)
- [`sources/Modules/Lists/Boost.php`](../applications/mpcservers/sources/Modules/Lists/Boost.php)
- [`sources/Modules/Lists/Directory.php`](../applications/mpcservers/sources/Modules/Lists/Directory.php)
- [`sources/Modules/Lists/ModeFilters.php`](../applications/mpcservers/sources/Modules/Lists/ModeFilters.php)
- [`sources/Modules/Lists/Status.php`](../applications/mpcservers/sources/Modules/Lists/Status.php)
- [`sources/Modules/Lists/TopicPreview.php`](../applications/mpcservers/sources/Modules/Lists/TopicPreview.php)
- [`sources/Modules/Lists/TopicSeo.php`](../applications/mpcservers/sources/Modules/Lists/TopicSeo.php)
- [`sources/Modules/Metin2/Policy.php`](../applications/mpcservers/sources/Modules/Metin2/Policy.php)
- [`sources/Modules/Metin2/PresentationProxy.php`](../applications/mpcservers/sources/Modules/Metin2/PresentationProxy.php)
- [`sources/Modules/Metin2/ProxyAjax.php`](../applications/mpcservers/sources/Modules/Metin2/ProxyAjax.php)
- [`sources/Modules/Metin2/ProxyAssets.php`](../applications/mpcservers/sources/Modules/Metin2/ProxyAssets.php)
- [`sources/Modules/Metin2/ProxySecret.php`](../applications/mpcservers/sources/Modules/Metin2/ProxySecret.php)
- [`sources/Modules/Minecraft/GameRewards.php`](../applications/mpcservers/sources/Modules/Minecraft/GameRewards.php)
- [`sources/Modules/Minecraft/Policy.php`](../applications/mpcservers/sources/Modules/Minecraft/Policy.php)
- [`sources/Modules/Minecraft/Versions.php`](../applications/mpcservers/sources/Modules/Minecraft/Versions.php)
- [`sources/Modules/Owners/GameRewards.php`](../applications/mpcservers/sources/Modules/Owners/GameRewards.php)
- [`sources/Modules/Owners/Owner.php`](../applications/mpcservers/sources/Modules/Owners/Owner.php)
- [`sources/Modules/Owners/OwnerAccess.php`](../applications/mpcservers/sources/Modules/Owners/OwnerAccess.php)
- [`sources/Modules/Owners/OwnerStats.php`](../applications/mpcservers/sources/Modules/Owners/OwnerStats.php)
- [`sources/Modules/Owners/TopicViews.php`](../applications/mpcservers/sources/Modules/Owners/TopicViews.php)
- [`sources/Modules/People/Crew.php`](../applications/mpcservers/sources/Modules/People/Crew.php)
- [`sources/Modules/People/CrewDirectory.php`](../applications/mpcservers/sources/Modules/People/CrewDirectory.php)
- [`sources/Modules/People/CrewForm.php`](../applications/mpcservers/sources/Modules/People/CrewForm.php)
- [`sources/Modules/People/CrewIdentity.php`](../applications/mpcservers/sources/Modules/People/CrewIdentity.php)
- [`sources/Modules/People/CrewLock.php`](../applications/mpcservers/sources/Modules/People/CrewLock.php)
- [`sources/Modules/People/CrewProfile.php`](../applications/mpcservers/sources/Modules/People/CrewProfile.php)
- [`sources/Modules/People/CrewRating.php`](../applications/mpcservers/sources/Modules/People/CrewRating.php)
- [`sources/Modules/Rankings/Awards.php`](../applications/mpcservers/sources/Modules/Rankings/Awards.php)
- [`sources/Modules/Rankings/Boost.php`](../applications/mpcservers/sources/Modules/Rankings/Boost.php)
- [`sources/Modules/Rankings/Highlights.php`](../applications/mpcservers/sources/Modules/Rankings/Highlights.php)
- [`sources/Modules/Rankings/Rankings.php`](../applications/mpcservers/sources/Modules/Rankings/Rankings.php)
- [`sources/OrdinaryTopic/OrdinaryTopic.php`](../applications/mpcservers/sources/OrdinaryTopic/OrdinaryTopic.php)
- [`sources/Owner/Owner.php`](../applications/mpcservers/sources/Owner/Owner.php)
- [`sources/OwnerAccess/OwnerAccess.php`](../applications/mpcservers/sources/OwnerAccess/OwnerAccess.php)
- [`sources/OwnerStats/OwnerStats.php`](../applications/mpcservers/sources/OwnerStats/OwnerStats.php)
- [`sources/Presentation/Presentation.php`](../applications/mpcservers/sources/Presentation/Presentation.php)
- [`sources/PresentationView/PresentationView.php`](../applications/mpcservers/sources/PresentationView/PresentationView.php)
- [`sources/Rankings/Rankings.php`](../applications/mpcservers/sources/Rankings/Rankings.php)
- [`sources/Recommended/Recommended.php`](../applications/mpcservers/sources/Recommended/Recommended.php)
- [`sources/Servers/Servers.php`](../applications/mpcservers/sources/Servers/Servers.php)
- [`sources/Status/Status.php`](../applications/mpcservers/sources/Status/Status.php)
- [`sources/SystemMigration/SystemMigration.php`](../applications/mpcservers/sources/SystemMigration/SystemMigration.php)
- [`sources/TopicForm/TopicForm.php`](../applications/mpcservers/sources/TopicForm/TopicForm.php)
- [`sources/TopicViews/TopicViews.php`](../applications/mpcservers/sources/TopicViews/TopicViews.php)

### `tasks/`

- [`tasks/mover.php`](../applications/mpcservers/tasks/mover.php)
- [`tasks/status.php`](../applications/mpcservers/tasks/status.php)

### `listeners/`

- [`listeners/PostTags.php`](../applications/mpcservers/listeners/PostTags.php)
- [`listeners/TopicView.php`](../applications/mpcservers/listeners/TopicView.php)

### `extensions/`

- [`extensions/core/FileStorage/Graphics.php`](../applications/mpcservers/extensions/core/FileStorage/Graphics.php)
- [`extensions/core/Loader/Servers.php`](../applications/mpcservers/extensions/core/Loader/Servers.php)
- [`extensions/core/Profile/Servers.php`](../applications/mpcservers/extensions/core/Profile/Servers.php)
- [`extensions/core/UIItem/TopicForm.php`](../applications/mpcservers/extensions/core/UIItem/TopicForm.php)
- [`extensions/core/UserMenu/OwnerPanel.php`](../applications/mpcservers/extensions/core/UserMenu/OwnerPanel.php)

## Wersje i aktualizacje

| Numer wewnętrzny | Wersja |
|---|---|
| `20000` | 2.0.0 |
| `30000` | 3.0.0 Beta 1 |
| `30001` | 3.0.0 Beta 2 |
| `30002` | 3.0.0 Beta 3 |
| `30003` | 3.0.0 Beta 4 |
| `30004` | 3.0.0 Beta 5 |
| `30005` | 3.0.0 Beta 6 |
| `30006` | 3.0.0 Beta 7 |
| `30007` | 3.0.0 Beta 8 |
| `30008` | 3.0.0 Beta 9 |
| `30009` | 3.0.0 Beta 10 |
| `30010` | 3.0.0 Beta 11 |
| `30011` | 3.0.0 Beta 12 |
| `30012` | 3.0.0 Beta 13 |
| `30013` | 3.0.0 Beta 14 |
| `30014` | 3.0.0 Beta 15 |
| `40000` | 4.0.0 |
| `40001` | 4.0.1 |
| `40002` | 4.0.2 |
| `40003` | 4.0.3 |
| `40004` | 4.0.4 |
| `40100` | 4.1.0 |
| `40101` | 4.1.1 |
| `40200` | 4.2.0 |
| `40201` | 4.2.1 |
| `40202` | 4.2.2 |
| `40203` | 4.2.3 |
| `40204` | 4.2.4 |
| `40205` | 4.2.5 |
| `50000` | 1.0.0 |
| `60000` | 6.0.0 |
| `60001` | 6.0.1 |
| `60002` | 6.0.2 |
| `60003` | 6.0.3 |
| `60004` | 6.0.4 |
| `60005` | 6.0.5 |
| `60006` | 6.0.6 |
| `60007` | 6.0.7 |
| `60008` | 6.0.8 |
| `60009` | 6.0.9 |
| `60010` | 6.0.10 |
| `60100` | 6.1.0 |
| `60101` | 6.1.1 |
| `60102` | 6.1.2 |
| `60103` | 6.1.3 |
| `60104` | 6.1.4 |
| `60105` | 6.1.5 |
| `60107` | 6.1.7 |
| `60108` | 6.1.8 |
| `60109` | 6.1.8.1 |
| `60110` | 6.1.8.2 |
| `60111` | 6.1.8.3 |
| `60112` | 6.1.8.4 |
| `60113` | 6.1.8.5 |

Pusty katalog `upg_60106` nie oznacza wydanej wersji 6.1.6: tego wpisu nie ma w manifeście. Aktualny przebieg migracji, w tym neutralny adapter dawnej synchronizacji tagów, opisuje [changelog](../CHANGELOG.md#schemat-ustawienia-i-migracje).

Pliki aktualizacji:

- [`setup/upg_20000/queries.json`](../applications/mpcservers/setup/upg_20000/queries.json)
- [`setup/upg_30000/queries.json`](../applications/mpcservers/setup/upg_30000/queries.json)
- [`setup/upg_30000/upgrade.php`](../applications/mpcservers/setup/upg_30000/upgrade.php)
- [`setup/upg_30001/queries.json`](../applications/mpcservers/setup/upg_30001/queries.json)
- [`setup/upg_30002/queries.json`](../applications/mpcservers/setup/upg_30002/queries.json)
- [`setup/upg_30002/upgrade.php`](../applications/mpcservers/setup/upg_30002/upgrade.php)
- [`setup/upg_30003/queries.json`](../applications/mpcservers/setup/upg_30003/queries.json)
- [`setup/upg_30004/queries.json`](../applications/mpcservers/setup/upg_30004/queries.json)
- [`setup/upg_30004/upgrade.php`](../applications/mpcservers/setup/upg_30004/upgrade.php)
- [`setup/upg_30005/queries.json`](../applications/mpcservers/setup/upg_30005/queries.json)
- [`setup/upg_30006/queries.json`](../applications/mpcservers/setup/upg_30006/queries.json)
- [`setup/upg_30007/queries.json`](../applications/mpcservers/setup/upg_30007/queries.json)
- [`setup/upg_30007/upgrade.php`](../applications/mpcservers/setup/upg_30007/upgrade.php)
- [`setup/upg_30008/queries.json`](../applications/mpcservers/setup/upg_30008/queries.json)
- [`setup/upg_30008/upgrade.php`](../applications/mpcservers/setup/upg_30008/upgrade.php)
- [`setup/upg_30009/queries.json`](../applications/mpcservers/setup/upg_30009/queries.json)
- [`setup/upg_30009/upgrade.php`](../applications/mpcservers/setup/upg_30009/upgrade.php)
- [`setup/upg_30010/queries.json`](../applications/mpcservers/setup/upg_30010/queries.json)
- [`setup/upg_30010/upgrade.php`](../applications/mpcservers/setup/upg_30010/upgrade.php)
- [`setup/upg_30011/queries.json`](../applications/mpcservers/setup/upg_30011/queries.json)
- [`setup/upg_30011/upgrade.php`](../applications/mpcservers/setup/upg_30011/upgrade.php)
- [`setup/upg_30012/queries.json`](../applications/mpcservers/setup/upg_30012/queries.json)
- [`setup/upg_30012/upgrade.php`](../applications/mpcservers/setup/upg_30012/upgrade.php)
- [`setup/upg_30013/queries.json`](../applications/mpcservers/setup/upg_30013/queries.json)
- [`setup/upg_30013/upgrade.php`](../applications/mpcservers/setup/upg_30013/upgrade.php)
- [`setup/upg_30014/queries.json`](../applications/mpcservers/setup/upg_30014/queries.json)
- [`setup/upg_30014/upgrade.php`](../applications/mpcservers/setup/upg_30014/upgrade.php)
- [`setup/upg_40000/queries.json`](../applications/mpcservers/setup/upg_40000/queries.json)
- [`setup/upg_40000/upgrade.php`](../applications/mpcservers/setup/upg_40000/upgrade.php)
- [`setup/upg_40001/queries.json`](../applications/mpcservers/setup/upg_40001/queries.json)
- [`setup/upg_40001/upgrade.php`](../applications/mpcservers/setup/upg_40001/upgrade.php)
- [`setup/upg_40002/queries.json`](../applications/mpcservers/setup/upg_40002/queries.json)
- [`setup/upg_40002/upgrade.php`](../applications/mpcservers/setup/upg_40002/upgrade.php)
- [`setup/upg_40003/queries.json`](../applications/mpcservers/setup/upg_40003/queries.json)
- [`setup/upg_40003/upgrade.php`](../applications/mpcservers/setup/upg_40003/upgrade.php)
- [`setup/upg_40004/queries.json`](../applications/mpcservers/setup/upg_40004/queries.json)
- [`setup/upg_40004/upgrade.php`](../applications/mpcservers/setup/upg_40004/upgrade.php)
- [`setup/upg_40100/queries.json`](../applications/mpcservers/setup/upg_40100/queries.json)
- [`setup/upg_40100/upgrade.php`](../applications/mpcservers/setup/upg_40100/upgrade.php)
- [`setup/upg_40101/queries.json`](../applications/mpcservers/setup/upg_40101/queries.json)
- [`setup/upg_40101/upgrade.php`](../applications/mpcservers/setup/upg_40101/upgrade.php)
- [`setup/upg_40200/queries.json`](../applications/mpcservers/setup/upg_40200/queries.json)
- [`setup/upg_40200/upgrade.php`](../applications/mpcservers/setup/upg_40200/upgrade.php)
- [`setup/upg_40201/queries.json`](../applications/mpcservers/setup/upg_40201/queries.json)
- [`setup/upg_40201/upgrade.php`](../applications/mpcservers/setup/upg_40201/upgrade.php)
- [`setup/upg_40202/queries.json`](../applications/mpcservers/setup/upg_40202/queries.json)
- [`setup/upg_40202/upgrade.php`](../applications/mpcservers/setup/upg_40202/upgrade.php)
- [`setup/upg_40203/queries.json`](../applications/mpcservers/setup/upg_40203/queries.json)
- [`setup/upg_40203/upgrade.php`](../applications/mpcservers/setup/upg_40203/upgrade.php)
- [`setup/upg_40204/queries.json`](../applications/mpcservers/setup/upg_40204/queries.json)
- [`setup/upg_40204/upgrade.php`](../applications/mpcservers/setup/upg_40204/upgrade.php)
- [`setup/upg_40205/queries.json`](../applications/mpcservers/setup/upg_40205/queries.json)
- [`setup/upg_40205/upgrade.php`](../applications/mpcservers/setup/upg_40205/upgrade.php)
- [`setup/upg_50000/upgrade.php`](../applications/mpcservers/setup/upg_50000/upgrade.php)
- [`setup/upg_60000/queries.json`](../applications/mpcservers/setup/upg_60000/queries.json)
- [`setup/upg_60000/upgrade.php`](../applications/mpcservers/setup/upg_60000/upgrade.php)
- [`setup/upg_60001/queries.json`](../applications/mpcservers/setup/upg_60001/queries.json)
- [`setup/upg_60002/queries.json`](../applications/mpcservers/setup/upg_60002/queries.json)
- [`setup/upg_60003/queries.json`](../applications/mpcservers/setup/upg_60003/queries.json)
- [`setup/upg_60004/queries.json`](../applications/mpcservers/setup/upg_60004/queries.json)
- [`setup/upg_60004/upgrade.php`](../applications/mpcservers/setup/upg_60004/upgrade.php)
- [`setup/upg_60005/queries.json`](../applications/mpcservers/setup/upg_60005/queries.json)
- [`setup/upg_60006/queries.json`](../applications/mpcservers/setup/upg_60006/queries.json)
- [`setup/upg_60007/queries.json`](../applications/mpcservers/setup/upg_60007/queries.json)
- [`setup/upg_60008/queries.json`](../applications/mpcservers/setup/upg_60008/queries.json)
- [`setup/upg_60008/upgrade.php`](../applications/mpcservers/setup/upg_60008/upgrade.php)
- [`setup/upg_60009/queries.json`](../applications/mpcservers/setup/upg_60009/queries.json)
- [`setup/upg_60010/queries.json`](../applications/mpcservers/setup/upg_60010/queries.json)
- [`setup/upg_60100/queries.json`](../applications/mpcservers/setup/upg_60100/queries.json)
- [`setup/upg_60101/queries.json`](../applications/mpcservers/setup/upg_60101/queries.json)
- [`setup/upg_60102/queries.json`](../applications/mpcservers/setup/upg_60102/queries.json)
- [`setup/upg_60103/queries.json`](../applications/mpcservers/setup/upg_60103/queries.json)
- [`setup/upg_60104/queries.json`](../applications/mpcservers/setup/upg_60104/queries.json)
- [`setup/upg_60105/queries.json`](../applications/mpcservers/setup/upg_60105/queries.json)
- [`setup/upg_60106/queries.json`](../applications/mpcservers/setup/upg_60106/queries.json)
- [`setup/upg_60107/queries.json`](../applications/mpcservers/setup/upg_60107/queries.json)
- [`setup/upg_60108/queries.json`](../applications/mpcservers/setup/upg_60108/queries.json)
- [`setup/upg_60108/upgrade.php`](../applications/mpcservers/setup/upg_60108/upgrade.php)
- [`setup/upg_60109/queries.json`](../applications/mpcservers/setup/upg_60109/queries.json)
- [`setup/upg_60109/upgrade.php`](../applications/mpcservers/setup/upg_60109/upgrade.php)
- [`setup/upg_60110/queries.json`](../applications/mpcservers/setup/upg_60110/queries.json)
- [`setup/upg_60110/upgrade.php`](../applications/mpcservers/setup/upg_60110/upgrade.php)
- [`setup/upg_60111/queries.json`](../applications/mpcservers/setup/upg_60111/queries.json)
- [`setup/upg_60111/upgrade.php`](../applications/mpcservers/setup/upg_60111/upgrade.php)
- [`setup/upg_60112/queries.json`](../applications/mpcservers/setup/upg_60112/queries.json)
- [`setup/upg_60112/upgrade.php`](../applications/mpcservers/setup/upg_60112/upgrade.php)
- [`setup/upg_60113/queries.json`](../applications/mpcservers/setup/upg_60113/queries.json)
- [`setup/upg_60113/upgrade.php`](../applications/mpcservers/setup/upg_60113/upgrade.php)

## Uruchamianie

Klasy wymagają inicjalizacji IPS, jego autoloadera, konfiguracji i bazy. Nie uruchamiaj bezpośrednio kontrolerów PHP z powłoki. Wyjątkiem są pliki `interface/` jawnie opisane w README jako punkty CLI. `php -l` jest kontrolą składni, a nie uruchomieniem aplikacji.
