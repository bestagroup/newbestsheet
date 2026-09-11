<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\MediaFile;
use App\Models\MenuPanel;
use App\Models\Project;
use App\Models\subject_file;
use App\Models\SubmenuPanel;
use App\Services\InvestmentWorkflowAccessService;
use App\Services\ProjectMediaService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;

class FilemanagerController extends Controller
{
    public function index(Request $request)
    {
        $thispage = [
            'title' => 'مدیریت فایل ها',
            'list' => 'لیست فایل ها',
            'add' => 'افزودن فایل ها',
            'create' => 'ایجاد فایل ها',
            'enter' => 'ورود فایل ها',
            'edit' => 'ویرایش فایل ها',
            'delete' => 'حذف فایل ها',
        ];
        $menupanels = MenuPanel::select('id', 'priority', 'icon', 'title', 'label', 'slug', 'status', 'class', 'controller')->get();
        $submenupanels = SubmenuPanel::select('id', 'priority', 'title', 'label', 'slug', 'status', 'class', 'controller', 'menu_id')->get();

        if ($request->ajax()) {
            $data = MediaFile::leftjoin('projects', 'projects.id', '=', 'media_files.project_id')
                ->leftjoin('subject_files', 'subject_files.id', '=', 'media_files.subject_id')
                ->select('media_files.id', 'media_files.file_path', 'media_files.name', 'media_files.original_name', 'media_files.type', 'media_files.size', 'media_files.updated_at', 'projects.title', 'projects.company_name', 'subject_files.title as step');
            app(InvestmentWorkflowAccessService::class)
                ->scopeWorkflowProjects($data, $request->user(), 'projects');

            return DataTables::of($data)
                ->addColumn('file_path', function ($data) {
                    $fileUrl = route('media.download', ['media' => $data->id, 'inline' => 1]);

                    if ($data->type === 'image') {
                        return '<img src="'.$fileUrl.'" alt="تصویر" style="width: 80px; height: auto;">';
                    } elseif ($data->type === 'audio') {
                        return '<audio controls style="width: 150px;"><source src="'.$fileUrl.'" type="audio/mpeg">مرورگر شما از پخش صوت پشتیبانی نمی‌کند.</audio>';
                    } elseif ($data->type === 'videos') {
                        return '<video width="160" height="90" controls><source src="'.$fileUrl.'" type="video/mp4">مرورگر شما از پخش ویدیو پشتیبانی نمی‌کند.</video>';
                    } else {
                        return '<a href="'.$fileUrl.'" target="_blank">'.'دانلود فایل'.'</a>';
                    }
                })
                ->addColumn('name', function ($data) {
                    return $data->name;
                })
                ->addColumn('step', function ($data) {
                    return $data->step;
                })
                ->addColumn('original_name', function ($data) {
                    return $data->original_name;
                })
                ->addColumn('title', function ($data) {
                    return $data->title;
                })
                ->addColumn('company_name', function ($data) {
                    return $data->company_name;
                })
                ->addColumn('type', function ($data) {
                    return match ($data->type) {
                        'image' => 'عکس',
                        'video' => 'ویدیویی',
                        'audio' => 'صوتی',
                        'document' => 'سند متنی',
                        'spreadsheet' => 'اکسل',
                        'presentation' => 'پاورپوینت',
                        'other' => 'سایر',
                        default => 'نامشخص',
                    };
                })
                ->addColumn('size', function ($data) {
                    $sizeInBytes = $data->size;

                    if ($sizeInBytes >= 1073741824) {
                        return number_format($sizeInBytes / 1073741824, 2).' GB';
                    } elseif ($sizeInBytes >= 1048576) {
                        return number_format($sizeInBytes / 1048576, 2).' MB';
                    } elseif ($sizeInBytes >= 1024) {
                        return number_format($sizeInBytes / 1024, 2).' KB';
                    } else {
                        return $sizeInBytes.' B';
                    }
                    // return ($data->size);
                })
                ->addColumn('date', function ($data) {
                    return jdate($data->updated_at)->format('Y/m/d');
                })
                ->editColumn('action', function ($data) {
                    $actionBtn = '';
                    if (auth()->user()->can('can-access', ['filemanager', 'edit'])) {
                        $actionBtn .= '<button type="button" class="btn btn-sm btn-outline-primary edit-btn" data-id="'.$data->id.'" data-url="'.route('filemanager.edit', $data->id).'"><i class="mdi mdi-pencil-outline"></i></button>';

                    }
                    if (auth()->user()->can('can-access', ['filemanager', 'delete'])) {
                        $actionBtn .= '<button type="button" class="btn btn-sm btn-icon btn-outline-danger mx-1 delete-btn" data-id="'.$data->id.'"><i class="mdi mdi-delete-outline"></i></button>';
                    }

                    return $actionBtn;
                })
                ->rawColumns(['action', 'file_path'])
                ->make(true);
        }

        return view('panel.file_manager')->with(compact(['menupanels', 'submenupanels', 'thispage']));
    }

