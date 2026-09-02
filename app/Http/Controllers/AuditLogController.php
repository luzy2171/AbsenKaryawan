<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AuditLog;
use Carbon\Carbon;

class AuditLogController extends Controller
{
    /**
     * Menampilkan halaman audit logs
     */
    public function index(Request $request)
    {
        $query = AuditLog::with('user')->orderBy('created_at', 'desc');

        // Filter by module
        if ($request->filled('module') && $request->module !== 'all') {
            $query->byModule($request->module);
        }

        // Filter by action
        if ($request->filled('action') && $request->action !== 'all') {
            $query->byAction($request->action);
        }

        // Filter by user
        if ($request->filled('user_id') && $request->user_id !== 'all') {
            $query->byUser($request->user_id);
        }

        // Filter by date range
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->inDateRange($request->start_date, $request->end_date);
        }

        // Pagination
        $logs = $query->paginate(50);

        // Get unique modules and actions for filter
        $modules = AuditLog::select('module')->distinct()->pluck('module');
        $actions = AuditLog::select('action')->distinct()->pluck('action');
        $users = \App\Models\User::select('id', 'name', 'username')->get();

        return view('admin.audit-logs.index', compact('logs', 'modules', 'actions', 'users'));
    }

    /**
     * Menampilkan detail audit log
     */
    public function show($id)
    {
        $log = AuditLog::with('user')->findOrFail($id);
        return view('admin.audit-logs.show', compact('log'));
    }

    /**
     * Export audit logs ke Excel
     */
    public function export(Request $request)
    {
        $query = AuditLog::with('user')->orderBy('created_at', 'desc');

        if ($request->filled('module') && $request->module !== 'all') {
            $query->byModule($request->module);
        }
        if ($request->filled('action') && $request->action !== 'all') {
            $query->byAction($request->action);
        }
        if ($request->filled('user_id') && $request->user_id !== 'all') {
            $query->byUser($request->user_id);
        }
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->inDateRange($request->start_date, $request->end_date);
        }

        $logs = $query->get();

        $filename = "Audit_Log_" . date('d-m-Y_His') . ".xls";

        $html = '
        <html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
        <head>
            <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
            <style>
                body { font-family: Calibri, sans-serif; }
                .title { font-size: 16pt; font-weight: bold; color: #1e7e34; }
                .header-table { font-weight: bold; background-color: #f2f2f2; text-align: center; border: 0.5pt solid #cccccc; }
                .cell-center { text-align: center; border: 0.5pt solid #cccccc; }
                .cell-left { text-align: left; border: 0.5pt solid #cccccc; }
            </style>
        </head>
        <body>
            <table>
                <tr>
                    <td colspan="7" class="title">AUDIT LOG - SISTEM ABSENSI</td>
                </tr>
                <tr><td colspan="7"></td></tr>
                <tr class="header-table">
                    <th width="50">No</th>
                    <th width="150">Waktu</th>
                    <th width="150">User</th>
                    <th width="100">Module</th>
                    <th width="100">Action</th>
                    <th width="300">Deskripsi</th>
                    <th width="150">IP Address</th>
                </tr>';

        $no = 1;
        foreach ($logs as $log) {
            $html .= '
            <tr>
                <td class="cell-center">' . $no++ . '</td>
                <td class="cell-center">' . $log->created_at->format('d/m/Y H:i:s') . '</td>
                <td class="cell-left">' . ($log->user ? $log->user->name : 'System') . '</td>
                <td class="cell-center">' . strtoupper($log->module) . '</td>
                <td class="cell-center">' . strtoupper($log->action) . '</td>
                <td class="cell-left">' . $log->description . '</td>
                <td class="cell-center">' . $log->ip_address . '</td>
            </tr>';
        }

        $html .= '
            </table>
        </body>
        </html>';

        return response($html, 200, [
            'Content-Type'        => 'application/vnd.ms-excel; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control'       => 'max-age=0'
        ]);
    }
}
