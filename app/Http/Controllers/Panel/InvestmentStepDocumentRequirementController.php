<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\InvestmentStepDocumentRequirement;
use App\Models\Investstep;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class InvestmentStepDocumentRequirementController extends Controller
{
    public function store(Request $request, int $investStep): JsonResponse
    {
        $step = Investstep::query()->findOrFail($investStep);
        $validated = $request->validate([
            'subject_file_id' => [
                'required',
                'integer',
                'exists:subject_files,id',
                Rule::unique('invest_step_document_requirements', 'subject_file_id')
                    ->where(static fn ($query) => $query->where('invest_step_id', $step->id)),
            ],
            'is_required' => ['nullable', 'boolean'],
            'minimum_files' => ['nullable', 'integer', 'min:1', 'max:20'],
        ]);

        $sortOrder = (int) $step->documentRequirements()->max('sort_order') + 1;

        $requirement = $step->documentRequirements()->create([
            'subject_file_id' => $validated['subject_file_id'],
            'is_required' => $request->boolean('is_required'),
            'minimum_files' => $validated['minimum_files'] ?? 1,
            'sort_order' => $sortOrder,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'سند مرحله با موفقیت به الزامات فرایند اضافه شد.',
            'data' => $requirement->load('subject'),
        ]);
    }

    public function update(Request $request, int $investStep, int $requirement): JsonResponse
    {
        $step = Investstep::query()->findOrFail($investStep);
        $requirementModel = $this->requirementForStep($step, $requirement);

        $validated = $request->validate([
            'is_required' => ['required', 'boolean'],
            'minimum_files' => ['required', 'integer', 'min:1', 'max:20'],
        ]);

        $requirementModel->fill($validated)->save();

        return response()->json([
            'success' => true,
            'message' => 'الزام سند مرحله با موفقیت به‌روزرسانی شد.',
            'data' => $requirementModel->fresh()->load('subject'),
        ]);
    }

    public function destroy(int $investStep, int $requirement): JsonResponse
    {
        $step = Investstep::query()->findOrFail($investStep);
        $requirementModel = $this->requirementForStep($step, $requirement);
        $requirementModel->delete();

        return response()->json([
            'success' => true,
            'message' => 'ارتباط سند با مرحله حذف شد.',
        ]);
    }

    private function requirementForStep(Investstep $step, int $requirementId): InvestmentStepDocumentRequirement
    {
        return $step->documentRequirements()->whereKey($requirementId)->firstOrFail();
    }
}
