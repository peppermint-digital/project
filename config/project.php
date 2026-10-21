<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Registrierungen
    |--------------------------------------------------------------------------
    |
    | Was das Produkt entscheidet, nicht das Paket.
    |
    | `kinds` sind die Projektarten. Eine Art entscheidet, welche Facetten ein
    | Projekt ueberhaupt hat — ob ein digitales Projekt einen Wissens-Zwilling
    | bekommt und ein Druckauftrag keinen. Das Paket kennt keine einzige Art;
    | es kennt nur den Platz, an dem sie stehen.
    |
    */

    'kinds' => [
        // 'digital' => \App\Project\DigitalProject::class,
    ],

    /*
    | Die erlaubten Status, als Liste des Produkts.
    |
    | Es gibt hier bewusst KEIN Paket-Enum. Gemessen am 21.10.2026: der Manager
    | fuehrt vier Werte als DB-Enum (active, on_hold, completed, cancelled),
    | AI Brain zwei als freien String (active, archived). Ein gemeinsames Enum
    | koennte nur eines von beidem sein und wuerde dem anderen Produkt seine
    | Bestandsdaten ungueltig machen. Dasselbe Muster fuehrt peppermint/tasks.
    */

    'statuses' => ['active'],

    'default_status' => 'active',

    /*
    | Was nur das Produkt fuer ein neues Projekt wissen kann.
    |
    | Beide Uebernehmer haben eine Pflichtspalte ohne Vorgabe, die das Paket
    | nicht fuellen kann: der Manager `project_manager_id` (NOT NULL, FK auf
    | users), AI Brain `knowledge_path` (NOT NULL). Ohne diesen Haken muesste
    | eine Migration zwei Produktionstabellen anfassen.
    |
    | `null` heisst: Das Paket legt in diesem Produkt keine Projekte selbst an.
    */

    'defaults' => null,

    /*
    |--------------------------------------------------------------------------
    | Tabellen- und Spalten-Abbildung
    |--------------------------------------------------------------------------
    |
    | Beide Uebernehmer haben ihre projects-Tabelle laenger als dieses Paket
    | (Manager seit 18.08.2025, AI Brain seit 03.02.2026). Ein Paket, das nur
    | auf frischen Tabellen laeuft, kann ein gewachsenes Produkt nicht nehmen —
    | und wird dann zweimal gebaut.
    |
    */

    'tables' => [
        'projects' => 'projects',
    ],

    'columns' => [
        'projects' => [
            // 'owner_id' => 'project_manager_id',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Migrationen
    |--------------------------------------------------------------------------
    |
    | `false`, wenn das Produkt die Tabelle schon hat. Dann laufen auch die
    | Migrationen aus `migrations-optional` nicht — die liegen dort fuer den
    | Fall, dass ein Produkt eine Tabelle NOCH nicht hat, und jede einzelne
    | traegt zusaetzlich einen Schema::hasTable-Riegel.
    |
    */

    'run_migrations' => true,

    /*
    |--------------------------------------------------------------------------
    | Produktkleinkram
    |--------------------------------------------------------------------------
    |
    | Die Berechtigung fragt das Paket nur unter einem Namen ab; wer sie
    | erteilt, entscheidet das Produkt.
    |
    */

    'permissions' => [
        'view' => 'view projects',
        'manage' => 'manage projects',
    ],
];
