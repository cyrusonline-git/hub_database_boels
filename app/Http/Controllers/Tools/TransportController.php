<?php

namespace App\Http\Controllers\Tools;

use App\Http\Controllers\Controller;
use App\Services\TransportTarief;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Backend van de transportcalculator (resources/views/tools/transport.blade.php).
 *
 * De tarieven staan server-side in de tabel `transport_settings` (één JSON-blob-row),
 * zodat iedereen dezelfde actuele cijfers ziet en de transportplanner ze via de
 * interne API kan opvragen. Alleen planner/admin/superadmin mogen de tarieven
 * opslaan (de "beheerkant"); iedereen die de tool mag openen kan berekenen.
 */
class TransportController extends Controller
{
    /** Mag deze gebruiker de tarieven bijstellen? (de beheerkant) */
    public static function magBeheren($user): bool
    {
        if (! $user) {
            return false;
        }
        return $user->is_super_admin
            || $user->hasRole(['super-admin', 'administrator', 'admin', 'planner']);
    }

    /** De tool-pagina met de server-dataset ingespoten. */
    public function page(Request $request)
    {
        $row = TransportTarief::settingsRow();
        return view('tools.transport', [
            'payloadJson' => $row->payload,              // ruwe JSON, in de view met {!! !!} in een <script id=app-state>
            'payloadVersion' => (int) $row->version,
            'canEdit' => self::magBeheren($request->user()),
        ]);
    }

    /** GET: huidige tarieven + version ophalen (voor de tool-JS). */
    public function show(Request $request)
    {
        $row = TransportTarief::settingsRow();
        return response()->json([
            'ok' => true,
            'version' => (int) $row->version,
            'canEdit' => self::magBeheren($request->user()),
            'payload' => json_decode($row->payload, true),
        ]);
    }

    /** POST: tarieven opslaan (alleen beheerders). Optimistic lock op version. */
    public function update(Request $request)
    {
        $user = $request->user();
        if (! self::magBeheren($user)) {
            return response()->json(['ok' => false, 'error' => 'Je mag de tarieven niet aanpassen.'], 403);
        }
        $data = (array) $request->json()->all();
        $payload = $data['payload'] ?? null;
        if (! is_array($payload) || ! isset($payload['jaren'], $payload['middelen'])) {
            return response()->json(['ok' => false, 'error' => 'Ongeldige tariefgegevens.'], 422);
        }
        $verwachteVersie = (int) ($data['version'] ?? 0);

        $row = TransportTarief::settingsRow();
        if ($verwachteVersie && $verwachteVersie !== (int) $row->version) {
            return response()->json([
                'ok' => false,
                'conflict' => true,
                'error' => 'Iemand anders heeft de tarieven net aangepast. Herlaad de pagina en probeer opnieuw.',
                'version' => (int) $row->version,
            ], 409);
        }

        $nieuweVersie = (int) $row->version + 1;
        DB::table('transport_settings')->where('id', $row->id)->update([
            'payload' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'version' => $nieuweVersie,
            'updated_by' => $user?->id,
            'updated_at' => now(),
        ]);

        return response()->json(['ok' => true, 'version' => $nieuweVersie]);
    }
}
