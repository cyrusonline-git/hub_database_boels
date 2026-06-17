<?php

namespace App\Services\Import;

use App\Models\Customer;
use App\Models\Employee;
use App\Models\Machine;
use App\Models\Project;

/**
 * Definieert welke entiteiten geïmporteerd kunnen worden,
 * met welke kolommen, en welke verplichte zijn.
 */
class EntityRegistry
{
    public static function all(): array
    {
        return [
            'customer' => [
                'label' => 'Klanten',
                'model' => Customer::class,
                'unique_keys' => ['customer_number'],
                'fields' => [
                    'customer_number' => ['label' => 'Klantnummer', 'required' => true],
                    'customer_name'   => ['label' => 'Klantnaam',   'required' => true],
                    'status'          => ['label' => 'Status'],
                    'kvk_number'      => ['label' => 'KvK nummer'],
                    'vat_number'      => ['label' => 'BTW nummer'],
                    'email'           => ['label' => 'E-mail'],
                    'phone'           => ['label' => 'Telefoon'],
                    'address_street'  => ['label' => 'Straat'],
                    'address_number'  => ['label' => 'Huisnummer'],
                    'address_postal'  => ['label' => 'Postcode'],
                    'address_city'    => ['label' => 'Plaats'],
                    'address_country' => ['label' => 'Land'],
                    'external_id'     => ['label' => 'Externe ID'],
                    'source_system'   => ['label' => 'Bronsysteem'],
                ],
            ],
            'machine' => [
                'label' => 'Machines',
                'model' => Machine::class,
                'unique_keys' => ['machine_number'],
                'fields' => [
                    'machine_number' => ['label' => 'Machinenummer', 'required' => true],
                    'description'    => ['label' => 'Omschrijving', 'required' => true],
                    'subgroup_id'    => ['label' => 'Subgroep ID',  'required' => true],
                    'brand'          => ['label' => 'Merk'],
                    'model'          => ['label' => 'Model'],
                    'serial_number'  => ['label' => 'Serienummer'],
                    'year'           => ['label' => 'Bouwjaar'],
                    'status'         => ['label' => 'Status'],
                    'location'       => ['label' => 'Locatie'],
                    'external_id'    => ['label' => 'Externe ID'],
                    'source_system'  => ['label' => 'Bronsysteem'],
                ],
            ],
            'project' => [
                'label' => 'Projecten',
                'model' => Project::class,
                'unique_keys' => ['project_number'],
                'fields' => [
                    'project_number' => ['label' => 'Projectnummer', 'required' => true],
                    'project_name'   => ['label' => 'Projectnaam',   'required' => true],
                    'customer_id'    => ['label' => 'Klant-ID',      'required' => true],
                    'status'         => ['label' => 'Status'],
                    'description'    => ['label' => 'Omschrijving'],
                    'start_date'     => ['label' => 'Startdatum'],
                    'end_date'       => ['label' => 'Einddatum'],
                ],
            ],
            'employee' => [
                'label' => 'Medewerkers',
                'model' => Employee::class,
                'unique_keys' => ['employee_number'],
                'fields' => [
                    'employee_number' => ['label' => 'Personeelsnummer', 'required' => true],
                    'name'            => ['label' => 'Naam',             'required' => true],
                    'email'           => ['label' => 'E-mail'],
                    'phone'           => ['label' => 'Telefoon'],
                    'department_id'   => ['label' => 'Afdeling ID'],
                    'function'        => ['label' => 'Functie'],
                    'area'            => ['label' => 'Area / Gebied'],
                    'country'         => ['label' => 'Land / Country'],
                    'city'            => ['label' => 'Plaats / City'],
                    'region'          => ['label' => 'Regio / Region'],
                    'start_date'      => ['label' => 'Startdatum'],
                    'end_date'        => ['label' => 'Einddatum'],
                    'manager'         => ['label' => 'Manager'],
                    'cost_center'     => ['label' => 'Kostenplaats'],
                    'active'          => ['label' => 'Actief (1/0)'],
                    'external_id'     => ['label' => 'Externe ID'],
                    'source_system'   => ['label' => 'Bronsysteem'],
                ],
            ],
        ];
    }

    public static function get(string $entity): ?array
    {
        return self::all()[$entity] ?? null;
    }
}
