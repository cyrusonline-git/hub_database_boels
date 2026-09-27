<?php

namespace App\Vraagbaak;

/**
 * Eén vraag uit de catalogus.
 *  - moet: lijst van woordgroepen; van elke groep moet minstens één woord in de vraag voorkomen
 *  - bonus: losse woorden die de score verhogen
 *  - params: welke parameters de vraag gebruikt (depot, periode, top, brandstof, machine, naam, status)
 *  - rol: 'iedereen' | 'manager' (financieel/overzichten) | 'admin'
 *  - periode: standaard periode als de gebruiker er geen noemt: 'maand' | 'jaar' | 'geen' | 'week'
 */
class Vraag
{
    public function __construct(
        public string $id,
        public string $bron,
        public string $titel,
        public array $moet,
        public array $bonus = [],
        public array $params = ['depot', 'periode'],
        public string $rol = 'iedereen',
        public string $periode = 'maand',
        public ?\Closure $bereken = null,
        public string $uitleg = '',
    ) {
    }

    public function bereken(DataBrug $brug, array $p): Resultaat
    {
        return ($this->bereken)($brug, $p);
    }
}
