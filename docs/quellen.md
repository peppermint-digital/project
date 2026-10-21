# Projekt-Quellen für Nicht-Eigentümer (Task #8461)

Vorarbeit vom 21.10.2026, am Code der Verwaltung abgelesen. **Noch nicht gebaut** —
die geteilte Form ist erst am zweiten Nicht-Eigentümer beweisbar (Wiki #359,
Prüffrage 3), und das CRM hat heute keinen Projektcode.

## Korrektur einer eigenen Zahl

Ich hatte gemeldet, in `app/Services/PeppermintManagerService.php` der Verwaltung
lägen „647 Zeilen handgeschriebener Fernzugriff", die das Paket absorbieren würde.
Nach Zeilenbereichen gezählt ist das falsch:

| Anliegen | Zeilen | gehört zu |
|---|---|---|
| Komm-Layer-Bequemlichkeiten | ~317 | bleibt im Produkt |
| Aufgaben-Verben | ~144 | `peppermint/tasks`, nicht hier |
| Nutzer | ~58 | — |
| **Projekte** | **~86** | `peppermint/project` |

Und die ~317 Zeilen sind **keine Doppelung**: Die Verwaltung benutzt
`peppermint/ai-brain-bridge` bereits (`AiBrain::gateway(...)`). `ueberBrain()` ist ein
dünner Mantel darüber, der Acting-User, Fehlermeldungen, Zwischenspeicher und das
Auspacken der Antwort beisteuert — nicht eine zweite Fassung des Gateways.

Es verschwinden also rund 86 Zeilen, nicht 647.

## Die Form, die dabei sichtbar wurde

Die fünf Projekt-Methoden sind dünn und folgen einem Muster:

| Methode | Fähigkeit | Besonderheit |
|---|---|---|
| `getProjects()` | `projects.list` | — |
| `createProject($daten)` | `projects.create` | `project_manager_id` ist im Manager Pflicht |
| `verknuepfeProjektMitAuftrag($id, $orderId)` | `projects.update` | `null` löst die Verknüpfung wieder |
| `projectForOrder($orderId)` | `projects.list` + Filter | verhindert Duplikate beim zweiten Klick |
| `projectOptions()` | `getProjects()` + Abbildung | `{id, name}` für Auswahlfelder |

**Der eine Unterschied zwischen Produkten ist der Name der Verweis-Spalte.** Die
Verwaltung filtert `projects.list` nach `verwaltung_order_id`; das CRM würde nach
seinem eigenen Verweis filtern. Das ist ein **Parameter**, keine zweite Klasse —
dieselbe Regel, an der im Kalender drei Produkte dieselbe Klasse dreimal hatten
(915/915/929 Zeichen, Unterschied: eine Zeile).

Eine Quelle braucht demnach:

- den Namen der Verweis-Spalte des Produkts (`verwaltung_order_id`, …)
- die Fähigkeits-Präfixe (`projects.list` / `.create` / `.update`)
- einen Haken für die Pflichtfelder des Eigentümers — das ist derselbe
  `ProvidesProjectDefaults`, der schon steht

## Zwei Dinge, die beim Umzug nicht verloren gehen dürfen

Beide stehen als Begründung im Code der Verwaltung und wären ohne Lesen weggefallen:

1. **`projectForOrder()` existiert gegen Duplikate.** Ohne diese Abfrage legte jeder
   weitere Klick auf „Projekt erstellen" ein weiteres Projekt an — und die Zeiten
   verteilten sich unbemerkt auf zwei Projekte.
2. **`projectOptions()` darf kein `isConfigured()` davor haben** (AI Brain #5243). Das
   prüft die *Peer*-Zugangsdaten, die für diesen Weg bedeutungslos sind, seit er über
   AI Brain läuft. Die Prüfung ließe die Liste genau dann leer werden, wenn die
   Peer-Zugangsdaten entfernt werden — also wenn die Abschaltung als geschafft gilt,
   und ohne Fehlermeldung.
