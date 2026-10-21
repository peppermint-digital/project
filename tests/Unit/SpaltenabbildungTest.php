<?php

use Peppermint\Project\Models\Project;

/**
 * Die Abbildung ist der Grund, warum zwei gewachsene Produkte dasselbe Paket
 * benutzen koennen. Deshalb wird sie in BEIDE Richtungen geprueft: Eine Probe,
 * die nur zeigt, dass ein abgebildetes Feld uebersetzt wird, wuerde auch gruen
 * bleiben, wenn die Methode blind ihre Eingabe zurueckgibt.
 */
it('uebersetzt ein abgebildetes Feld in den Spaltennamen des Produkts', function () {
    config()->set('project.columns.projects.owner_id', 'project_manager_id');

    expect(Project::column('owner_id'))->toBe('project_manager_id');
});

it('laesst ein NICHT abgebildetes Feld unveraendert', function () {
    config()->set('project.columns.projects', ['owner_id' => 'project_manager_id']);

    // Gegenprobe nach unten: Haette `column()` einfach alles uebersetzt oder
    // immer null geliefert, waere der Test oben trotzdem gruen gewesen.
    expect(Project::column('name'))->toBe('name');
});

it('liest ein Feld durch die Abbildung, nicht durch den Spaltennamen', function () {
    config()->set('project.columns.projects.owner_id', 'project_manager_id');

    $projekt = new Project;
    $projekt->setRawAttributes(['project_manager_id' => 42]);

    expect($projekt->field('owner_id'))->toBe(42)
        ->and($projekt->field('name'))->toBeNull();
});

it('schreibt ein Feld unter dem Spaltennamen des Produkts', function () {
    config()->set('project.columns.projects.owner_id', 'project_manager_id');

    $projekt = (new Project)->setField('owner_id', 7);

    expect($projekt->getAttributes())->toHaveKey('project_manager_id')
        ->and($projekt->getAttributes())->not->toHaveKey('owner_id');
});

it('nimmt den Tabellennamen aus der Konfiguration', function () {
    config()->set('project.tables.projects', 'pm_projekte');

    expect((new Project)->getTable())->toBe('pm_projekte');
});

/**
 * Riegel gegen eine Falle, die beim ersten Lauf 365.000 Stack-Frames erzeugt
 * hat: Eloquent sucht bei einem fehlenden Attribut nach einer Beziehungsmethode
 * gleichen Namens. Eine Methode `kind()`, die `field('kind')` liest, ruft darum
 * sich selbst auf, bis der Speicher voll ist.
 *
 * Der Riegel prueft nicht den Namen, sondern die Wirkung — sonst bewacht er die
 * Umbenennung statt des Verhaltens.
 */
it('hat keine Methode, die wie ein Paketfeld heisst', function () {
    $felder = ['name', 'description', 'status', 'kind'];

    foreach ($felder as $feld) {
        expect(method_exists(Project::class, $feld))->toBeFalse(
            "Project::{$feld}() kollidiert mit dem Feld gleichen Namens — Eloquent "
            .'loest das fehlende Attribut ueber die Methode auf und rekursiert.'
        );
    }
});

it('liest ein fehlendes Feld als null, ohne zu rekursieren', function () {
    expect((new Project)->field('kind'))->toBeNull();
});
