<?php

namespace Peppermint\Project\Testing;

use Illuminate\Support\Facades\Schema;
use Peppermint\Project\Models\Project;
use Peppermint\Project\Services\ProjectCreator;
use PHPUnit\Framework\Assert;

/**
 * Die Zusicherungen, die in JEDEM uebernehmenden Produkt gleich aussehen.
 *
 * ## Warum es diese Klasse gibt
 *
 * Am 22.10.2026, als mit Manager und AI Brain der zweite Eigentuemer
 * angebunden war, wurde Prueffrage 3 aus dem Paket-Vertrag erstmals
 * beantwortbar: *Schreibt jedes Produkt denselben Verdrahtungscode?*
 *
 * Gemessen: Die Konfiguration unterscheidet sich in Werten (richtig so), die
 * Haken sind wirklich verschieden (richtig so) — aber **3 von 9 Testfaellen**
 * waren in beiden Produkten wortgleich benannt und in der Form identisch. Das
 * ist die Doppelung, und nach Regel 6 des Vertrags gehoert sie hierher: „Was
 * in jedem Produkt gleich aussehen wuerde, gehoert ins Paket."
 *
 * ## Was NICHT hierher gehoert
 *
 * Alles Produkt-Eigene bleibt im Produkttest: beim Manager die
 * Spaltenabbildung und die vier Status, beim Brain der Vault-Haken und der
 * Riegel gegen einen Lauf auf der Produktionsdatenbank. Diese Klasse nimmt
 * dem Produkt nicht die Arbeit ab, sondern nur die Wiederholung.
 */
class AssertsProjectAdoption
{
    /**
     * Das Produkt erbt vom Paketmodell, statt es nachzubauen.
     *
     * Geprueft wird die KONFIGURIERTE Klasse, nicht eine uebergebene: Ein
     * Produkt, das erbt, aber `project.model` nicht eintraegt, haette sonst
     * eine gruene Probe — und das Paket legte trotzdem sein eigenes Modell an.
     */
    public static function assertInheritsCore(): void
    {
        $konfiguriert = config('project.model');

        Assert::assertNotNull(
            $konfiguriert,
            'config/project.php: "model" ist nicht gesetzt. Dann legt das Paket SEIN '
            .'Modell an und umgeht Casts, Spalten-Vorgaben, Beobachter und '
            .'Feldbereinigung des Produkts.'
        );

        Assert::assertTrue(
            is_subclass_of($konfiguriert, Project::class),
            sprintf('%s erbt nicht von %s.', $konfiguriert, Project::class),
        );

        Assert::assertSame($konfiguriert, Project::resolveClass());
    }

    /**
     * Das Paket migriert nicht in die gewachsene Tabelle des Produkts hinein.
     *
     * @param  list<string>  $gewachseneSpalten  Spalten, die es im Produkt gibt und
     *                                           weiter geben muss — die Gegenprobe
     *                                           nach oben. Ohne sie waere „keine
     *                                           kind-Spalte" nicht von „gar keine
     *                                           Tabelle" zu unterscheiden.
     */
    public static function assertAdoptsExistingTable(array $gewachseneSpalten): void
    {
        Assert::assertNotEmpty(
            $gewachseneSpalten,
            'Nenne mindestens eine gewachsene Spalte — sonst ist diese Probe '
            .'nicht von einer leeren Datenbank zu unterscheiden.'
        );

        Assert::assertFalse(
            (bool) config('project.run_migrations'),
            'config/project.php: "run_migrations" muss aus sein, wenn das Produkt '
            .'seine Tabelle schon hat.'
        );

        $tabelle = config('project.tables.projects', 'projects');

        Assert::assertFalse(
            Schema::hasColumn($tabelle, 'kind'),
            "Die Kernmigration des Pakets hat eine kind-Spalte in {$tabelle} angelegt, "
            .'die hier niemand bestellt hat.'
        );

        foreach ($gewachseneSpalten as $spalte) {
            Assert::assertTrue(
                Schema::hasColumn($tabelle, $spalte),
                "Die gewachsene Spalte {$spalte} fehlt in {$tabelle}."
            );
        }
    }

    /**
     * Der Gutfall: anlegen, wenn ein optionales Feld FEHLT.
     *
     * Das ist der Fall, in dem ein ausdrueckliches NULL den Lauf platzen liesse
     * (Bug #1060). Geprueft wird die Wirkung in der Tabelle des Produkts, nicht
     * am Objekt — ein frisch angelegtes Modell traegt die Vorgabewerte seiner
     * Spalten nicht.
     *
     * Gibt das Projekt zurueck, damit das Produkt seine eigenen Felder danach
     * selbst pruefen kann.
     */
    public static function assertCreatesWithNameOnly(string $name): Project
    {
        $projekt = app(ProjectCreator::class)->create(['name' => $name]);

        Assert::assertTrue($projekt->exists, 'Das Projekt wurde nicht angelegt.');

        $tabelle = config('project.tables.projects', 'projects');
        $spalte = Project::column('name');

        Assert::assertTrue(
            $projekt->newQuery()->where($spalte, $name)->exists(),
            "Das Projekt steht nicht in {$tabelle}."
        );

        return $projekt;
    }
}
