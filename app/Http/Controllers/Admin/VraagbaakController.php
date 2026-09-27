<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Vraagbaak\DataBrug;
use App\Vraagbaak\Logboek;
use Illuminate\Http\Request;

/** Beheer: onbegrepen vragen (om synoniemen bij te leren), gebruik, en status van de bronnen. */
class VraagbaakController extends Controller
{
    public function index(Request $request)
    {
        $status = (string) $request->query('status', '');
        $q = Logboek::with('user')->orderByDesc('id');
        if ($status !== '') {
            $q->where('status', $status);
        }

        return view('admin.vraagbaak.logboek', [
            'log' => $q->paginate(50)->withQueryString(),
            'status' => $status,
            'onbegrepen' => Logboek::whereIn('status', ['onbekend', 'keuze'])->where('created_at', '>=', now()->subDays(90))->selectRaw('vraag, count(*) as n')->groupBy('vraag')->orderByDesc('n')->limit(30)->get(),
            'populair' => Logboek::where('status', 'ok')->where('created_at', '>=', now()->subDays(90))->selectRaw('herkend_als, count(*) as n')->groupBy('herkend_als')->orderByDesc('n')->limit(20)->get(),
            'bronnen' => app(DataBrug::class)->diagnose(),
            'totaal' => Logboek::count(),
        ]);
    }
}
