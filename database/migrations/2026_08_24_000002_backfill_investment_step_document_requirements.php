<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('invest_step_document_requirements')
            || ! Schema::hasTable('investsteps')
            || ! Schema::hasTable('subject_files')
            || DB::table('invest_step_document_requirements')->exists()) {
            return;
        }

        // This is the document mapping used by the existing investor workflow UI.
        // It is backfilled only when the requirements table is completely empty,
        // so a manager's existing configuration is never changed or restored.
        $mapping = [
            1 => [4],
            3 => [1],
            4 => [2],
            5 => [29, 30],
            6 => [3, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16],
            8 => [25],
            9 => [19, 31, 32, 33],
            10 => [27, 30, 34],
            11 => [38],
            12 => [28, 36],
            13 => [20],
            14 => [18, 21],
            15 => [18, 22],
            16 => [18, 23],
            17 => [18, 24],
            18 => [37],
        ];

        $stepIds = DB::table('investsteps')->whereIn('id', array_keys($mapping))->pluck('id')->mapWithKeys(
            static fn ($id): array => [(int) $id => true]
        );
        $subjectIds = DB::table('subject_files')->whereIn(
            'id',
            collect($mapping)->flatten()->unique()->values()
        )->pluck('id')->mapWithKeys(static fn ($id): array => [(int) $id => true]);
        $now = now();
        $rows = [];

        foreach ($mapping as $stepId => $subjects) {
            if (! $stepIds->has($stepId)) {
                continue;
            }

            foreach (array_values($subjects) as $sortOrder => $subjectId) {
                if (! $subjectIds->has($subjectId)) {
                    continue;
                }

                $rows[] = [
                    'invest_step_id' => $stepId,
                    'subject_file_id' => $subjectId,
                    'is_required' => false,
                    'minimum_files' => 1,
                    'sort_order' => $sortOrder + 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        foreach ($rows as $row) {
            DB::table('invest_step_document_requirements')->insertOrIgnore($row);
        }
    }

    public function down(): void
    {
        // Intentionally left intact: these rows can be edited by managers after deployment.
    }
};
