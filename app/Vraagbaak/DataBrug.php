<?php

namespace App\Vraagbaak;

use Illuminate\Support\Facades\Cache;
use PDO;

/**
 * Read-only toegang tot de databases van de gekoppelde apps op dezelfde server.
 * SQLite wordt met mode=ro geopend (raakt de WAL van de live app niet aan);
 * Shell (MySQL) via de constanten uit config/secrets.php van die app.
 */
class DataBrug
{
    private array $verbindingen = [];

    public static function domainsDir(): string
    {
        return rtrim((string) (config('vraagbaak.domains_dir') ?: dirname(dirname(base_path()))), '/');
    }

    public static function pad(string $bron): ?string
    {
        $cfg = config("vraagbaak.bronnen.$bron");
        if (! $cfg) {
            return null;
        }

        return self::domainsDir().'/'.ltrim($cfg['pad'], '/');
    }

    public function beschikbaar(string $bron): bool
    {
        try {
            return $this->pdo($bron) !== null;
        } catch (\Throwable) {
            return false;
        }
    }

    public function pdo(string $bron): ?PDO
    {
        if (array_key_exists($bron, $this->verbindingen)) {
            return $this->verbindingen[$bron];
        }
        $cfg = config("vraagbaak.bronnen.$bron");
        $pad = self::pad($bron);
        $pdo = null;
        if ($cfg && $pad && is_readable($pad)) {
            if ($cfg['type'] === 'sqlite') {
                $pdo = new PDO('sqlite:file:'.$pad.'?mode=ro', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_TIMEOUT => 5]);
                $pdo->exec('PRAGMA query_only = 1');
            } elseif ($cfg['type'] === 'mysql_secrets') {
                $src = (string) file_get_contents($pad);
                $w = fn ($naam) => preg_match("/define\\(\\s*'".$naam."'\\s*,\\s*'([^']*)'\\s*\\)/", $src, $m) ? $m[1] : null;
                if ($w('DB_NAME') && $w('DB_USER')) {
                    $pdo = new PDO('mysql:host='.($w('DB_HOST') ?: 'localhost').';dbname='.$w('DB_NAME').';charset=utf8mb4', $w('DB_USER'), (string) $w('DB_PASS'), [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_TIMEOUT => 5]);
                }
            }
        }

        return $this->verbindingen[$bron] = $pdo;
    }

    /** SELECT uitvoeren (alleen lezen), met korte cache. */
    public function select(string $bron, string $sql, array $params = []): array
    {
        if (! preg_match('/^\s*(select|with)\b/i', $sql)) {
            throw new \RuntimeException('Alleen SELECT is toegestaan.');
        }
        $sleutel = 'vraagbaak:'.$bron.':'.md5($sql.json_encode($params));
        $ttl = (int) config('vraagbaak.cache_seconden', 60);

        return Cache::remember($sleutel, $ttl, function () use ($bron, $sql, $params) {
            $pdo = $this->pdo($bron);
            if (! $pdo) {
                throw new BronNietBeschikbaar($bron);
            }
            $st = $pdo->prepare($sql);
            $st->execute($params);
            $rijen = $st->fetchAll();

            return array_slice($rijen, 0, (int) config('vraagbaak.max_rijen', 200));
        });
    }

    public function waarde(string $bron, string $sql, array $params = []): mixed
    {
        $r = $this->select($bron, $sql, $params);

        return $r ? array_values($r[0])[0] : null;
    }

    /** Diagnose per bron: pad, gevonden, leesbaar, tabellen. */
    public function diagnose(): array
    {
        $uit = [];
        foreach (array_keys((array) config('vraagbaak.bronnen')) as $bron) {
            $pad = self::pad($bron);
            $rij = ['bron' => $bron, 'pad' => $pad, 'gevonden' => $pad && file_exists($pad), 'leesbaar' => false, 'tabellen' => null, 'fout' => null];
            try {
                $pdo = $this->pdo($bron);
                if ($pdo) {
                    $rij['leesbaar'] = true;
                    $cfg = config("vraagbaak.bronnen.$bron");
                    $rij['tabellen'] = $cfg['type'] === 'sqlite'
                        ? (int) $pdo->query("select count(*) from sqlite_master where type='table'")->fetchColumn()
                        : (int) $pdo->query('select count(*) from information_schema.tables where table_schema = database()')->fetchColumn();
                }
            } catch (\Throwable $e) {
                $rij['fout'] = $e->getMessage();
            }
            $uit[] = $rij;
        }

        return $uit;
    }
}