    public function store(Request $request, ProjectMediaService $mediaService)
    {
        $validated = $request->validate([
            'file' => [
                'required',
                'file',
                'max:'.config('investment.documents.max_size_kb', 51200),
                'mimetypes:'.implode(',', config('investment.documents.allowed_mimes', [])),
            ],
            'record_id' => ['nullable', 'integer', 'exists:projects,id'],
            'subject_id' => ['nullable', 'integer', 'exists:subject_files,id'],
        ]);

        $project = ! empty($validated['record_id'])
            ? Project::query()->select('id', 'company_id')->findOrFail($validated['record_id'])
            : null;
        if ($project) {
            abort_unless(app(InvestmentWorkflowAccessService::class)->canViewProject(
                $request->user(),
                (int) $project->getKey()
            ), 403);
        }
        $media = $mediaService->store(
            $request->file('file'),
            $project,
            $validated['subject_id'] ?? null,
            (int) Auth::id()
        );

        return response()->json([
            'success' => true,
            'message' => 'فایل با موفقیت بارگذاری شد.',
            'file_id' => $media->id,
            'file_path' => $media->url,
            'download_url' => $media->url,
        ]);
    }

    public function edit($id)
    {
        $mediafile = MediaFile::query()->findOrFail($id);
        abort_unless(
            ! $mediafile->project_id || app(InvestmentWorkflowAccessService::class)->canViewProject(
                auth()->user(),
                (int) $mediafile->project_id
            ),
            403
        );
        $subject_files = subject_file::query()->orderBy('title')->get(['id', 'title']);
        $projectQuery = Project::query()->orderBy('title');
        app(InvestmentWorkflowAccessService::class)
            ->scopeWorkflowProjects($projectQuery, auth()->user());
        $projects = $projectQuery->get(['id', 'title']);

        return view('panel.partials.edit-form-filemanager', compact('mediafile', 'subject_files', 'projects'));

    }

