# Übernahme in die beiden Eigentümer

Stand 21.10.2026. Die Spaltensätze sind aus den Migrationen der Produkte
abgelesen, nicht erfunden — `tests/Feature/UebernahmeTest.php` baut genau diese
beiden Schemata nach und beweist, dass eine Klasse für beide reicht.

Projekte sind **zwei Populationen**: Der Manager führt Geschäftsprojekte (Kunde,
Budget, Rechnung), AI Brain führt Wissenskontexte (Codebase, Paket, Werkzeug).
Von 58 Brain-Projekten sind die meisten keine Kundenprojekte. Es wandern keine
Daten; beide behalten ihre Tabelle.

## Peppermint Manager

`projects` seit 18.08.2025, 19 Spalten.

```php
// config/project.php
'tables'  => ['projects' => 'projects'],
'columns' => ['projects' => [
    'owner_id' => 'project_manager_id',
    'kind'     => 'category',          // falls die Art über die Kategorie läuft
]],
'statuses'       => ['active', 'on_hold', 'completed', 'cancelled'],
'default_status' => 'active',
'run_migrations' => false,
```

`status` ist dort ein **DB-Enum** mit genau diesen vier Werten — ein fünfter
Wert aus dem Paket würde beim Schreiben abgewiesen.

**Stolperstelle:** `project_manager_id` ist NOT NULL mit Fremdschlüssel auf
`users` und hat keine Vorgabe. Siehe „Die NOT-NULL-Falle" unten.

## AI Brain

`projects` seit 03.02.2026, 7 Spalten. **Produktion** — eine gespeicherte
PHP-Datei dort ist der Deploy.

```php
// config/project.php
'tables'  => ['projects' => 'projects'],
'columns' => ['projects' => []],       // heißt alles wie im Paket
'statuses'       => ['active', 'archived'],
'default_status' => 'active',
'run_migrations' => false,
```

**Stolperstellen:**

- `knowledge_path` ist NOT NULL ohne Vorgabe.
- `slug` ist unique NOT NULL und **tragend**: Der Vault-Ordner heißt wie der
  Slug, und `file_path` ist relativ dazu. Ein Slug, der ohne seinen Ordner
  wandert, lässt jeden Pfad des Projekts ins Leere zeigen — am 31.07.2026 traf
  das 122 Memories auf einen Schlag (Bug #693). `Project::booted()` zieht den
  Ordner deshalb mit. Diese Mechanik darf die Übernahme nicht verlieren.
- Das Brain führt **keine** Projektarten. Eine leere Registratur ist ein
  gültiger Zustand, kein Fehlerfall.

## Die NOT-NULL-Falle

Die Regel aus Bug #1060 — „Schlüssel weglassen, dann greift die Vorgabe der
Spalte" — gilt **nur für eine Spalte mit Vorgabe.** Beim Kalender war
`event_categories.color` NOT NULL *mit* Vorgabe; Weglassen heilte es.

Die beiden Spalten hier haben **keine** Vorgabe. Dort bricht auch das
Weglassen. Drei Proben in `tests/Feature/UebernahmeTest.php` halten den
Unterschied fest, einschließlich des Gegenfalls mit Vorgabe.

Also eine von zwei Behebungen, nicht „Schlüssel weglassen":

| Weg | Eingriff |
|---|---|
| Spalten eine DB-Vorgabe geben oder nullbar machen | Migration auf zwei Produktionstabellen |
| **Das Paket legt nie selbst an**, sondern über einen produktgestellten Haken | keine Produktionstabelle berührt — *empfohlen* |

Für `project_manager_id` gibt es ohnehin keine sinnvolle Vorgabe: Der
Fremdschlüssel zeigt auf `users`.

## Was die Übernahme NICHT anfasst

- **Abrechnung.** `App\Traits\HasBillableTarget` beantwortet die Kundenfrage
  bereits an einer Stelle (steht `project_id`, erbt das Ziel vom Projekt; sonst
  gewinnt das eigene `organization_id`/`customer_id`), und
  `ValidatesBillableTarget` erzwingt, dass beides nie zugleich gesetzt ist. Die
  `customer_id`-Spalten auf `tasks`, `calendar_events` und `time_entries` sind
  keine Dubletten — sie sind der „sonst"-Zweig, und Aufgaben ohne Projekt
  brauchen ihn.
- `App\Services\Stundensatz` im Manager.
- Oberfläche, Autorisierung, Stammdaten.
