# peppermint/project

Eine Projekt-Identität, an die andere Systeme ihre eigene Bedeutung hängen.

Stand: Gerüst. Noch in kein Produkt eingebunden. Der Schnitt ist vorgeschlagen,
nicht entschieden — siehe „Offen" unten.

## Was hier hineingehört

- Die Identität: `name`, `description`, `status`, `kind`
- **Projektarten** (`src/Kinds/ProjectKind.php`) — eine Art entscheidet, welche
  Facetten ein Projekt hat. Das ist der Kern des Pakets: Die Regel „ein
  digitales Projekt bekommt einen Wissens-Zwilling, ein Druckauftrag nicht"
  existierte am 21.10.2026 in keinem der vier Produkte als Code.
- **Spaltenabbildung** (`Project::column()` / `::field()`) — beide Übernehmer
  haben ihre Tabelle länger als dieses Paket und nennen ihre Spalten anders.
- **Status als Vokabular des Produkts** — kein Paket-Enum. Gemessen: Manager
  vier Werte als DB-Enum, AI Brain zwei als freier String.

## Was hier ausdrücklich NICHT hineingehört

| Nicht hier | Wo stattdessen | Warum |
|---|---|---|
| Budget, Stundensätze, Kostenverfolgung | Manager | `ProjectController` allein hat ~20 `hourly_rate`-Stellen; `App\Services\Stundensatz` ist dort die eine Antwort auf „was kostet diese Person in diesem Projekt" |
| Rechnungsstellung | Verwaltung | CRM und AI Brain rechnen keine Projekte ab |
| `knowledge_path`, `repo_path`, Vault, Secrets | AI Brain | Ein Feld, das nur ein Produkt braucht, gehört nicht in die geteilte Mitte |
| Equipment, Verleih, Dokumente, Bilder | Manager | dito |
| Oberfläche | je Produkt | Vue im Manager, React im Brain |
| Autorisierung | je Produkt | Das Paket fragt nur einen konfigurierbaren Namen ab (`project.permissions`) |

## Wie andere Pakete andocken

**Nicht über den `subject`-Platz.** Der ist einer, und er ist belegt: Im Manager
ist eine Aufgabe das Subjekt ihres Zeitblocks und ihrer Zeiteinträge. Würde das
Projekt denselben Platz beanspruchen, könnte eine eingeplante Aufgabe nicht zu
einem Projekt gehören.

`subject` sagt, **worum** es geht. Das Projekt sagt, **wozu** etwas gehört. Zwei
Achsen, zwei Spalten. Der Projektbezug ist deshalb eine eigene, nullbare Spalte
in der Tabelle des Produkts — `tasks.project_id` gibt es im Manager schon.

Dieses Paket verlangt **kein** anderes Peppermint-Paket per `require`. Brücken
zu `tasks`, `calendar` und `time` stehen als `suggest` und werden von der
Anwendung registriert — so bleibt die Gegenprobe aus dem Grundsatz „zwei
Core-Pakete dürfen sich nicht kennen" in beide Richtungen grün.

## Offen — vor der ersten Einbindung zu entscheiden

1. Besitzt der Manager die Projekt-Identität? (Beide Querverweise hängen heute
   dort: `ai_brain_project_id`, `verwaltung_order_id`.)
2. Ist die Art eine eigene Spalte oder eine vorhandene Kategorie-Spalte?
   **Beides geht ohne Codeänderung** — `project.columns.projects.kind` bildet ab.
3. Wird `slug` Kernspalte? AI Brain hat ihn unique NOT NULL und tragend (der
   Vault-Ordner heißt so), der Manager hat **keinen**. Kernspalte hieße Backfill.
4. Fließt `name` automatisch Manager → Brain? Der **Status nicht** — zwei Status
   mit zwei Bedeutungen sind keine Dublette, sondern zwei Fragen.

## Vor der ersten Übernahme zu beheben

Zwei NOT-NULL-Spalten ohne Vorgabe, in **verschiedenen** Produkten:

- Manager: `projects.project_manager_id` NOT NULL, Fremdschlüssel auf `users`
- AI Brain: `projects.knowledge_path` NOT NULL

Ein Paket-Insert, der einen dieser Schlüssel wegläßt, bricht genau in einem
Produkt. Nie `feld => $wert ?? null` schreiben — den Schlüssel **weglassen**,
dann greift die Vorgabe der Spalte.

## Tests

```
composer install
vendor/bin/pest
```

18 Tests. Die Suite hat ihre Rotfähigkeit bewiesen: Beim ersten Lauf fand sie
eine Endlosrekursion (eine Methode `kind()`, die `field('kind')` liest — Eloquent
löst das fehlende Attribut über die gleichnamige Methode auf). Ein Riegel hält
die Falle jetzt.
