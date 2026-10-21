<?php

namespace Peppermint\Project\Kinds;

use Peppermint\Project\Exceptions\UnknownProjectKind;

/**
 * Die angemeldeten Projektarten.
 *
 * Leer ist ein gueltiger Zustand: Ein Produkt, das nur eine Sorte Projekt hat,
 * braucht keine Arten. Deshalb wirft `get()` auf einen unbekannten Schluessel,
 * aber eine leere Registratur ist kein Fehler — sonst muesste jedes Produkt
 * eine Art erfinden, um das Paket ueberhaupt einzubinden.
 */
class ProjectKindRegistry
{
    /** @var array<string, ProjectKind> */
    protected array $kinds = [];

    public function register(ProjectKind $kind): static
    {
        $this->kinds[$kind->key()] = $kind;

        return $this;
    }

    public function get(string $key): ProjectKind
    {
        return $this->kinds[$key] ?? throw UnknownProjectKind::forKey($key, array_keys($this->kinds));
    }

    public function has(string $key): bool
    {
        return isset($this->kinds[$key]);
    }

    /** @return array<string, ProjectKind> */
    public function all(): array
    {
        return $this->kinds;
    }

    /** @return list<string> */
    public function keys(): array
    {
        return array_keys($this->kinds);
    }
}
