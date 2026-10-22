<?php

use Illuminate\Support\Facades\Schema;
use Peppermint\Project\Models\Project;
use Peppermint\Project\Testing\AssertsProjectAdoption;
use PHPUnit\Framework\AssertionFailedError;

/**
 * Die geteilten Zusicherungen muessen SCHEITERN, wenn sie sollen.
 *
 * Ein Helfer, der immer gruen bleibt, ist schlimmer als keiner: Er sieht in
 * zwei Produkten nach Nachweis aus und ist keiner. Jede Probe hier hat deshalb
 * ein Paar — einmal erfuellt, einmal verletzt.
 */
class ErbendesTestmodell extends Project
{
    protected $table = 'projects';
}

it('assertInheritsCore besteht, wenn das Produktmodell erbt', function () {
    config()->set('project.model', ErbendesTestmodell::class);

    // Als Erwartung formuliert, nicht als blosser Aufruf: Ein Test ohne eigene
    // Zusicherung wird von PHPUnit als "risky" gemeldet — er sieht gruen aus
    // und sagt nichts.
    expect(fn () => AssertsProjectAdoption::assertInheritsCore())
        ->not->toThrow(AssertionFailedError::class);
});

it('assertInheritsCore scheitert ohne Eintrag', function () {
    config()->set('project.model', null);

    expect(fn () => AssertsProjectAdoption::assertInheritsCore())
        ->toThrow(AssertionFailedError::class, 'ist nicht gesetzt');
});

it('assertInheritsCore scheitert bei einer Klasse, die nicht erbt', function () {
    config()->set('project.model', stdClass::class);

    expect(fn () => AssertsProjectAdoption::assertInheritsCore())
        ->toThrow(AssertionFailedError::class, 'erbt nicht von');
});

it('assertAdoptsExistingTable besteht bei einer gewachsenen Tabelle', function () {
    config()->set('project.run_migrations', false);
    Schema::create('projects', function ($t): void {
        $t->id();
        $t->string('name');
        $t->unsignedBigInteger('eigene_spalte');
        $t->timestamps();
    });

    expect(fn () => AssertsProjectAdoption::assertAdoptsExistingTable(['eigene_spalte']))
        ->not->toThrow(AssertionFailedError::class);
});

it('assertAdoptsExistingTable scheitert, wenn run_migrations an ist', function () {
    config()->set('project.run_migrations', true);
    Schema::create('projects', function ($t): void {
        $t->id();
        $t->string('name');
        $t->timestamps();
    });

    expect(fn () => AssertsProjectAdoption::assertAdoptsExistingTable(['name']))
        ->toThrow(AssertionFailedError::class, 'run_migrations');
});

it('assertAdoptsExistingTable scheitert, wenn eine kind-Spalte entstanden ist', function () {
    config()->set('project.run_migrations', false);
    Schema::create('projects', function ($t): void {
        $t->id();
        $t->string('name');
        $t->string('kind')->nullable();
        $t->timestamps();
    });

    expect(fn () => AssertsProjectAdoption::assertAdoptsExistingTable(['name']))
        ->toThrow(AssertionFailedError::class, 'kind-Spalte');
});

it('assertAdoptsExistingTable verlangt eine Gegenprobe nach oben', function () {
    config()->set('project.run_migrations', false);

    // Ohne genannte Spalte waere die Probe nicht von einer leeren Datenbank zu
    // unterscheiden — und genau das soll sie nicht zulassen.
    expect(fn () => AssertsProjectAdoption::assertAdoptsExistingTable([]))
        ->toThrow(AssertionFailedError::class, 'mindestens eine gewachsene Spalte');
});

it('assertCreatesWithNameOnly legt an und gibt das Projekt zurueck', function () {
    Schema::create('projects', function ($t): void {
        $t->id();
        $t->string('name');
        $t->string('status')->default('active');
        $t->timestamps();
    });

    $projekt = AssertsProjectAdoption::assertCreatesWithNameOnly('Nur ein Name');

    expect($projekt)->toBeInstanceOf(Project::class)
        ->and($projekt->name)->toBe('Nur ein Name');
});
