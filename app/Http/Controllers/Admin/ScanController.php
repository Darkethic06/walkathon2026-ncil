<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WalkathonRegistration;
use App\Support\Roles;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ScanController extends Controller
{
    /**
     * GET /admin/scan              -> camera scanner
     * GET /admin/scan?code=...     -> result for a scanned QR or a typed bib number
     */
    public function index(Request $request)
    {
        $code = trim((string) $request->query('code', ''));
        $registration = $code !== '' ? $this->find($code) : null;

        $registration?->load('attendedBy:id,first_name,last_name');

        return Inertia::render('Admin/Scan', [
            'code' => $code,
            'registration' => $registration?->toPanelArray($request->user()->hasAnyRole(Roles::ADMINS)),
        ]);
    }

    /**
     * Accepts the full QR text (it contains "Ref: <uuid>" and "BIB: <number>"),
     * a bare token, or a bib number typed by hand.
     */
    protected function find(string $code): ?WalkathonRegistration
    {
        if (preg_match('/[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}/i', $code, $m)) {
            return WalkathonRegistration::where('token', strtolower($m[0]))->first();
        }

        if (preg_match('/BIB:\s*(\d+)/i', $code, $m) || preg_match('/^\s*(\d{4,})\s*$/', $code, $m)) {
            return WalkathonRegistration::where('bib_number', $m[1])->first();
        }

        return null;
    }
}
