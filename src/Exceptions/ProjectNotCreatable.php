<?php

namespace Peppermint\Project\Exceptions;

use RuntimeException;

class ProjectNotCreatable extends RuntimeException
{
    public static function withoutName(): self
    {
        return new self(
            'Ein Projekt braucht einen Namen. Uebergib "name" an ProjectCreator::create().'
        );
    }

    public static function missingProductColumn(string $column, string $hinweis): self
    {
        // Der Hinweis nennt den Haken: Ein leerer Befund ueber eine Pflichtspalte
        // ist sonst nicht von einem Programmfehler zu unterscheiden, und der
        // Naechste sucht in der Datenbank statt in der Konfiguration.
        return new self(sprintf(
            'Die Spalte [%s] ist in diesem Produkt Pflicht und wurde nicht gefuellt. %s '
            .'Konfiguriere "project.defaults" mit einer Klasse, die '
            .'Peppermint\Project\Contracts\ProvidesProjectDefaults umsetzt.',
            $column,
            $hinweis,
        ));
    }
}
