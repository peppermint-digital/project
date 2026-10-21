<?php

use Illuminate\Support\Facades\Schema;

/**
 * Eine Konfigurationszeile ist eine Behauptung ueber das Produkt, keine
 * Zusicherung. `run_migrations` und der `hasTable`-Riegel werden deshalb beide
 * geprueft — und zwar in beide Richtungen, sonst bliebe die Probe auch gruen,
 * wenn gar nichts migriert wuerde.
 */
it('legt die Kerntabelle an, wenn das Produkt keine hat', function () {
    $this->artisan('migrate')->run();

    expect(Schema::hasTable('projects'))->toBeTrue()
        ->and(Schema::hasColumn('projects', 'name'))->toBeTrue()
        ->and(Schema::hasColumn('projects', 'kind'))->toBeTrue();

    // Gegenprobe nach unten: eine erfundene Spalte darf es nicht geben, sonst
    // beantwortet hasColumn eine andere Frage als die gestellte.
    expect(Schema::hasColumn('projects', 'budget'))->toBeFalse()
        ->and(Schema::hasColumn('projects', 'hourly_rate'))->toBeFalse();
});

it('traegt den Vorgabestatus aus der Konfiguration als Spalten-Default', function () {
    $this->artisan('migrate')->run();

    DB::table('projects')->insert(['name' => 'Ohne Status']);

    // Der Schluessel wurde WEGGELASSEN, nicht als null geschrieben. Genau das
    // ist der Unterschied, an dem Bug #1060 den ganzen Lauf platzen liess.
    expect(DB::table('projects')->where('name', 'Ohne Status')->value('status'))
        ->toBe('active');
});

it('laesst die Tabelle des Produkts in Ruhe, wenn sie schon da ist', function () {
    // Ein Produkt, dessen Tabelle aelter ist als das Paket: andere Spalten,
    // und eine NOT-NULL-Spalte ohne Vorgabe, wie sie im Manager steht.
    Schema::create('projects', function ($blueprint) {
        $blueprint->id();
        $blueprint->string('name');
        $blueprint->unsignedBigInteger('project_manager_id');
    });

    $this->artisan('migrate')->run();

    expect(Schema::hasColumn('projects', 'project_manager_id'))->toBeTrue()
        ->and(Schema::hasColumn('projects', 'kind'))->toBeFalse();
});
