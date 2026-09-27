<?php

namespace App\Vraagbaak;

class BronNietBeschikbaar extends \RuntimeException
{
    public function __construct(public string $bron)
    {
        parent::__construct('De database van '.(config("vraagbaak.bronnen.$bron.naam") ?? $bron).' is op dit moment niet bereikbaar.');
    }
}
