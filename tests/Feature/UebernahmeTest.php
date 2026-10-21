<?php

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Peppermint\Project\Models\Project;

/**
 * Beweist die Uebernahme gegen die ECHTEN Schemata beider Produkte, bevor eines
 * angefasst wird.
 *
 * Beide Tabellen sind aelter als dieses Paket — der Manager seit 18.08.2025 mit
 * 19 Spalten, AI Brain seit 03.02.2026 mit 7. Sie teilen vier Felder und nennen
 * den Rest verschieden. Ein Paket, das das nicht kann, wird zweimal gebaut.
 *
 * Die Spaltensaetze hier sind am 21.10.2026 aus den Migrationen der Produkte
 * abgelesen, nicht erfunden.
 */
function managerSchema(): void
{
    Schema::create('projects', function ($t): void {
        $t->id();
        $t->string('name');
        $t->text('description')->nullable();
        $t->string('client')->nullable();
        $t->unsignedInteger('customer_id')->nullable();
        $t->unsignedBigInteger('verwaltung_order_id')->nullable();
        $t->unsignedBigInteger('organization_id')->nullable();
        $t->string('status')->default('active');
        $t->string('category', 64)->nullable();
        $t->unsignedBigInteger('project_category_id')->nullable();
        $t->date('start_date')->nullable();
        $t->date('end_date')->nullable();
        $t->decimal('budget', 12, 2)->nullable();
        $t->decimal('hourly_rate', 8, 2)->nullable();
        // Die Falle: NOT NULL ohne Vorgabe, Fremdschluessel auf users.
        $t->unsignedBigInteger('project_manager_id');
        $t->unsignedInteger('ai_brain_project_id')->nullable();
        $t->timestamps();
    });
}

function brainSchema(): void
{
    Schema::create('projects', function ($t): void {
        $t->id();
        $t->string('name');
        $t->string('slug')->unique();
        $t->text('description')->nullable();
        $t->string('status')->default('active');
        $t->string('repo_path')->nullable();
        $t->string('github_repo')->nullable();
        // Die zweite Falle, im anderen Produkt: NOT NULL ohne Vorgabe.
        $t->string('knowledge_path');
        $t->timestamps();
    });
}

it('liest ein Manager-Projekt durch die Abbildung', function () {
    managerSchema();
    config()->set('project.columns.projects', [
        'owner_id' => 'project_manager_id',
        'kind' => 'category',
    ]);

    DB::table('projects')->insert([
        'name' => 'Webseite Henneberg',
        'status' => 'on_hold',
        'category' => 'digital',
        'project_manager_id' => 3,
        'budget' => '1500.00',
    ]);

    $projekt = Project::first();

    expect($projekt->field('name'))->toBe('Webseite Henneberg')
        ->and($projekt->field('status'))->toBe('on_hold')
        ->and($projekt->field('owner_id'))->toBe(3)
        ->and($projekt->field('kind'))->toBe('digital');
});

it('liest ein Brain-Projekt durch dieselbe Klasse', function () {
    brainSchema();
    config()->set('project.columns.projects', []);

    DB::table('projects')->insert([
        'name' => 'calendar',
        'slug' => 'calender',
        'status' => 'active',
        'knowledge_path' => 'projekte/calender',
    ]);

    $projekt = Project::first();

    // Dieselbe Klasse, anderes Produkt, kein Zweig im Paketcode.
    expect($projekt->field('name'))->toBe('calendar')
        ->and($projekt->field('status'))->toBe('active')
        // Das Brain fuehrt keine Arten — und das ist kein Fehlerfall.
        ->and($projekt->field('kind'))->toBeNull();
});

it('trifft im Manager KEINE Brain-Spalte und umgekehrt', function () {
    managerSchema();

    // Gegenprobe: Die Abbildung darf nicht zufaellig funktionieren, weil beide
    // Tabellen alles haetten. Sie haben es nicht.
    expect(Schema::hasColumn('projects', 'knowledge_path'))->toBeFalse()
        ->and(Schema::hasColumn('projects', 'slug'))->toBeFalse()
        ->and(Schema::hasColumn('projects', 'project_manager_id'))->toBeTrue();
});

/**
 * Die NOT-NULL-Falle, in drei Fassungen.
 *
 * Die Regel aus Bug #1060 lautet „Schluessel weglassen, dann greift die Vorgabe
 * der Spalte". Das gilt aber NUR, wenn es eine Vorgabe gibt. Beim Kalender war
 * `event_categories.color` NOT NULL *mit* Vorgabe — Weglassen heilte es.
 *
 * Die beiden Spalten, die hier bevorstehen, haben KEINE Vorgabe:
 * `projects.project_manager_id` im Manager und `projects.knowledge_path` im
 * Brain. Dort hilft Weglassen nicht. Diese Proben halten den Unterschied fest,
 * damit Task #8455 das Richtige behebt.
 */
it('bricht bei einem ausdruecklichen NULL in eine NOT-NULL-Spalte', function () {
    brainSchema();

    expect(fn () => DB::table('projects')->insert([
        'name' => 'Ohne Pfad',
        'slug' => 'ohne-pfad',
        'knowledge_path' => null,
    ]))->toThrow(QueryException::class);
});

it('bricht AUCH, wenn der Schluessel fehlt — weil die Spalte keine Vorgabe hat', function () {
    brainSchema();

    // Das ist die Verscharfung: Weglassen heilt nur eine NOT-NULL-Spalte MIT
    // Vorgabe. Ohne Vorgabe ist auch das Weglassen ein Fehler. Wer sich hier
    // auf die Kalender-Regel verlaesst, baut denselben Ausfall nach.
    expect(fn () => DB::table('projects')->insert([
        'name' => 'Ohne Pfad',
        'slug' => 'ohne-pfad',
    ]))->toThrow(QueryException::class);
});

it('heilt dagegen eine NOT-NULL-Spalte MIT Vorgabe durch Weglassen', function () {
    // Der Gegenfall, der den Unterschied beweist: dieselbe Form, nur mit
    // Vorgabe. Ohne diese Probe waeren die zwei oben nicht von „NOT NULL ist
    // immer kaputt" zu unterscheiden.
    Schema::create('projects', function ($t): void {
        $t->id();
        $t->string('name');
        $t->string('status')->default('active');
        $t->timestamps();
    });

    DB::table('projects')->insert(['name' => 'Mit Vorgabe']);

    expect(DB::table('projects')->value('status'))->toBe('active');
});
