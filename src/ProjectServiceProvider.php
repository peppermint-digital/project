<?php

namespace Peppermint\Project;

use Illuminate\Support\ServiceProvider;
use Peppermint\Project\Kinds\ProjectKind;
use Peppermint\Project\Kinds\ProjectKindRegistry;

class ProjectServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/project.php', 'project');

        $this->app->singleton(ProjectKindRegistry::class, function (): ProjectKindRegistry {
            $registry = new ProjectKindRegistry;

            foreach ((array) config('project.kinds', []) as $key => $kind) {
                // Ueber den Container aufloesen, nicht `new`: Ersetzt ein
                // Produkttest eine Art per Binding, macht ein direktes `new`
                // die Ersetzung wirkungslos — und der Test bleibt gruen, ohne
                // noch etwas zu pruefen.
                $instance = is_string($kind) ? $this->app->make($kind) : $kind;

                if (! $instance instanceof ProjectKind) {
                    throw new \InvalidArgumentException(sprintf(
                        'config/project.php: "kinds.%s" ist keine ProjectKind-Art, sondern %s.',
                        is_string($key) ? $key : (string) $key,
                        get_debug_type($instance),
                    ));
                }

                $registry->register($instance);
            }

            return $registry;
        });
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/project.php' => config_path('project.php'),
        ], 'project-config');

        // `run_migrations` ist aus: Das Produkt hat seine Tabelle schon. Dann
        // laeuft auch nichts aus migrations-optional — jede Datei dort traegt
        // zusaetzlich einen eigenen Riegel, weil eine Konfigurationszeile eine
        // Behauptung ueber das Produkt ist und keine Zusicherung.
        if (config('project.run_migrations', true)) {
            $this->loadMigrationsFrom([
                __DIR__.'/../database/migrations',
                __DIR__.'/../database/migrations-optional',
            ]);
        }
    }
}
