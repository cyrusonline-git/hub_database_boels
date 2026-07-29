<?php
/**
 * Boels CORE — Eenmalig account-vrijzet script.
 *
 * Zet het account van Wim vrij: nieuw tijdelijk wachtwoord,
 * status actief, en toont met welk e-mailadres je moet inloggen.
 *
 * Open: https://databasehub.sorai.nl/__reset-login.php?k=BOELS_RESET_2026
 * Ander account? Voeg &email=jouw@adres.nl toe.
 * Verwijdert zichzelf na succes.
 */

$secret = 'BOELS_RESET_2026';
if (($_GET['k'] ?? '') !== $secret) {
    http_response_code(403);
    exit('forbidden');
}

header('Content-Type: text/plain; charset=utf-8');

echo "Boels CORE — Account vrijzetten\n";
echo str_repeat('=', 50) . "\n\n";

// Boot Laravel
$candidates = [__DIR__ . '/../laravel_app', __DIR__ . '/..'];
$booted = false;
foreach ($candidates as $p) {
    if (file_exists($p . '/vendor/autoload.php')) {
        require $p . '/vendor/autoload.php';
        $app = require_once $p . '/bootstrap/app.php';
        $kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
        $kernel->bootstrap();
        $booted = true;
        break;
    }
}
if (! $booted) {
    exit("FOUT: Laravel niet gevonden (vendor/autoload.php ontbreekt).\n");
}

$email = strtolower(trim($_GET['email'] ?? 'w_groeneweg@hotmail.com'));
$tempPassword = 'Wim-Boels-2026!';

$user = \App\Models\User::withTrashed()->where('email', $email)->first();

if (! $user) {
    echo "FOUT: geen account gevonden met e-mailadres: $email\n\n";
    echo "Deze accounts bestaan wel:\n";
    foreach (\App\Models\User::withTrashed()->orderBy('id')->get() as $u) {
        $flags = [];
        if ($u->deleted_at) $flags[] = 'VERWIJDERD';
        if ($u->status !== \App\Models\User::STATUS_ACTIVE) $flags[] = strtoupper($u->status);
        if (! $u->active) $flags[] = 'INACTIEF';
        if ($u->is_super_admin) $flags[] = 'super-admin';
        echo sprintf("  #%-4d %-40s %s\n", $u->id, $u->email, $flags ? '(' . implode(', ', $flags) . ')' : '');
    }
    echo "\nProbeer opnieuw met: ?k=$secret&email=HET_JUISTE_ADRES\n";
    echo "(Script blijft staan zodat je het opnieuw kunt proberen.)\n";
    exit;
}

if ($user->deleted_at) {
    $user->restore();
    echo "✓ Account was verwijderd — hersteld.\n";
}

$user->forceFill([
    'password' => \Illuminate\Support\Facades\Hash::make($tempPassword),
    'status' => \App\Models\User::STATUS_ACTIVE,
    'active' => true,
    'activation_token' => null,
    'activation_token_expires_at' => null,
    'email_verified_at' => $user->email_verified_at ?? now(),
])->save();

echo "✓ Account vrijgezet!\n\n";
echo "Inloggen op: " . url('/login') . "\n";
echo "  E-mailadres: {$user->email}\n";
echo "  Tijdelijk wachtwoord: $tempPassword\n\n";
echo "BELANGRIJK: log direct in en wijzig daarna je wachtwoord\n";
echo "via 'Wachtwoord vergeten?' op de inlogpagina.\n";

@unlink(__FILE__);
echo "\nDit script heeft zichzelf verwijderd.\n";
