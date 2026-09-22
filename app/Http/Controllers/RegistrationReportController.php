<?php

namespace App\Http\Controllers;

use App\Services\RegistrationReportService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class RegistrationReportController extends Controller
{
    public function index(): View
    {
        return view('admin.reports.registrations');
    }

    public function generate(Request $request, RegistrationReportService $service): View|Response
    {
        $validated = $request->validate([
            'status' => ['required', 'integer', 'in:3,5'],
            'action' => ['required', 'in:preview,download'],
        ]);
        $report = $service->generate((int) $validated['status']);

        if ($validated['action'] === 'download') {
            return response($report['markdown'], 200, [
                'Content-Type' => 'text/markdown; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="'.$report['filename'].'"',
                'X-Content-Type-Options' => 'nosniff',
            ]);
        }

        return view('admin.reports.registrations', compact('report'));
    }
}
