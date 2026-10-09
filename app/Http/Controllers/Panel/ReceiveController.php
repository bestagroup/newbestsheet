<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Finance;
use App\Models\MenuPanel;
use App\Models\SubmenuPanel;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class ReceiveController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = Finance::query()
                ->leftJoin('projects', 'projects.id', '=', 'finances.project_id')
                ->select([
                    'finances.id',
                    'finances.serial',
                    'projects.title as project_title',
                    'finances.amount',
                    'finances.date',
                    'finances.description',
                ]);

            return DataTables::of($data)
                ->editColumn('amount', static fn ($row): string => \App\Support\Monetary::format($row->amount))
                ->make(true);
        }

        $submenupanels = SubmenuPanel::select('id', 'priority', 'title', 'label', 'menu_id', 'slug', 'status', 'class', 'controller')->get();
        $menupanels = MenuPanel::select('id', 'priority', 'title', 'label', 'slug', 'status', 'class', 'controller')->get();

        $thispage = [
            'title' => 'گزارش دریافت‌های مالی',
            'list' => 'لیست دریافت‌ها و واریزهای ثبت‌شده',
        ];

        return view('panel.receivemanage', compact('thispage', 'submenupanels', 'menupanels'));
    }
}
