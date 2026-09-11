<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ThemeController extends Controller
{
    public function toggle(Request $request): RedirectResponse
    {
        $theme = $request->session()->get('theme') === 'theme-default-dark'
            ? 'theme-default'
            : 'theme-default-dark';

        $request->session()->put('theme', $theme);

        return back();
    }
}
