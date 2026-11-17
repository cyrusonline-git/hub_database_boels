<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Vlootvoorraad (Smartview "Items With Every Status"), zie config/sap.php en docs/sap-uploads.md. */
class FleetItem extends Model
{
    protected $table = 'fleet_items';

    public $timestamps = false;

    protected $guarded = [];

    public const STATUSSEN = [
        'beschikbaar' => 'Beschikbaar',
        'verhuurd' => 'Verhuurd',
        'onderhoud' => 'In onderhoud',
        'reparatie' => 'In reparatie',
        'onderweg' => 'Onderweg',
        'overig' => 'Overig',
    ];

    public static function statusKey(?string $status): string
    {
        return match (mb_strtolower(trim((string) $status))) {
            'beschikbaar', 'available' => 'beschikbaar',
            'verhuurd', 'on hire', 'on-hire' => 'verhuurd',
            'in onderhoud', 'onderhoud' => 'onderhoud',
            'in reparatie', 'reparatie' => 'reparatie',
            'onderweg', 'in transit' => 'onderweg',
            default => 'overig',
        };
    }
}
