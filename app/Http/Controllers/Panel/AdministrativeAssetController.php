<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Http\Requests\Panel\AdministrativeAssetRequest;
use App\Models\AdministrativeAsset;
use App\Models\Employee;
use App\Services\ActivityLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AdministrativeAssetController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->input('q'));
        $assets = AdministrativeAsset::query()
            ->with('custodian:id,first_name,last_name,personnel_code')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($filter) use ($search): void {
                    $filter->where('asset_code', 'like', '%'.$search.'%')
                        ->orWhere('name', 'like', '%'.$search.'%')
                        ->orWhere('category', 'like', '%'.$search.'%')
                        ->orWhere('serial_number', 'like', '%'.$search.'%')
                        ->orWhere('property_tag', 'like', '%'.$search.'%')
                        ->orWhere('location', 'like', '%'.$search.'%');
                });
            })
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $editingAsset = $request->filled('edit')
            ? $this->editableAsset($request)
            : null;

        return view('panel.administrative-assets', [
            'thispage' => ['title' => 'مدیریت کالاها و اموال'],
            'assets' => $assets,
            'editingAsset' => $editingAsset,
            'employees' => Employee::query()->where('status', 'active')->orderBy('last_name')->get([
                'id', 'first_name', 'last_name', 'personnel_code',
            ]),
            'conditionLabels' => AdministrativeAsset::conditionLabels(),
            'statusLabels' => AdministrativeAsset::statusLabels(),
        ]);
    }

    private function editableAsset(Request $request): AdministrativeAsset
    {
        abort_unless($request->user()->can('can-access', ['assets', 'edit']), 403);

        return AdministrativeAsset::query()->findOrFail($request->integer('edit'));
    }

    public function store(
        AdministrativeAssetRequest $request,
        ActivityLogService $activity
    ): RedirectResponse {
        $asset = AdministrativeAsset::query()->create([
            ...$request->validated(),
            'created_by' => $request->user()->getKey(),
        ]);
        $activity->record(
            'administrative_asset.created',
            "مال با کد {$asset->asset_code} ثبت شد.",
            subjectType: AdministrativeAsset::class,
            subjectId: (int) $asset->getKey(),
            newValues: $asset->toArray()
        );

        return redirect()->route('assets.index')->with('success', 'اطلاعات کالا/مال با موفقیت ثبت شد.');
    }

    public function update(
        AdministrativeAssetRequest $request,
        AdministrativeAsset $asset,
        ActivityLogService $activity
    ): RedirectResponse {
        $old = $asset->toArray();
        $asset->update($request->validated());
        $activity->record(
            'administrative_asset.updated',
            "مال با کد {$asset->asset_code} به‌روزرسانی شد.",
            subjectType: AdministrativeAsset::class,
            subjectId: (int) $asset->getKey(),
            oldValues: $old,
            newValues: $asset->fresh()->toArray()
        );

        return redirect()->route('assets.index')->with('success', 'اطلاعات کالا/مال به‌روزرسانی شد.');
    }

    public function destroy(
        AdministrativeAsset $asset,
        ActivityLogService $activity
    ): RedirectResponse {
        $old = $asset->toArray();
        $asset->delete();
        $activity->record(
            'administrative_asset.archived',
            "مال با کد {$asset->asset_code} بایگانی شد.",
            subjectType: AdministrativeAsset::class,
            subjectId: (int) $asset->getKey(),
            oldValues: $old
        );

        return redirect()->route('assets.index')->with('success', 'رکورد کالا/مال بایگانی شد.');
    }
}
