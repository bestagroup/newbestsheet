<?php

namespace App\Http\Controllers\Panel;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LeveluserController extends TypeuserController
{
    public function index(Request $request): JsonResponse|View
    {
        if ($request->ajax()) {
            return parent::index($request);
        }

        return view('panel.leveluser', $this->viewData('مدیریت سطح کاربران'));
    }
}
