<?php

namespace Database\Seeders;

use App\Models\Application;
use App\Models\Permission;
use Illuminate\Database\Seeder;

class ApplicationSeeder extends Seeder
{
    public function run(): void
    {
        $apps = [
            ['name' => 'Boels CORE',       'slug' => 'core',       'icon' => 'bi-hdd-stack',     'url' => '/launcher',                  'sort' => 1,  'desc' => 'Centrale databank, gebruikers- en applicatiebeheer.'],
            ['name' => 'Project App',      'slug' => 'projects',   'icon' => 'bi-kanban',        'url' => 'https://projects.sorai.nl', 'sort' => 10, 'desc' => 'Projectbeheer en planning.'],
            ['name' => 'Fleet App',        'slug' => 'fleet',      'icon' => 'bi-truck',         'url' => 'https://fleet.sorai.nl',    'sort' => 20, 'desc' => 'Vlootbeheer en verhuur.'],
            ['name' => 'Schade App',       'slug' => 'damages',    'icon' => 'bi-exclamation-triangle', 'url' => 'https://schade.sorai.nl', 'sort' => 30, 'desc' => 'Schaderegistratie en afhandeling.'],
            ['name' => 'Sales App',        'slug' => 'sales',      'icon' => 'bi-graph-up-arrow','url' => 'https://sales.sorai.nl',    'sort' => 40, 'desc' => 'CRM, leads en opportunities.'],
            ['name' => 'AI Assistant',     'slug' => 'ai',         'icon' => 'bi-robot',         'url' => 'https://ai.sorai.nl',       'sort' => 50, 'desc' => 'AI-agents bovenop alle data.'],
            ['name' => 'Werkbon App',      'slug' => 'workorders', 'icon' => 'bi-clipboard-check','url' => 'https://werkbon.sorai.nl', 'sort' => 60, 'desc' => 'Digitale werkbonnen.'],
            ['name' => 'Monteurs App',     'slug' => 'mechanics',  'icon' => 'bi-tools',         'url' => 'https://monteurs.sorai.nl', 'sort' => 70, 'desc' => 'Mobiele app voor monteurs.'],
            ['name' => 'Rapportage App',   'slug' => 'reports',    'icon' => 'bi-bar-chart',     'url' => 'https://rapportage.sorai.nl', 'sort' => 80, 'desc' => 'Dashboards en rapportages.'],
        ];

        foreach ($apps as $a) {
            $app = Application::updateOrCreate(
                ['slug' => $a['slug']],
                [
                    'name' => $a['name'],
                    'icon' => $a['icon'],
                    'url'  => $a['url'],
                    'description' => $a['desc'],
                    'sort_order' => $a['sort'],
                    'active' => true,
                    'color' => config('boels.brand.color'),
                ]
            );

            // Default permissions per app
            foreach (['view', 'manage'] as $action) {
                Permission::updateOrCreate(
                    ['application_id' => $app->id, 'key' => $a['slug'].'.'.$action],
                    ['name' => $a['name'].' — '.ucfirst($action)]
                );
            }
        }
    }
}
