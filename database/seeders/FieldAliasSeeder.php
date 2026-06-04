<?php

namespace Database\Seeders;

use App\Models\FieldAlias;
use Illuminate\Database\Seeder;

class FieldAliasSeeder extends Seeder
{
    public function run(): void
    {
        $aliases = [
            // Customer
            ['customer', 'Klantnaam',       'customer_name'],
            ['customer', 'Debiteurnaam',    'customer_name'],
            ['customer', 'Account Name',    'customer_name'],
            ['customer', 'Naam',            'customer_name'],
            ['customer', 'Klantnummer',     'customer_number'],
            ['customer', 'Debiteurnummer',  'customer_number'],
            ['customer', 'Account ID',      'customer_number'],
            ['customer', 'KvK',             'kvk_number'],
            ['customer', 'KvK nummer',      'kvk_number'],
            ['customer', 'BTW',             'vat_number'],
            ['customer', 'E-mail',          'email'],
            ['customer', 'Email',           'email'],
            ['customer', 'Telefoon',        'phone'],
            ['customer', 'Tel',             'phone'],

            // Machine
            ['machine', 'Machinenummer',    'machine_number'],
            ['machine', 'Materieelnummer',  'machine_number'],
            ['machine', 'Omschrijving',     'description'],
            ['machine', 'Merk',             'brand'],
            ['machine', 'Type',             'model'],
            ['machine', 'Serienummer',      'serial_number'],

            // Project
            ['project', 'Projectnummer',    'project_number'],
            ['project', 'Projectnaam',      'project_name'],
            ['project', 'Klant',            'customer_id'],
            ['project', 'Klantnummer',      'customer_id'],

            // Employee
            ['employee', 'Personeelsnummer','employee_number'],
            ['employee', 'Medewerker',      'name'],
            ['employee', 'Afdeling',        'department_id'],
        ];

        foreach ($aliases as [$entity, $alias, $field]) {
            FieldAlias::updateOrCreate(
                ['entity' => $entity, 'alias' => $alias],
                ['field' => $field]
            );
        }
    }
}
