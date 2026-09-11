<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Http\Requests\Panel\EmployeeRequest;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Services\ActivityLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->input('q'));
        $employees = Employee::query()
            ->with(['documents', 'assets:id,custodian_employee_id'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($filter) use ($search): void {
                    $filter->where('personnel_code', 'like', '%'.$search.'%')
                        ->orWhere('national_id', 'like', '%'.$search.'%')
                        ->orWhere('first_name', 'like', '%'.$search.'%')
                        ->orWhere('last_name', 'like', '%'.$search.'%')
                        ->orWhere('department', 'like', '%'.$search.'%')
                        ->orWhere('job_title', 'like', '%'.$search.'%');
                });
            })
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(15)
            ->withQueryString();

        $editingEmployee = $request->filled('edit')
            ? $this->editableEmployee($request)
            : null;

        return view('panel.employees', [
            'thispage' => ['title' => 'مدیریت کارکنان و مدارک پرسنلی'],
            'employees' => $employees,
            'editingEmployee' => $editingEmployee,
            'statusLabels' => Employee::statusLabels(),
            'documentCategories' => EmployeeDocument::categoryLabels(),
        ]);
    }

    private function editableEmployee(Request $request): Employee
    {
        abort_unless($request->user()->can('can-access', ['employees', 'edit']), 403);

        return Employee::query()->findOrFail($request->integer('edit'));
    }

    public function store(EmployeeRequest $request, ActivityLogService $activity): RedirectResponse
    {
        $employee = Employee::query()->create([
            ...$request->validated(),
            'created_by' => $request->user()->getKey(),
        ]);
        $activity->record(
            'employee.created',
            "پرونده پرسنلی {$employee->full_name} ایجاد شد.",
            subjectType: Employee::class,
            subjectId: (int) $employee->getKey(),
            newValues: $employee->toArray()
        );

        return redirect()->route('employees.index')->with('success', 'اطلاعات کارمند با موفقیت ثبت شد.');
    }

    public function update(
        EmployeeRequest $request,
        Employee $employee,
        ActivityLogService $activity
    ): RedirectResponse {
        $old = $employee->toArray();
        $employee->update($request->validated());
        $activity->record(
            'employee.updated',
            "پرونده پرسنلی {$employee->full_name} به‌روزرسانی شد.",
            subjectType: Employee::class,
            subjectId: (int) $employee->getKey(),
            oldValues: $old,
            newValues: $employee->fresh()->toArray()
        );

        return redirect()->route('employees.index')->with('success', 'اطلاعات کارمند به‌روزرسانی شد.');
    }

    public function destroy(Employee $employee, ActivityLogService $activity): RedirectResponse
    {
        $old = $employee->toArray();
        $employee->delete();
        $activity->record(
            'employee.archived',
            "پرونده پرسنلی {$employee->full_name} بایگانی شد.",
            subjectType: Employee::class,
            subjectId: (int) $employee->getKey(),
            oldValues: $old
        );

        return redirect()->route('employees.index')->with('success', 'پرونده کارمند بایگانی شد.');
    }

    public function storeDocument(
        Request $request,
        Employee $employee,
        ActivityLogService $activity
    ): RedirectResponse {
        $validated = $request->validate([
            'category' => ['required', Rule::in(array_keys(EmployeeDocument::categoryLabels()))],
            'title' => ['required', 'string', 'max:255'],
            'document' => [
                'required',
                'file',
                'max:10240',
                'mimetypes:application/pdf,image/jpeg,image/png,image/webp,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ],
        ]);

        $file = $request->file('document');
        $path = $file->store('employee-documents/'.$employee->getKey(), 'local');
        $document = $employee->documents()->create([
            'category' => $validated['category'],
            'title' => $validated['title'],
            'disk' => 'local',
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'uploaded_by' => $request->user()->getKey(),
        ]);
        $activity->record(
            'employee.document_uploaded',
            "مدرک {$document->title} به پرونده {$employee->full_name} افزوده شد.",
            subjectType: EmployeeDocument::class,
            subjectId: (int) $document->getKey(),
            newValues: $document->toArray()
        );

        return back()->with('success', 'مدرک پرسنلی با موفقیت بارگذاری شد.');
    }

    public function downloadDocument(Employee $employee, EmployeeDocument $document): StreamedResponse|BinaryFileResponse
    {
        abort_unless((int) $document->employee_id === (int) $employee->getKey(), 404);
        abort_unless(Storage::disk($document->disk)->exists($document->path), 404);

        return Storage::disk($document->disk)->download($document->path, $document->original_name, [
            'Content-Type' => $document->mime_type ?: 'application/octet-stream',
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function destroyDocument(
        Employee $employee,
        EmployeeDocument $document,
        ActivityLogService $activity
    ): RedirectResponse {
        abort_unless((int) $document->employee_id === (int) $employee->getKey(), 404);
        Storage::disk($document->disk)->delete($document->path);
        $documentId = (int) $document->getKey();
        $document->delete();
        $activity->record(
            'employee.document_deleted',
            "یک مدرک از پرونده {$employee->full_name} حذف شد.",
            subjectType: EmployeeDocument::class,
            subjectId: $documentId
        );

        return back()->with('success', 'مدرک پرسنلی حذف شد.');
    }
}
