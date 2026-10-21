<?php

use Peppermint\Project\Exceptions\UnknownProjectKind;
use Peppermint\Project\Kinds\ProjectKind;
use Peppermint\Project\Kinds\ProjectKindRegistry;
use Peppermint\Project\Models\Project;

class DigitalesProjekt extends ProjectKind
{
    public function key(): string
    {
        return 'digital';
    }

    public function label(): string
    {
        return 'Digitales Projekt';
    }

    public function facets(): array
    {
        return ['wissen', 'auftrag'];
    }
}

class Druckauftrag extends ProjectKind
{
    public function key(): string
    {
        return 'druck';
    }

    public function label(): string
    {
        return 'Druckauftrag';
    }

    public function facets(): array
    {
        return ['auftrag'];
    }
}

it('meldet die Arten aus der Konfiguration an', function () {
    config()->set('project.kinds', ['digital' => DigitalesProjekt::class]);

    expect(app(ProjectKindRegistry::class)->keys())->toBe(['digital']);
});

it('loest die Art ueber den Container auf, damit Tests sie ersetzen koennen', function () {
    config()->set('project.kinds', ['digital' => DigitalesProjekt::class]);

    // Ein direktes `new` an der Aufrufstelle wuerde diese Ersetzung wirkungslos
    // machen — und der Test bliebe gruen, ohne noch etwas zu pruefen.
    app()->bind(DigitalesProjekt::class, fn () => new Druckauftrag);

    expect(app(ProjectKindRegistry::class)->keys())->toBe(['druck']);
});

it('traegt die bekannten Arten in die Fehlermeldung', function () {
    config()->set('project.kinds', ['digital' => DigitalesProjekt::class]);

    expect(fn () => app(ProjectKindRegistry::class)->get('webseite'))
        ->toThrow(UnknownProjectKind::class, 'Unbekannte Projektart [webseite]');

    // Gegenprobe: die Liste muss wirklich drinstehen, nicht nur der Name.
    try {
        app(ProjectKindRegistry::class)->get('webseite');
    } catch (UnknownProjectKind $e) {
        expect($e->getMessage())->toContain('[digital]');
    }
});

it('haelt eine leere Registratur fuer gueltig', function () {
    config()->set('project.kinds', []);

    // Ein Produkt mit nur einer Sorte Projekt soll keine Art erfinden muessen.
    expect(app(ProjectKindRegistry::class)->all())->toBe([]);
});

it('entscheidet ueber die Facetten — die Regel, die vorher nirgends stand', function () {
    config()->set('project.kinds', [
        'digital' => DigitalesProjekt::class,
        'druck' => Druckauftrag::class,
    ]);

    $arten = app(ProjectKindRegistry::class);

    expect($arten->get('digital')->hasFacet('wissen'))->toBeTrue()
        ->and($arten->get('druck')->hasFacet('wissen'))->toBeFalse()
        ->and($arten->get('druck')->hasFacet('auftrag'))->toBeTrue();
});

it('liest die Art eines Projekts durch die Abbildung', function () {
    config()->set('project.kinds', ['digital' => DigitalesProjekt::class]);
    config()->set('project.columns.projects.kind', 'category');

    $projekt = new Project;
    $projekt->setRawAttributes(['category' => 'digital']);

    // Dasselbe Feld, egal ob das Produkt eine eigene Spalte fuehrt oder eine
    // vorhandene Kategorie-Spalte benutzt.
    expect($projekt->kindDefinition()?->label())->toBe('Digitales Projekt');
});

it('gibt null zurueck, wenn das Projekt keine Art traegt', function () {
    config()->set('project.kinds', ['digital' => DigitalesProjekt::class]);

    expect((new Project)->kindDefinition())->toBeNull();
});
