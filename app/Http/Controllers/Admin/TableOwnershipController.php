<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppTableOwnership;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TableOwnershipController extends Controller
{
    public function index()
    {
        // Alle tabellen in de DB
        $allTables = DB::select('SHOW TABLES');
        $key = 'Tables_in_' . config('database.connections.mysql.database');
        $tableNames = array_map(fn ($r) => $r->$key, $allTables);

        // Eigendom-records
        $owned = AppTableOwnership::all()->keyBy('table_name');

        // Per tabel: rij data
        $rows = collect($tableNames)->map(function ($name) use ($owned) {
            $own = $owned->get($name);
            return [
                'table_name' => $name,
                'owner_slug' => $own?->owner_slug ?? '— onbekend —',
                'owner_name' => $own?->owner_name ?? '',
                'locked' => $own?->locked ?? false,
                'notes' => $own?->notes ?? '',
            ];
        })->sortBy('owner_slug')->values();

        return view('admin.table_ownership.index', ['rows' => $rows]);
    }
}
