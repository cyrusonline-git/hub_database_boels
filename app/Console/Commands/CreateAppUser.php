<?php

namespace App\Console\Commands;

use App\Models\Application;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class CreateAppUser extends Command
{
    protected $signature = 'boels:create-app-user
                            {slug : Slug van de app, bv. "fleet" of "sales"}
                            {--prefix= : Tabel-prefix die deze app mag ALTER-en (default: {slug}_)}';

    protected $description = 'Genereert MySQL user + GRANT-statements voor een child-app van Boels CORE.';

    public function handle(): int
    {
        $slug = Str::lower($this->argument('slug'));
        $prefix = $this->option('prefix') ?: ($slug . '_');
        $coreDb = config('database.connections.mysql.database');

        // Username: zelfde stijl als Antagonist (deb2003831_xxx)
        // We nemen de host-prefix uit de bestaande DB-user (alles voor het eerste underscore + nummer)
        $coreUser = config('database.connections.mysql.username');
        $hostPrefix = Str::before($coreUser, '_'); // bv. "deb2003831"
        $username = $hostPrefix . '_' . $slug;
        if (strlen($username) > 32) {
            $username = substr($username, 0, 32);
            $this->warn("Username afgekapt naar 32 tekens (MySQL limiet): $username");
        }

        // Random sterk wachtwoord
        $password = Str::random(20);

        $this->info('');
        $this->info(str_repeat('=', 70));
        $this->info("Boels CORE — App user voor: {$slug}");
        $this->info(str_repeat('=', 70));
        $this->info('');

        $this->line('STAP 1 — Maak in Antagonist DirectAdmin een MySQL user aan:');
        $this->line('  → DirectAdmin → MySQL Management → "Create New Database User"');
        $this->line('');
        $this->line("    Database:        {$coreDb}  (gebruik bestaande, NIET nieuwe!)");
        $this->line("    Username:        {$username}");
        $this->line("    Password:        {$password}");
        $this->line('');
        $this->warn('  Bewaar dit wachtwoord goed — komt hierna niet meer terug.');
        $this->line('');

        $this->line('STAP 2 — Open in DirectAdmin "phpMyAdmin" en run deze GRANT-statements:');
        $this->line(str_repeat('-', 70));

        $sql = $this->buildGrantSql($username, $coreDb, $prefix);
        $this->line($sql);

        $this->line(str_repeat('-', 70));
        $this->line('');

        $this->line('STAP 3 — Plaats deze regels in de .env van de nieuwe app:');
        $this->line(str_repeat('-', 70));
        $this->line("DB_CONNECTION=mysql");
        $this->line("DB_HOST=localhost");
        $this->line("DB_PORT=3306");
        $this->line("DB_DATABASE={$coreDb}");
        $this->line("DB_USERNAME={$username}");
        $this->line("DB_PASSWORD={$password}");
        $this->line('');
        $this->line("BOELS_CORE_URL=" . config('app.url'));
        $this->line("BOELS_APP_PREFIX={$prefix}");
        $this->line(str_repeat('-', 70));
        $this->line('');

        // Application registry: registreer deze app als die nog niet bestaat
        $app = Application::where('slug', $slug)->first();
        if (! $app) {
            if ($this->confirm("Applicatie '{$slug}' staat nog niet in de registry. Toevoegen?", true)) {
                Application::create([
                    'name' => Str::title($slug) . ' App',
                    'slug' => $slug,
                    'description' => 'Child-app van Boels CORE',
                    'sort_order' => 100,
                    'active' => true,
                ]);
                $this->info("✓ Toegevoegd aan applications tabel.");
            }
        } else {
            $this->info("✓ App '{$slug}' staat al in de registry.");
        }

        $this->info('');
        $this->info(str_repeat('=', 70));
        $this->info('Klaar. Nu in DirectAdmin de user aanmaken en de SQL runnen.');
        $this->info(str_repeat('=', 70));

        return Command::SUCCESS;
    }

    private function buildGrantSql(string $username, string $db, string $prefix): string
    {
        $u = addslashes($username);
        $d = addslashes($db);

        // Lijst van core-tabellen die de app NIET mag ALTERen
        $coreTables = [
            'users', 'roles', 'permissions', 'role_permissions', 'user_roles',
            'applications', 'departments', 'employees',
            'customers', 'contacts', 'projects', 'machines', 'machine_groups',
            'machine_subgroups', 'work_orders', 'damages', 'customer_visits',
            'leads', 'opportunities', 'tasks', 'notes', 'documents', 'attachments',
            'custom_fields', 'custom_field_values', 'field_aliases',
            'import_profiles', 'import_jobs', 'import_job_rows',
            'audit_logs', 'app_table_ownership',
            'sessions', 'password_reset_tokens', 'jobs', 'failed_jobs',
            'cache', 'cache_locks', 'migrations',
        ];

        $lines = [];
        $lines[] = "-- ============================================================";
        $lines[] = "-- Boels CORE — App permissions voor user: {$username}";
        $lines[] = "-- ============================================================";
        $lines[] = "";
        $lines[] = "-- 1) Basis: lezen + rijen wijzigen op ALLE tabellen (data delen)";
        $lines[] = "GRANT SELECT, INSERT, UPDATE, DELETE ON `{$d}`.* TO '{$u}'@'%';";
        $lines[] = "";
        $lines[] = "-- 2) Schema-wijzigingen ALLEEN op tabellen met prefix '{$prefix}'";
        $lines[] = "GRANT ALTER, CREATE, DROP, INDEX, REFERENCES";
        $lines[] = "    ON `{$d}`.`{$prefix}%`";
        $lines[] = "    TO '{$u}'@'%';";
        $lines[] = "";
        $lines[] = "-- 3) BELANGRIJK: REVOKE ALTER op alle CORE tabellen (extra zekerheid)";

        foreach ($coreTables as $t) {
            $lines[] = "REVOKE ALTER, CREATE, DROP, INDEX, REFERENCES ON `{$d}`.`{$t}` FROM '{$u}'@'%';";
        }

        $lines[] = "";
        $lines[] = "FLUSH PRIVILEGES;";

        return implode("\n", $lines);
    }
}
