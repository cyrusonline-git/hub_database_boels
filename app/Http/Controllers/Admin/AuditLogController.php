<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $query = AuditLog::with('user')->orderByDesc('created_at');

        if ($request->filled('event')) {
            $query->where('event', $request->event);
        }
        if ($request->filled('type')) {
            $query->where('auditable_type', $request->type);
        }
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        $logs = $query->paginate(50);
        return view('admin.audit_log.index', compact('logs'));
    }
}
