<?php

namespace Peppermint\Project\Exceptions;

use InvalidArgumentException;

class UnknownProjectKind extends InvalidArgumentException
{
    /**
     * @param  list<string>  $known
     */
    public static function forKey(string $key, array $known): self
    {
        // Die bekannten Schluessel gehoeren in die Meldung: Ein leerer Befund
        // ueber eine Faehigkeit ist meist ein falscher Name, und ohne die Liste
        // sucht der Naechste am falschen Ende.
        return new self(sprintf(
            'Unbekannte Projektart [%s]. Angemeldet ist %s. Arten stehen in config/project.php unter "kinds".',
            $key,
            $known === [] ? 'keine' : '['.implode(', ', $known).']',
        ));
    }
}
