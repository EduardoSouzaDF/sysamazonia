<?php

namespace App\Http\Controllers;

use App\Services\DashboardStatisticsService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(DashboardStatisticsService $statistics): View
    {
        return view('dashboard', [
            'statistics' => $statistics->statistics(),
            'mapPaths' => json_decode(file_get_contents(resource_path('maps/brazil-states.json')), true, 512, JSON_THROW_ON_ERROR),
        ]);
    }
}
