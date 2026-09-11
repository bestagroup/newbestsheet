<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Services\DashboardMetricsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;

class IndexController extends Controller
{
    public function __construct(
        private readonly DashboardMetricsService $dashboardMetrics,
    ) {}

    public function index()
    {
        if (Auth::user()->hasRole('investee_representative') || Auth::user()->level === 'applicant') {
            return Redirect::route('profile');
        }

        $thispage = ['list' => 'داشبورد مدیریتی'];
        $metrics = $this->dashboardMetrics->build();

        return view('dashboard', ['thispage' => $thispage, ...$metrics]);
    }

    public function getcities(int $stateId): JsonResponse
    {
        $cities = City::query()
            ->where('state_id', $stateId)
            ->select('id', 'title')
            ->orderBy('title')
            ->get();

        return response()->json($cities);
    }
}
