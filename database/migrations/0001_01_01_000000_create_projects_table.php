<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Die Kerntabelle — fuer ein Produkt, das noch keine hat.
 *
 * Bewusst schmal. Gemessen am 21.10.2026 hat der Manager 19 Spalten und AI
 * Brain 7; gemeinsam sind ihnen vier. Alles darueber ist Produktsache, und eine
 * Spalte nachzuruesten ist billig — eine aus der geteilten Mitte wieder heraus
 * zu holen ist es nicht.
 */
return new class extends Migration
{
    public function up(): void
    {
        $table = config('project.tables.projects', 'projects');

        if (Schema::hasTable($table)) {
            return;
        }

        Schema::create($table, function (Blueprint $blueprint): void {
            $blueprint->id();

            // Ring 1 — ohne das existiert kein Projekt.
            $blueprint->string('name');

            // Ring 2 — alles nullbar oder mit Vorgabe.
            $blueprint->text('description')->nullable();

            // Kein Enum: die Vokabulare der Produkte widersprechen sich
            // (Manager vier Werte, AI Brain zwei). Die erlaubte Liste steht in
            // config/project.php und gehoert dem Produkt.
            $blueprint->string('status')->default(config('project.default_status', 'active'));

            // Die Art entscheidet, welche Facetten das Projekt hat. Nullbar,
            // weil ein Produkt mit nur einer Sorte Projekt keine Arten braucht.
            $blueprint->string('kind')->nullable();

            $blueprint->timestamps();

            $blueprint->index('status');
            $blueprint->index('kind');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('project.tables.projects', 'projects'));
    }
};
