<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\MenuPanel;
use App\Models\Permission;
use App\Models\SubmenuPanel;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;

class SubmenupanelController extends Controller
{
    public function index(Request $request)
    {

        $submenupanels = SubmenuPanel::select('id', 'priority', 'title', 'label', 'menu_id', 'slug', 'status', 'class', 'controller')->get();
        $menupanels = MenuPanel::select('id', 'priority', 'title', 'label', 'slug', 'status', 'class', 'controller')->get();
        $thispage = [
            'title' => 'مدیریت زیرمنو داشبورد',
            'list' => 'لیست زیرمنو داشبورد',
            'add' => 'افزودن زیرمنو داشبورد',
            'create' => 'ایجاد زیرمنو داشبورد',
            'enter' => 'ورود زیرمنو داشبورد',
            'edit' => 'ویرایش زیرمنو داشبورد',
            'delete' => 'حذف زیرمنو داشبورد',
        ];

        if ($request->ajax()) {
            $data = SubmenuPanel::join('menu_panels', 'menu_panels.id', '=', 'submenu_panels.menu_id')
                ->select('menu_panels.label as menu', 'submenu_panels.id', 'submenu_panels.title', 'submenu_panels.label', 'submenu_panels.slug', 'submenu_panels.status', 'submenu_panels.class', 'submenu_panels.controller');

            return DataTables::of($data)
                ->addColumn('id', function ($data) {
                    return $data->id;
                })
                ->addColumn('title', function ($data) {
                    return $data->title;
                })
                ->addColumn('label', function ($data) {
                    return $data->label;
                })
                ->addColumn('slug', function ($data) {
                    return $data->slug;
                })
                ->addColumn('menu', function ($data) {
                    return $data->menu;
                })
                ->addColumn('class', function ($data) {
                    return $data->class;
                })
                ->addColumn('controller', function ($data) {
                    return $data->controller;
                })
                ->addColumn('status', function ($data) {

                    if ($data->status == '0') {
                        $text = 'عدم نمایش';
                        $cls = 'bg-label-danger';
                    } elseif ($data->status == '4') {
                        $text = 'در حال نمایش';
                        $cls = 'bg-label-success';
                    } else {
                        $text = 'نامشخص';
                        $cls = 'bg-label-secondary';
                    }

                    return '<span class="badge '.$cls.' px-3 py-2 rounded-pill text-white"
                                style="font-size: 0.85rem; letter-spacing: 0;">
                                '.$text.'
                            </span>';
                })
                ->editColumn('action', function ($data) {
                    $base = 'btn btn-sm btn-icon rounded-pill waves-effect mx-1';

                    $actionBtn = '';
                    if (auth()->user()->can('can-access', ['submenupanel', 'edit'])) {
                        $actionBtn .= '<button type="button" class="'.$base.' btn btn-sm btn-outline-primary edit-btn" data-id="'.$data->id.'" data-url="'.route('submenupanel.edit', $data->id).'"><i class="mdi mdi-pencil-outline"></i></button>';
                    }
                    if (auth()->user()->can('can-access', ['submenupanel', 'delete'])) {
                        $actionBtn .= '<button type="button" class="'.$base.' btn btn-sm btn-icon btn-outline-danger mx-1 delete-btn" data-id="'.$data->id.'"><i class="mdi mdi-delete-outline"></i></button>';
                    }

                    return $actionBtn;
                })
                ->rawColumns(['action', 'status'])
                ->make(true);
        }

        return view('panel.submenupanel')->with(compact(['thispage', 'submenupanels', 'menupanels']));
    }

