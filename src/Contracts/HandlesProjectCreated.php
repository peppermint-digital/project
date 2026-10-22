<?php

namespace Peppermint\Project\Contracts;

use Peppermint\Project\Models\Project;

/**
 * Was das Produkt tun muss, NACHDEM das Paket ein Projekt angelegt hat.
 *
 * ## Warum das ein zweiter Vertrag ist und keine Methode am ersten
 *
 * `ProvidesProjectDefaults` liefert Attribute. Manche Produkte brauchen aber
 * eine Nebenwirkung: Im AI Brain muss der Anlegende als `owner` ins
 * `project_user`-Pivot, sonst filtert `scopeVisibleTo` ihm sein eigenes
 * frisches Projekt weg. Das ist kein Attribut.
 *
 * Ein eigener Vertrag statt einer neuen Methode am bestehenden: So bricht
 * nichts bei Produkten, die ihn nicht brauchen — der Manager etwa braucht ihn
 * nicht.
 *
 * ## Warum nicht einfach ein `created`-Haken am Produktmodell
 *
 * Genau das war der erste Versuch am 22.10.2026, und ein fremder Test hat ihn
 * widerlegt: Ein Modell-Haken greift bei JEDEM `Project::create()`. Im Brain
 * legte damit auch ein Testaufbau, der absichtlich ein fremdes Projekt
 * erzeugt, dem gerade angemeldeten Menschen die Eigentuemerschaft unter —
 * `StopTimerTrifftNurEigeneTest` wurde rot, und zu Recht: „beim Anlegen
 * angemeldet" ist nicht dasselbe wie „das ist meines".
 *
 * Dieser Haken laeuft nur, wenn das PAKET anlegt. Jeder andere Weg bleibt
 * unveraendert.
 */
interface HandlesProjectCreated
{
    public function projectCreated(Project $project): void;
}
