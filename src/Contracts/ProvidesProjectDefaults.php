<?php

namespace Peppermint\Project\Contracts;

/**
 * Was nur das Produkt fuer ein neues Projekt wissen kann.
 *
 * ## Warum es diesen Haken gibt
 *
 * Beide Uebernehmer haben eine Pflichtspalte, die das Paket nicht fuellen kann:
 * der Manager `project_manager_id` (NOT NULL, Fremdschluessel auf `users`),
 * AI Brain `knowledge_path` (NOT NULL). Beide ohne Vorgabe.
 *
 * Die Regel aus Bug #1060 — „Schluessel weglassen, dann greift die Vorgabe der
 * Spalte" — hilft hier NICHT: Sie gilt nur fuer eine Spalte MIT Vorgabe. Beim
 * Kalender war `event_categories.color` NOT NULL mit Vorgabe, da heilte das
 * Weglassen. Ohne Vorgabe bricht auch das Weglassen. Drei Proben in
 * tests/Feature/UebernahmeTest.php halten den Unterschied fest.
 *
 * Die Alternative waere eine Migration auf zwei Produktionstabellen gewesen.
 * Dieser Haken kommt ohne sie aus: Das Paket legt nie blind an, sondern fragt.
 */
interface ProvidesProjectDefaults
{
    /**
     * Zusaetzliche Attribute fuer ein neues Projekt — unter den Spaltennamen
     * des Produkts, nicht unter den Paketfeldern.
     *
     * Ein Schluessel, fuer den es keinen Wert gibt, wird **weggelassen**, nicht
     * als null geliefert. Ein ausdrueckliches null in eine NOT-NULL-Spalte
     * laesst den ganzen Lauf platzen — genau so ist Bug #1060 entstanden.
     *
     * @param  array<string, mixed>  $attributes  was der Aufrufer schon gesetzt hat
     * @return array<string, mixed>
     */
    public function forCreate(array $attributes): array;
}
