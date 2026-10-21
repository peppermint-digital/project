<?php

namespace Peppermint\Project\Tests;

/**
 * Fuer den Fall `run_migrations => false`. Die Konfiguration muss stehen, BEVOR
 * der Provider bootet — im Testkoerper gesetzt waere sie zu spaet, und die
 * Probe wuerde eine andere Frage beantworten als die gestellte.
 */
abstract class OhneMigrationenTestCase extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('project.run_migrations', false);
    }
}
