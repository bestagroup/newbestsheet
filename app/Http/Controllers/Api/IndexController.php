<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class IndexController extends Controller
{
    public function getdata()
    {
        $finances = DB::table('finances as f')
            ->leftJoin('projects as p', 'f.project_id', '=', 'p.id')
            ->select('f.amount', 'f.date', 'p.title', 'p.logo')
            ->where('f.amount', '>', 0)
            ->orderBy('f.date', 'DESC')
            ->get();

        $projects = DB::table('finances as f')
            ->leftjoin('projects as p', 'f.project_id', '=', 'p.id')
            ->select('p.CEO', 'p.title', DB::raw('SUM(f.amount) as total_amount'), 'p.logo')
            ->groupBy('p.title', 'p.logo', 'p.CEO')
            ->having('total_amount', '>', 0)
            ->orderBy('total_amount', 'desc')
            ->get();

        return response()->json(
            ['isSuccess' => true,
                'message' => 'عملیات با موفقیت انجام شد.',
                'errors' => false,
                'status_code' => 200,
                'finances' => $finances,
                'projects' => $projects,
            ], 200);
    }
}
