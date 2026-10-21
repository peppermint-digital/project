<?php

namespace Peppermint\Project\Services;

use Peppermint\Project\Contracts\ProvidesProjectDefaults;
use Peppermint\Project\Exceptions\ProjectNotCreatable;
use Peppermint\Project\Models\Project;

/**
 * Der eine Weg, auf dem das Paket ein Projekt anlegt.
 *
 * Eine Frage, eine Antwort: Jeder Aufrufer im Paket — MCP-Werkzeuge, Quellen,
 * spaetere Verben — geht hier durch. Vier Stellen, die dasselbe halb richtig
 * tun, sind der Mechanismus hinter zwei Geldfehlern im Manager gewesen.
 */
class ProjectCreator
{
    public function __construct(
        protected ?ProvidesProjectDefaults $defaults = null,
    ) {}

    /**
     * Legt ein Projekt an.
     *
     * @param  array<string, mixed>  $fields  Paketfelder (name, description, status, kind)
     * @param  array<string, mixed>  $columns  zusaetzliche Produktspalten, schon unter ihrem echten Namen
     */
    public function create(array $fields, array $columns = []): Project
    {
        $attributes = [];

        foreach ($fields as $field => $value) {
            // Ein Feld ohne Wert wird WEGGELASSEN, nicht als null geschrieben.
            // Dann greift die Vorgabe der Spalte, und bei einem Abgleich bleibt
            // ein vorhandener Wert stehen (Bug #1060).
            if ($value === null) {
                continue;
            }

            $attributes[Project::column($field)] = $value;
        }

        // Was der Aufrufer ausdruecklich setzt, gewinnt — auch gegen den Haken.
        // Sonst koennte das Produkt eine bewusste Angabe stillschweigend
        // ueberschreiben, und der Aufrufer saehe keinen Unterschied.
        $attributes = array_merge(
            $this->defaultColumns($attributes),
            array_filter($columns, fn ($value) => $value !== null),
            $attributes,
        );

        if (! isset($attributes[Project::column('name')])) {
            throw ProjectNotCreatable::withoutName();
        }

        // Die Klasse des PRODUKTS, nicht die des Pakets — sonst fallen Casts,
        // Spalten-Vorgaben, Beobachter und Feldbereinigung des Produkts weg.
        // Im Manager haengt daran die HTML-Positivliste (Bug #814).
        $klasse = Project::resolveClass();

        return $klasse::create($attributes);
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    protected function defaultColumns(array $attributes): array
    {
        if ($this->defaults === null) {
            return [];
        }

        // Auch hier: null aus dem Haken wird verworfen, nicht geschrieben.
        return array_filter(
            $this->defaults->forCreate($attributes),
            fn ($value) => $value !== null,
        );
    }
}
