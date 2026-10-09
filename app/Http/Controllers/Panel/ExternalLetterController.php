<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Http\Requests\Panel\ExternalLetterRequest;
use App\Models\User;
use App\Services\EnterpriseRecordAccess;
use App\Services\ExternalLetterService;
use App\Services\MediaFileStorageLocator;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class ExternalLetterController extends Controller
{
    public function __construct(private readonly EnterpriseRecordAccess $access, private readonly ExternalLetterService $service) {}

    public function index(Request $request)
    {
        $request->validate(['q' => ['nullable', 'string', 'max:255'], 'status' => ['nullable', Rule::in(['registered', 'in_progress', 'closed'])]]);
        $letters = $this->access->letters($request->user())->with('assignee')
            ->when($request->filled('q'), fn ($q) => $q->where(function ($q) use ($request): void {
                $q->where('subject', 'like', '%'.$request->q.'%')->orWhere('correspondent', 'like', '%'.$request->q.'%')->orWhere('external_reference', 'like', '%'.$request->q.'%');
            }))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))->latest()->paginate(20)->withQueryString();
        $projects = $this->access->projects($request->user())->get(['id', 'title']);
        $users = User::query()->where('status', 4)->where('level', '!=', 'applicant')->get(['id', 'name']);

        $attachments = $this->access->attachments($request->user());

        return view('panel.letters.index', compact('letters', 'projects', 'users', 'attachments'));
    }

    public function store(ExternalLetterRequest $request)
    {
        $letter = $this->service->save($request->validated(), $request->user());

        return redirect()->route('letters.show', $letter->id)->with('success', 'نامه در دبیرخانه ثبت شد.');
    }

    public function show(Request $request, int $letter)
    {
        $letter = $this->access->letters($request->user())->with('project', 'assignee', 'media')->findOrFail($letter);
        $projects = $this->access->projects($request->user())->get(['id', 'title']);
        $users = User::query()->where('status', 4)->where('level', '!=', 'applicant')->get(['id', 'name']);

        $attachments = $this->access->attachments($request->user());

        return view('panel.letters.show', compact('letter', 'projects', 'users', 'attachments'));
    }

    public function update(ExternalLetterRequest $request, int $letter)
    {
        $this->service->save($request->validated(), $request->user(), $letter);

        return back()->with('success', 'نامه ویرایش و سابقه تغییر ذخیره شد.');
    }

    public function transition(Request $request, int $letter)
    {
        $data = $request->validate(['status' => ['required', Rule::in(['in_progress', 'closed'])], 'lock_version' => ['required', 'integer'], 'completion_note' => ['required_if:status,closed', 'nullable', 'string', 'max:5000']]);
        $this->service->transition($letter, $data['status'], $data['lock_version'], $data['completion_note'] ?? null, $request->user());

        return back()->with('success', 'وضعیت پیگیری ثبت شد.');
    }

    public function attachment(Request $request, int $letter, MediaFileStorageLocator $locator)
    {
        $letter = $this->access->letters($request->user())->with('media')->findOrFail($letter);
        $media = $letter->media;
        abort_unless($media && $media->scan_status === 'clean', 404);
        $location = $locator->locate($media);
        abort_unless($location, 404);

        return $location['disk']->download($location['path'], $media->original_name ?: $media->name, [
            'Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
