<?php

namespace App\Services;

use App\Models\Investstep;
use App\Models\ProjectStageFormSubmission;
use App\Models\ProjectStageInstance;
use App\Models\StageFormDefinition;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class StageFormService
{
    public function createDefinition(Investstep $step, array $data, User $actor): StageFormDefinition
    {
        return DB::transaction(function () use ($step, $data, $actor): StageFormDefinition {
            $existing = StageFormDefinition::query()
                ->where('invest_step_id', $step->getKey())
                ->where('code', $data['code'])
                ->lockForUpdate()
                ->get();
            $existing->each->update(['is_active' => false]);

            return StageFormDefinition::query()->create([
                'invest_step_id' => $step->getKey(),
                'code' => $data['code'],
                'title' => $data['title'],
                'version' => ((int) $existing->max('version')) + 1,
                'json_schema' => $data['json_schema'],
                'ui_schema' => $data['ui_schema'] ?? null,
                'is_required' => (bool) ($data['is_required'] ?? false),
                'is_active' => true,
                'created_by' => $actor->getKey(),
            ]);
        }, 3);
    }

    public function submit(
        ProjectStageInstance $stage,
        StageFormDefinition $definition,
        array $payload,
        bool $submit,
        User $actor
    ): ProjectStageFormSubmission {
        abort_unless((int) $definition->invest_step_id === (int) $stage->invest_step_id, 422);
        $project = $stage->project;
        $isOwner = (int) $project->user_id === (int) $actor->getKey();
        if (! $isOwner && ! app(InvestmentWorkflowAccessService::class)->canManageStage(
            $actor,
            (int) $stage->invest_step_id
        )) {
            throw new AuthorizationException('اجازه تکمیل فرم این مرحله را ندارید.');
        }

        if (in_array($stage->status->value, ['locked', 'approved', 'rejected'], true)) {
            throw ValidationException::withMessages(['stage' => 'فرم این مرحله در وضعیت جاری قابل ویرایش نیست.']);
        }

        $this->validatePayload($definition->json_schema, $payload);

        return DB::transaction(function () use ($stage, $definition, $payload, $submit, $actor): ProjectStageFormSubmission {
            $revision = ((int) ProjectStageFormSubmission::query()
                ->where('project_stage_instance_id', $stage->getKey())
                ->where('stage_form_definition_id', $definition->getKey())
                ->lockForUpdate()
                ->max('revision')) + 1;

            return ProjectStageFormSubmission::query()->create([
                'project_stage_instance_id' => $stage->getKey(),
                'stage_form_definition_id' => $definition->getKey(),
                'revision' => $revision,
                'status' => $submit ? 'submitted' : 'draft',
                'payload' => $payload,
                'submitted_by' => $submit ? $actor->getKey() : null,
                'submitted_at' => $submit ? now() : null,
            ]);
        }, 3);
    }

    public function assertRequiredFormsSubmitted(ProjectStageInstance $stage): void
    {
        $required = StageFormDefinition::query()
            ->where('invest_step_id', $stage->invest_step_id)
            ->where('is_active', true)
            ->where('is_required', true)
            ->get(['id', 'title']);

        $submittedIds = ProjectStageFormSubmission::query()
            ->where('project_stage_instance_id', $stage->getKey())
            ->whereIn('stage_form_definition_id', $required->pluck('id'))
            ->whereIn('status', ['submitted', 'approved'])
            ->pluck('stage_form_definition_id')
            ->unique();
        $missing = $required->whereNotIn('id', $submittedIds);

        if ($missing->isNotEmpty()) {
            throw ValidationException::withMessages([
                'forms' => 'فرم‌های الزامی این مرحله تکمیل نشده‌اند: '.$missing->pluck('title')->implode('، '),
            ]);
        }
    }

    private function validatePayload(array $schema, array $payload): void
    {
        $properties = (array) ($schema['properties'] ?? []);
        $required = (array) ($schema['required'] ?? []);
        $rules = [];

        foreach ($properties as $name => $property) {
            if (! is_array($property)) {
                continue;
            }

            $fieldRules = in_array($name, $required, true) ? ['required'] : ['nullable'];
            $fieldRules[] = match ($property['type'] ?? 'string') {
                'integer' => 'integer',
                'number' => 'numeric',
                'boolean' => 'boolean',
                'array' => 'array',
                'object' => 'array',
                default => 'string',
            };
            if (isset($property['enum']) && is_array($property['enum'])) {
                $fieldRules[] = Rule::in($property['enum']);
            }
            if (isset($property['maxLength'])) {
                $fieldRules[] = 'max:'.(int) $property['maxLength'];
            }
            if (isset($property['minimum'])) {
                $fieldRules[] = 'min:'.$property['minimum'];
            }
            if (isset($property['maximum'])) {
                $fieldRules[] = 'max:'.$property['maximum'];
            }
            $rules[$name] = $fieldRules;
        }

        $validator = Validator::make($payload, $rules);
        if (($schema['additionalProperties'] ?? true) === false) {
            $unknown = array_diff(array_keys($payload), array_keys($properties));
            if ($unknown !== []) {
                $validator->after(static function ($validator) use ($unknown): void {
                    $validator->errors()->add('payload', 'فیلدهای تعریف‌نشده مجاز نیستند: '.implode('، ', $unknown));
                });
            }
        }
        $validator->validate();
    }
}
