<?php

namespace Peppermint\Project\Kinds;

/**
 * Eine Projektart.
 *
 * Eine Art ist definiert durch das, was sie verbietet und was sie nach sich
 * zieht — sonst ist sie eine Beschriftung.
 *
 * ## Wozu es diese Stelle gibt
 *
 * Am 21.10.2026 gemessen: Die Regel „eine Webseite bekommt auch ein Projekt im
 * AI Brain, ein Druckauftrag nicht" existierte im Code an keiner Stelle.
 * `projects.ai_brain_project_id` war ein nullbarer Integer, den ein Mensch ins
 * Formular tippte, und kein Feld unterschied digital von Druck. Jedes Projekt,
 * bei dem das Verknuepfen vergessen wurde, war ein stiller Fehler.
 *
 * Diese Klasse ist der Ort, an dem die Regel aufgeschrieben und damit pruefbar
 * wird.
 */
abstract class ProjectKind
{
    /**
     * Der Schluessel, unter dem die Art in `project.kinds` steht und in der
     * Spalte des Produkts stehen wird.
     */
    abstract public function key(): string;

    /**
     * Was ein Mensch liest.
     */
    abstract public function label(): string;

    /**
     * Die Facetten, die ein Projekt dieser Art bekommt.
     *
     * Freie Kennungen, keine Klassennamen: Das Paket darf nicht wissen, welche
     * Produkte es gibt. Was eine Facette bedeutet, weiss nur die Anwendung —
     * sie fragt diese Liste und handelt danach.
     *
     * @return list<string>
     */
    public function facets(): array
    {
        return [];
    }

    public function hasFacet(string $facet): bool
    {
        return in_array($facet, $this->facets(), true);
    }

    /**
     * Zusaetzliche Pflichtfelder dieser Art, als Laravel-Regeln.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
