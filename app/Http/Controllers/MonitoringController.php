<?php

namespace App\Http\Controllers;

use App\Http\Requests\MonitoringRequest;
use App\Services\MonitoringService;
use Illuminate\View\View;

class MonitoringController extends Controller
{
    public function index(MonitoringRequest $request, MonitoringService $monitoring): View
    {
        return view('admin.monitoring', $monitoring->report($request->filled('edition') ? $request->integer('edition') : null));
    }
}
