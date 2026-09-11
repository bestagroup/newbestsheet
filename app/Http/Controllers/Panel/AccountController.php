<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;

class AccountController extends Controller
{
    public function index()
    {
        $thispage = [
            'title' => 'مدیریت پرداخت ها',
            'list' => 'لیست پرداخت ها',
            'add' => 'افزودن پرداخت ها',
            'create' => 'ایجاد پرداخت ها',
            'enter' => 'ورود پرداخت ها',
            'edit' => 'ویرایش پرداخت ها',
            'delete' => 'حذف پرداخت ها',
        ];

        return view('panel.profile')->with(compact('thispage'));
    }
}
