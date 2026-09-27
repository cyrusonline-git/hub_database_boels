<?php

namespace App\Vraagbaak;

/** Antwoord op een vraag: een getal, een tabel of een lijst, met uitleg hoe het berekend is. */
class Resultaat
{
    public function __construct(
        public string $type,              // getal | tabel | tekst
        public mixed $waarde = null,      // bij getal
        public string $eenheid = '',
        public array $kolommen = [],      // bij tabel: ['Vestiging', 'Liters']
        public array $rijen = [],         // bij tabel: lijst van lijsten
        public string $uitleg = '',
        public array $sub = [],           // extra kerngetallen: ['Tankbeurten' => 12]
        public string $opmerking = '',
    ) {
    }

    public static function getal(float|int|null $waarde, string $eenheid = '', string $uitleg = '', array $sub = []): self
    {
        return new self('getal', $waarde ?? 0, $eenheid, [], [], $uitleg, $sub);
    }

    public static function tabel(array $kolommen, array $rijen, string $uitleg = '', array $sub = []): self
    {
        return new self('tabel', count($rijen), 'rijen', $kolommen, $rijen, $uitleg, $sub);
    }

    public static function tekst(string $tekst, string $uitleg = ''): self
    {
        return new self('tekst', $tekst, '', [], [], $uitleg);
    }

    public function toArray(): array
    {
        return ['type' => $this->type, 'waarde' => $this->waarde, 'eenheid' => $this->eenheid, 'kolommen' => $this->kolommen, 'rijen' => $this->rijen, 'uitleg' => $this->uitleg, 'sub' => $this->sub, 'opmerking' => $this->opmerking];
    }
}
