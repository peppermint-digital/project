<?php

use Illuminate\Support\Facades\Schema;

it('migriert gar nicht, wenn run_migrations aus ist', function () {
    $this->artisan('migrate')->run();

    // Gegenprobe nach oben steht in MigrationTest: dort legt derselbe Aufruf
    // die Tabelle sehr wohl an. Ohne diese Gegenrichtung waere ein "nicht da"
    // nicht von einem kaputten Testaufbau zu unterscheiden.
    expect(Schema::hasTable('projects'))->toBeFalse();
});