    public function destroy($id, ProjectMediaService $mediaService)
    {
        $media = MediaFile::findOrFail($id);
        abort_unless(
            ! $media->project_id || app(InvestmentWorkflowAccessService::class)->canViewProject(
                auth()->user(),
                (int) $media->project_id
            ),
            403
        );

        try {
            $mediaService->delete($media);
            $result = true;

            if ($result) {
                $success = true;
                $flag = 'success';
                $subject = 'عملیات موفق';
                $message = 'اطلاعات با موفقیت پاک شد';
            } elseif ($result != true) {
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

    public function selectfile(Request $request)
    {

        $thispage = [
            'title' => 'مدیریت فایل ها',
            'list' => 'لیست فایل ها',
            'add' => 'افزودن فایل ها',
            'create' => 'ایجاد فایل ها',
            'enter' => 'ورود فایل ها',
            'edit' => 'ویرایش فایل ها',
            'delete' => 'حذف فایل ها',
        ];

        $recordId = $request->record_id;
        abort_unless(app(InvestmentWorkflowAccessService::class)->canViewProject(
            $request->user(),
            (int) $recordId
        ), 403);
        $files = MediaFile::where('project_id', $recordId)->get();

        return view('panel.files', compact('files', 'thispage'));
    }

    public function deletefile(Request $request, ProjectMediaService $mediaService)
    {
        $validated = $request->validate(['id' => ['required', 'integer', 'exists:media_files,id']]);
        $file = MediaFile::findOrFail($validated['id']);
        abort_unless(
            ! $file->project_id || app(InvestmentWorkflowAccessService::class)->canViewProject(
                $request->user(),
                (int) $file->project_id
            ),
            403
        );
        $mediaService->delete($file);

        return response()->json(['message' => 'Deleted successfully']);
    }

    public function filestatus(Request $request)
    {
        $validated = $request->validate([
            'id' => ['required', 'integer', 'exists:media_files,id'],
            'status' => ['required', 'integer', 'in:0,4,5'],
        ]);
        $file = MediaFile::query()->findOrFail($validated['id']);
        abort_unless(
            ! $file->project_id || app(InvestmentWorkflowAccessService::class)->canViewProject(
                $request->user(),
                (int) $file->project_id
            ),
            403
        );

        try {
            $file->status = $validated['status'];
            $result = $file->save();
            if ($result) {
                $success = true;
                $flag = 'success';
                $subject = 'عملیات موفق';
                $message = 'اطلاعات با موفقیت پاک شد';
            } elseif ($result != true) {
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

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'project_id' => ['required', 'integer', 'exists:projects,id'],
            'subject_id' => ['nullable', 'integer', 'exists:subject_files,id'],
        ]);

        $media = MediaFile::query()->findOrFail($id);
        $project = Project::query()->select('id', 'company_id')->findOrFail($validated['project_id']);
        abort_unless(
            ! $media->project_id || app(InvestmentWorkflowAccessService::class)->canViewProject(
                $request->user(),
                (int) $media->project_id
            ),
            403
        );
        abort_unless(app(InvestmentWorkflowAccessService::class)->canViewProject(
            $request->user(),
            (int) $project->getKey()
        ), 403);

        $media->fill([
            'subject_id' => $validated['subject_id'] ?? null,
            'project_id' => $project->id,
            'company_id' => $project->company_id,
        ])->save();

        return response()->json([
            'success' => true,
            'subject' => 'عملیات موفق',
            'flag' => 'success',
            'message' => 'اطلاعات با موفقیت ثبت شد',
        ]);
    }

    public function show(Request $request, $id)
    {
        abort_unless(app(InvestmentWorkflowAccessService::class)->canViewProject(
            $request->user(),
            (int) $id
        ), 403);
        if ($request->ajax()) {
            $data = MediaFile::leftjoin('projects', 'projects.id', '=', 'media_files.project_id')
                ->leftjoin('subject_files', 'subject_files.id', '=', 'media_files.subject_id')
                ->select('media_files.id', 'media_files.file_path', 'media_files.name', 'media_files.type', 'media_files.size', 'media_files.updated_at', 'projects.title', 'subject_files.title as step')
                ->where('media_files.project_id', $id);

            return DataTables::of($data)
                ->addColumn('file_path', function ($data) {
                    $fileUrl = route('media.download', ['media' => $data->id, 'inline' => 1]);

                    if ($data->type === 'image') {
                        return '<img src="'.$fileUrl.'" alt="تصویر" style="width: 80px; height: auto;">';
                    } elseif ($data->type === 'audio') {
                        return '<audio controls style="width: 150px;"><source src="'.$fileUrl.'" type="audio/mpeg">مرورگر شما از پخش صوت پشتیبانی نمی‌کند.</audio>';
                    } elseif ($data->type === 'videos') {
                        return '<video width="160" height="90" controls><source src="'.$fileUrl.'" type="video/mp4">مرورگر شما از پخش ویدیو پشتیبانی نمی‌کند.</video>';
                    } else {
                        return '<a href="'.$fileUrl.'" target="_blank">'.'دانلود فایل'.'</a>';
                    }
                })
                ->addColumn('name', function ($data) {
                    return $data->name;
                })
                ->addColumn('step', function ($data) {
                    return $data->step;
                })
                ->addColumn('type', function ($data) {
                    return match ($data->type) {
                        'image' => 'عکس',
                        'video' => 'ویدیویی',
                        'audio' => 'صوتی',
                        'document' => 'سند متنی',
                        'spreadsheet' => 'اکسل',
                        'presentation' => 'پاورپوینت',
                        'other' => 'سایر',
                        default => 'نامشخص',
                    };
                })
                ->addColumn('size', function ($data) {
                    $sizeInBytes = $data->size;

                    if ($sizeInBytes >= 1073741824) {
                        return number_format($sizeInBytes / 1073741824, 2).' GB ';
                    } elseif ($sizeInBytes >= 1048576) {
                        return number_format($sizeInBytes / 1048576, 2).' MB ';
                    } elseif ($sizeInBytes >= 1024) {
                        return number_format($sizeInBytes / 1024, 2).' KB ';
                    } else {
                        return $sizeInBytes.' B ';
                    }
                    // return ($data->size);
                })
                ->addColumn('date', function ($data) {
                    return jdate($data->updated_at)->format('Y/m/d');
                })
                ->rawColumns(['file_path'])
                ->make(true);
        }
    }
}
