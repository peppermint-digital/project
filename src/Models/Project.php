<?php

namespace Peppermint\Project\Models;

use Illuminate\Database\Eloquent\Model;
use Peppermint\Project\Kinds\ProjectKind;
use Peppermint\Project\Kinds\ProjectKindRegistry;

/**
 * Die Projekt-Identitaet.
 *
 * ## Was hier NICHT steht
 *
 * Abrechnung, Budget und Stundensaetze. Gemessen am 21.10.2026: die Kosten-
 * verfolgung liegt im Manager (allein `ProjectController` hat rund zwanzig
 * Stellen mit `hourly_rate`, dazu `BillingTransferService`, `ProjectBillingRun`
 * und vier Billing-Routen), die Rechnungsstellung in der Verwaltung. CRM und
 * AI Brain rechnen keine Projekte ab. Ein Feld, das nur ein Produkt braucht,
 * gehoert nicht in die geteilte Mitte — auch nicht „fuer spaeter".
 *
 * ## Wie andere Pakete andocken
 *
 * Nicht ueber den `subject`-Platz. Der ist einer, und er ist belegt: im Manager
 * ist eine Aufgabe das Subjekt ihres Zeitblocks und ihrer Zeiteintraege. Wuerde
 * das Projekt denselben Platz beanspruchen, koennte eine eingeplante Aufgabe
 * nicht zu einem Projekt gehoeren.
 *
 * `subject` sagt, WORUM es geht. Das Projekt sagt, WOZU etwas gehoert. Zwei
 * Achsen, zwei Spalten — der Projektbezug ist deshalb eine eigene, nullbare
 * Spalte in der Tabelle des Produkts (`tasks.project_id` gibt es im Manager
 * schon), und dieses Paket verlangt kein anderes per `require`.
 */
class Project extends Model
{
    protected $guarded = [];

    public function getTable(): string
    {
        return config('project.tables.projects', 'projects');
    }

    /**
     * Uebersetzt einen Paket-Feldnamen in den Spaltennamen des Produkts.
     *
     * Beide Uebernehmer haben ihre Tabelle laenger als dieses Paket und heissen
     * ihre Spalten verschieden — der Manager fuehrt den Verantwortlichen als
     * `project_manager_id`. Paketcode fragt deshalb nie eine Spalte direkt.
     */
    public static function column(string $field): string
    {
        return config("project.columns.projects.{$field}")
            ?? config("project.columns.{$field}", $field);
    }

    /**
     * Liest ein Paketfeld, egal wie die Spalte im Produkt heisst.
     */
    public function field(string $name): mixed
    {
        return $this->getAttribute(static::column($name));
    }

    /**
     * Setzt ein Paketfeld unter dem Spaltennamen des Produkts.
     */
    public function setField(string $name, mixed $value): static
    {
        $this->setAttribute(static::column($name), $value);

        return $this;
    }

    public static function kinds(): ProjectKindRegistry
    {
        return app(ProjectKindRegistry::class);
    }

    /**
     * Die Art dieses Projekts — oder null, wenn das Produkt keine fuehrt.
     *
     * Heisst `kindDefinition` und nicht `kind`, und das ist keine Geschmacks-
     * frage: Eloquent sucht bei einem fehlenden Attribut nach einer Beziehungs-
     * methode gleichen Namens. Eine Methode `kind()`, die `field('kind')` liest,
     * ruft darum sich selbst auf — bis der Speicher voll ist. Beim Kalender
     * heisst sie aus demselben Grund `kindDefinition()`.
     *
     * Ob die Art eine eigene Spalte ist oder eine schon vorhandene Kategorie-
     * Spalte, entscheidet das Produkt ueber `project.columns`. Das Paket sieht
     * in beiden Faellen dasselbe Feld.
     */
    public function kindDefinition(): ?ProjectKind
    {
        $kind = $this->field('kind');

        return $kind === null ? null : static::kinds()->get((string) $kind);
    }
}
