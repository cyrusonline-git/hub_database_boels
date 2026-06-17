<?php

namespace Database\Seeders;

use App\Models\FieldAlias;
use Illuminate\Database\Seeder;

class EmployeeFieldAliasSeeder extends Seeder
{
    public function run(): void
    {
        $aliases = [
            // Personeelsnummer / Employee Number
            ['employee', 'Personeelsnummer', 'employee_number'],
            ['employee', 'Employee Number',  'employee_number'],
            ['employee', 'Employee ID',      'employee_number'],
            ['employee', 'Medewerkernummer', 'employee_number'],
            ['employee', 'Nummer',           'employee_number'],
            ['employee', 'ID',               'employee_number'],

            // Naam
            ['employee', 'Naam',             'name'],
            ['employee', 'Name',             'name'],
            ['employee', 'Full Name',        'name'],
            ['employee', 'Volledige Naam',   'name'],
            ['employee', 'Medewerker',       'name'],
            ['employee', 'Employee',         'name'],
            ['employee', 'Employee Name',    'name'],

            // Email
            ['employee', 'E-mail',           'email'],
            ['employee', 'Email',            'email'],
            ['employee', 'E-mailadres',      'email'],
            ['employee', 'Email Address',    'email'],
            ['employee', 'Mail',             'email'],

            // Telefoon
            ['employee', 'Telefoon',         'phone'],
            ['employee', 'Phone',            'phone'],
            ['employee', 'Tel',              'phone'],
            ['employee', 'Tel.',             'phone'],
            ['employee', 'Mobiel',           'phone'],
            ['employee', 'Mobile',           'phone'],

            // Afdeling
            ['employee', 'Afdeling',         'department_id'],
            ['employee', 'Department',       'department_id'],
            ['employee', 'Dept',             'department_id'],

            // Functie
            ['employee', 'Functie',          'function'],
            ['employee', 'Function',         'function'],
            ['employee', 'Job Title',        'function'],
            ['employee', 'Title',            'function'],
            ['employee', 'Position',         'function'],
            ['employee', 'Rol',              'function'],

            // Area / Gebied
            ['employee', 'Area',             'area'],
            ['employee', 'Gebied',           'area'],
            ['employee', 'Werkgebied',       'area'],

            // Land / Country
            ['employee', 'Land',             'country'],
            ['employee', 'Country',          'country'],
            ['employee', 'Country Code',     'country'],

            // City / Plaats
            ['employee', 'Plaats',           'city'],
            ['employee', 'Stad',             'city'],
            ['employee', 'City',             'city'],
            ['employee', 'Woonplaats',       'city'],

            // Region
            ['employee', 'Regio',            'region'],
            ['employee', 'Region',           'region'],
            ['employee', 'Provincie',        'region'],

            // Datums
            ['employee', 'Startdatum',       'start_date'],
            ['employee', 'Start Date',       'start_date'],
            ['employee', 'In dienst',        'start_date'],
            ['employee', 'Hire Date',        'start_date'],
            ['employee', 'Einddatum',        'end_date'],
            ['employee', 'End Date',         'end_date'],
            ['employee', 'Uit dienst',       'end_date'],

            // Manager
            ['employee', 'Manager',          'manager'],
            ['employee', 'Leidinggevende',   'manager'],
            ['employee', 'Supervisor',       'manager'],

            // Kostenplaats
            ['employee', 'Kostenplaats',     'cost_center'],
            ['employee', 'Cost Center',      'cost_center'],
            ['employee', 'Cost Centre',      'cost_center'],

            // Depot / Vestiging
            ['employee', 'Depot',            'depot'],
            ['employee', 'Vestiging',        'depot'],
            ['employee', 'Branch',           'depot'],
            ['employee', 'Locatie',          'depot'],
            ['employee', 'Location',         'depot'],

            // Actief
            ['employee', 'Actief',           'active'],
            ['employee', 'Active',           'active'],
            ['employee', 'Status',           'active'],
        ];

        foreach ($aliases as [$entity, $alias, $field]) {
            FieldAlias::updateOrCreate(
                ['entity' => $entity, 'alias' => $alias],
                ['field' => $field]
            );
        }
    }
}
