<?php
/**
 * Boels CORE — Eenmalig: gebruikers van de inhuur-app (boels.sorai.nl)
 * overnemen ZONDER activatiemails te sturen.
 *
 * - Leest {inhuur}/core-users.php (e-mail + rollen, read-only).
 * - Bestaande CORE-gebruikers: alleen de Inhuur-rollen erbij koppelen.
 * - Nog geen CORE-login maar wél in de medewerkerslijst: account aanmaken
 *   met status "wacht op activatie" — GEEN mail. De mails stuur je later
 *   in één keer via Beheer → Gebruikers → "Activatiemails versturen".
 * - Iedereen blijft intussen gewoon op de oude manier in de app inloggen.
 *
 * Sleutel = DEPLOY_SECRET uit de server-.env. Verwijdert zichzelf na succes.
 */

$envFile = null;
foreach ([__DIR__ . '/../laravel_app/.env', __DIR__ . '/../.env'] as $p) {
    if (file_exists($p)) { $envFile = realpath($p); break; }
}
if (! $envFile) exit('env niet gevonden');
$contents = file_get_contents($envFile);
if (! preg_match('/^DEPLOY_SECRET=(.+)$/m', $contents, $m)
    || ! hash_equals(trim($m[1]), (string) ($_GET['k'] ?? ''))) {
    http_response_code(403);
    exit('forbidden');
}

header('Content-Type: text/plain; charset=utf-8');
@set_time_limit(120);
echo "Boels CORE — inhuur-gebruikers overnemen (zonder mails)\n" . str_repeat('=', 60) . "\n\n";

// Laravel bootstrappen
foreach ([__DIR__ . '/../laravel_app', __DIR__ . '/..'] as $p) {
    if (file_exists($p . '/vendor/autoload.php')) {
        require $p . '/vendor/autoload.php';
        $app = require_once $p . '/bootstrap/app.php';
        $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        break;
    }
}
if (! function_exists('app')) exit("FOUT: Laravel niet gevonden.\n");

$application = \App\Models\Application::where('slug', 'inhuur')->first();
if (! $application) exit("FOUT: applicatie 'inhuur' niet gevonden — draai eerst de migraties.\n");

// Gebruikerslijst ophalen bij de app (zelfde server, IP-check daar)
$ch = curl_init('https://boels.sorai.nl/core-users.php');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_TIMEOUT => 20,
    CURLOPT_HTTPHEADER => ['Accept: application/json'],
]);
$body = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
if ($code !== 200 || ! $body) exit("FOUT: core-users.php gaf HTTP $code.\n");
$data = json_decode($body, true);
if (! is_array($data) || ! isset($data['users'])) exit("FOUT: onverwacht antwoord van core-users.php.\n");

$appRoles = \App\Models\Role::where('application_id', $application->id)->get()->keyBy('slug');
$gekoppeld = 0;
$aangemaakt = 0;
$onbekend = [];

foreach ($data['users'] as $u) {
    $email = mb_strtolower(trim((string) ($u['email'] ?? '')));
    if ($email === '') continue;

    $user = \App\Models\User::where('email', $email)->first();

    if (! $user) {
        $employee = \App\Models\Employee::whereRaw('lower(email) = ?', [$email])->first();
        if (! $employee) {
            $onbekend[] = $email;
            continue;
        }
        $herstel = \App\Models\User::onlyTrashed()->where('email', $email)->first();
        $velden = [
            'name' => $employee->name,
            'email' => $employee->email,
            'password' => \Illuminate\Support\Facades\Hash::make(\Illuminate\Support\Str::random(40)),
            'employee_id' => $employee->id,
            'is_super_admin' => false,
            'active' => false,
            'status' => \App\Models\User::STATUS_PENDING,
            'allowed_areas' => $employee->area ? [$employee->area] : null,
            'allowed_depots' => $employee->depot ? [$employee->depot] : null,
            'allowed_countries' => $employee->country ? [$employee->country] : null,
            'activation_token' => \Illuminate\Support\Str::random(40),
            'activation_token_expires_at' => now()->addDays(7),
        ];
        if ($herstel) {
            $herstel->restore();
            $herstel->update($velden);
            $user = $herstel;
        } else {
            $user = \App\Models\User::create($velden);
        }
        // BEWUST GEEN activatiemail — die stuur je later in bulk.
        $aangemaakt++;
    }

    $roleIds = collect((array) ($u['roles'] ?? []))
        ->map(fn ($slug) => $appRoles[\Illuminate\Support\Str::slug((string) $slug)]->id ?? null)
        ->filter()->all();
    if ($roleIds) {
        $user->roles()->syncWithoutDetaching($roleIds);
        $gekoppeld++;
    }
}

echo "Rollen gekoppeld op e-mail: $gekoppeld gebruiker(s)\n";
echo "Nieuwe CORE-logins aangemaakt (wachtend, GEEN mail): $aangemaakt\n";
if ($onbekend) {
    echo "\nNiet in de medewerkerslijst (geen login aangemaakt):\n";
    foreach ($onbekend as $e) echo "  - $e\n";
    echo "Voeg ze toe aan de medewerkerslijst of maak handmatig een login.\n";
}
echo "\nDe activatiemails stuur je wanneer jij wilt, in één keer:\n";
echo "Beheer → Gebruikers → knop \"Activatiemails versturen\".\n";

@unlink(__FILE__);
echo "\n✓ Klaar. Dit script heeft zichzelf verwijderd.\n";