    public function store(Request $request)
    {
        try {
            $priority = SubmenuPanel::select('id')->orderBy('id', 'DESC')->first();
            $submenu_panel = new SubmenuPanel;
            $submenu_panel->title = $request->input('title');
            $submenu_panel->label = $request->input('label');
            $submenu_panel->menu_id = $request->input('menupanel_id');
            $submenu_panel->class = $request->input('class');
            $submenu_panel->controller = $request->input('controller');
            $submenu_panel->user_id = Auth::user()->id;
            $submenu_panel->priority = $priority->id + 1;
            $submenu_panel->status = 4;
            $result1 = $submenu_panel->save();

            $permission = new Permission;
            $permission->title = $request->input('title');
            $permission->label = $request->input('label');
            $permission->submenu_panel_id = $submenu_panel->id;
            $permission->menu_panel_id = $request->input('menupanel_id');
            $permission->user_id = Auth::user()->id;

            $result2 = $permission->save();

            if ($result1 == true && $result2 == true) {
                $success = true;
                $flag = 'success';
                $subject = 'عملیات موفق';
                $message = 'اطلاعات زیرمنو با موفقیت ثبت شد';
            } elseif ($result1 == true && $result2 != true) {
                $success = false;
                $flag = 'error';
                $subject = 'عملیات نا موفق';
                $message = 'اطلاعات دسترسی ثبت نشد، لطفا مجددا تلاش نمایید';
            } elseif ($result1 != true && $result2 != true) {
                $success = false;
                $flag = 'error';
                $subject = 'عملیات نا موفق';
                $message = 'اطلاعات زیرمنو ثبت نشد، لطفا مجددا تلاش نمایید';
            }

        } catch (Exception $e) {

            $success = false;
            $flag = 'error';
            $subject = 'خطا در ارتباط با سرور';
            // $message = strchr($e);
            $message = 'اطلاعات زیرمنو ثبت نشد،لطفا بعدا مجدد تلاش نمایید ';
        }

        return response()->json(['success' => $success, 'subject' => $subject, 'flag' => $flag, 'message' => $message]);

        //        return redirect(route('menudashboards.index'));

    }

    public function edit($id)
    {
        $menupanels = MenuPanel::all();
        $submenupanel = SubmenuPanel::whereId($id)->first();

        return view('panel.partials.edit-form-submenupanel', compact('menupanels', 'submenupanel'));
    }

    public function update(Request $request, $id)
    {

        $submenu_panel = SubmenuPanel::findOrfail($id);
        $permission_slug = $submenu_panel->slug;
        $submenu_panel->title = $request->input('title');
        $submenu_panel->label = $request->input('label');
        $submenu_panel->menu_id = $request->input('menupanel_id');
        $submenu_panel->class = $request->input('class');
        $submenu_panel->controller = $request->input('controller');
        $submenu_panel->user_id = Auth::user()->id;
        $submenu_panel->status = $request->input('status');
        //        if ($request->input('userlevel')){
        //            $menu->userlevel        = json_encode(explode("،", $request->input('userlevel')));
        //        }
        //        $menu->priority         = $request->input('priority');

        $result = $submenu_panel->update();
        $newsubmenu_panel = SubmenuPanel::findOrfail($id);
        $permision = Permission::whereSlug($permission_slug)->first();
        $permision->title = $newsubmenu_panel->title;
        $permision->slug = $newsubmenu_panel->slug;
        $permision->update();
        try {
            if ($result == true) {
                $success = true;
                $flag = 'success';
                $subject = 'عملیات موفق';
                $message = 'اطلاعات با موفقیت ثبت شد';
            } else {
                $success = false;
                $flag = 'error';
                $subject = 'عملیات نا موفق';
                $message = 'اطلاعات ثبت نشد، لطفا مجددا تلاش نمایید';
            }

        } catch (Exception $e) {

            $success = false;
            $flag = 'error';
            $subject = 'خطا در ارتباط با سرور';
            // $message = strchr($e);
            $message = 'اطلاعات ثبت نشد،لطفا بعدا مجدد تلاش نمایید ';
        }

        return response()->json(['success' => $success, 'subject' => $subject, 'flag' => $flag, 'message' => $message]);
    }

    public function destroy($id)
    {
        try {
            $submenu = SubmenuPanel::findOrfail($id);
            $result1 = $submenu->delete();

            $permission = Permission::whereSubmenu_panel_id($id)->first();
            $result2 = $permission->delete();

            if ($result1 == true && $result2 == true) {
                $success = true;
                $flag = 'success';
                $subject = 'عملیات موفق';
                $message = 'اطلاعات با موفقیت پاک شد';
            } elseif ($result1 == true && $result2 != true) {
                $success = false;
                $flag = 'error';
                $subject = 'عملیات نا موفق';
                $message = 'اطلاعات دسترسی ثبت نشد، لطفا مجددا تلاش نمایید';
            } elseif ($result1 != true && $result2 != true) {
                $success = false;
                $flag = 'error';
                $subject = 'عملیات نا موفق';
                $message = 'اطلاعات زیرمنو ثبت نشد، لطفا مجددا تلاش نمایید';
            }

        } catch (Exception $e) {

            $success = false;
            $flag = 'error';
            $subject = 'خطا در ارتباط با سرور';
            $message = 'اطلاعات پاک نشد،لطفا بعدا مجدد تلاش نمایید ';
        }

        return response()->json(['success' => $success, 'subject' => $subject, 'flag' => $flag, 'message' => $message]);
    }
}
