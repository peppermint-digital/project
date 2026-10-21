<?php

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Peppermint\Project\Contracts\ProvidesProjectDefaults;
use Peppermint\Project\Exceptions\ProjectNotCreatable;
use Peppermint\Project\Services\ProjectCreator;

/** Füllt, was nur der Manager weiss. */
class ManagerDefaults implements ProvidesProjectDefaults
{
    public function forCreate(array $attributes): array
    {
        return ['project_manager_id' => 42];
    }
}

/** Füllt, was nur das Brain weiss — abgeleitet, nicht geraten. */
class BrainDefaults implements ProvidesProjectDefaults
{
    public function forCreate(array $attributes): array
    {
        $slug = str($attributes['name'] ?? 'projekt')->slug()->value();

        return ['slug' => $slug, 'knowledge_path' => "projekte/{$slug}"];
    }
}

it('bricht ohne Haken an der Pflichtspalte des Manager — sichtbar, nicht still', function () {
    managerSchema();

    expect(fn () => app(ProjectCreator::class)->create(['name' => 'Ohne Haken']))
        ->toThrow(QueryException::class);
});

it('legt mit dem Haken des Manager an', function () {
    managerSchema();
    config()->set('project.defaults', ManagerDefaults::class);

    $projekt = app(ProjectCreator::class)->create([
        'name' => 'Webseite Henneberg',
        'status' => 'active',
    ]);

    expect($projekt->project_manager_id)->toBe(42)
        ->and($projekt->field('name'))->toBe('Webseite Henneberg');
});

it('legt mit dem Haken des Brain an und leitet den tragenden Slug ab', function () {
    brainSchema();
    config()->set('project.defaults', BrainDefaults::class);

    $projekt = app(ProjectCreator::class)->create(['name' => 'Peppermint Project']);

    // Der Vault-Ordner heisst wie der Slug — ein Slug ohne seinen Pfad liess am
    // 31.07.2026 122 Memories ins Leere zeigen (Bug #693).
    expect($projekt->slug)->toBe('peppermint-project')
        ->and($projekt->knowledge_path)->toBe('projekte/peppermint-project');
});

it('schreibt KEIN ausdrueckliches null — bewiesen an einer NOT-NULL-Spalte mit Vorgabe', function () {
    // Die entscheidende Form, und zwar GENAU die aus Bug #1060:
    // event_categories.color war NOT NULL MIT Vorgabe. Ein ausdrueckliches null
    // laesst den Lauf platzen, ein weggelassener Schluessel greift die Vorgabe.
    //
    // An einer nullbaren Spalte waere diese Probe wertlos: Dort sieht
    // "null geschrieben" genauso aus wie "Schluessel weggelassen".
    Schema::create('projects', function ($t): void {
        $t->id();
        $t->string('name');
        $t->string('description')->default('keine Angabe');
        $t->string('status')->default('active');
        $t->timestamps();
    });

    app(ProjectCreator::class)->create(['name' => 'Ohne Beschreibung', 'description' => null]);

    $zeile = DB::table('projects')->first();

    // Waere der Schluessel als null in die Anfrage gewandert, haette SQLite hier
    // mit einem NOT-NULL-Verstoss abgebrochen statt die Vorgabe zu nehmen.
    expect($zeile->description)->toBe('keine Angabe')
        ->and($zeile->status)->toBe('active');
});

it('belegt die Gegenrichtung: ein ausdrueckliches null in dieselbe Spalte bricht', function () {
    Schema::create('projects', function ($t): void {
        $t->id();
        $t->string('name');
        $t->string('description')->default('keine Angabe');
        $t->timestamps();
    });

    // Ohne diese Gegenprobe waere der Test darueber nicht von "SQLite ist
    // nachsichtig mit null" zu unterscheiden. Er ist es nicht.
    expect(fn () => DB::table('projects')->insert([
        'name' => 'Mit ausdruecklichem null',
        'description' => null,
    ]))->toThrow(QueryException::class);
});

it('laesst die ausdrueckliche Angabe des Aufrufers gegen den Haken gewinnen', function () {
    managerSchema();
    config()->set('project.defaults', ManagerDefaults::class);

    $projekt = app(ProjectCreator::class)->create(
        ['name' => 'Eigener Verantwortlicher'],
        ['project_manager_id' => 7],
    );

    // Sonst koennte das Produkt eine bewusste Angabe still ueberschreiben.
    expect($projekt->project_manager_id)->toBe(7);
});

it('verlangt einen Namen', function () {
    managerSchema();
    config()->set('project.defaults', ManagerDefaults::class);

    expect(fn () => app(ProjectCreator::class)->create(['status' => 'active']))
        ->toThrow(ProjectNotCreatable::class, 'braucht einen Namen');
});

it('loest den Haken ueber den Container auf, damit Tests ihn ersetzen koennen', function () {
    managerSchema();
    config()->set('project.defaults', ManagerDefaults::class);
    app()->bind(ManagerDefaults::class, fn () => new class implements ProvidesProjectDefaults
    {
        public function forCreate(array $attributes): array
        {
            return ['project_manager_id' => 99];
        }
    });

    expect(app(ProjectCreator::class)->create(['name' => 'Ersetzt'])->project_manager_id)
        ->toBe(99);
});

it('weist eine Haken-Klasse ab, die den Vertrag nicht umsetzt', function () {
    config()->set('project.defaults', stdClass::class);

    expect(fn () => app(ProjectCreator::class))
        ->toThrow(InvalidArgumentException::class, 'muss');
});

it('benutzt die Spaltenabbildung auch beim Anlegen', function () {
    managerSchema();
    config()->set('project.defaults', ManagerDefaults::class);
    config()->set('project.columns.projects.kind', 'category');

    app(ProjectCreator::class)->create(['name' => 'Druck', 'kind' => 'druck']);

    // Das Feld heisst kind, die Spalte category — und genau das muss passieren.
    expect(DB::table('projects')->value('category'))->toBe('druck')
        ->and(Schema::hasColumn('projects', 'kind'))->toBeFalse();
});
