<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Investstep;
use App\Services\StageFormService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StageFormDefinitionController extends Controller
{
    public function store(Request $request, Investstep $investStep, StageFormService $service): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:80', 'regex:/^[A-Za-z0-9_.-]+$/'],
            'title' => ['required', 'string', 'max:255'],
            'json_schema' => ['required', 'array'],
            'json_schema.type' => ['required', 'in:object'],
            'json_schema.properties' => ['required', 'array'],
            'ui_schema' => ['nullable', 'array'],
            'is_required' => ['nullable', 'boolean'],
        ]);
        $definition = $service->createDefinition($investStep, $validated, $request->user());

        return response()->json(['success' => true, 'data' => $definition], 201);
    }
}
