<?php

namespace App\Services;

use App\Models\User_logs;
use Illuminate\Http\Request;
use Throwable;

class ActivityLogService
{
    public const REQUEST_LOGGED_ATTRIBUTE = 'investment_activity_logged';

    public function record(
        string $action,
        ?string $description = null,
        ?int $userId = null,
        bool $status = true,
        ?Request $request = null,
        ?string $subjectType = null,
        ?int $subjectId = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?array $metadata = null,
    ): ?User_logs {
        try {
            $request ??= request();

            $requestContext = $this->requestContext($request);
            $metadata = array_filter(
                array_replace($requestContext, $metadata ?? []),
                static fn ($value): bool => $value !== null && $value !== '' && $value !== []
            );

            $log = User_logs::query()->create([
                'user_id' => $userId ?? auth()->id(),
                'action' => $action,
                'ip_address' => $request?->ip(),
                'user_agent' => $request?->userAgent(),
                'request_id' => $request?->attributes->get('request_id'),
                'subject_type' => $subjectType,
                'subject_id' => $subjectId,
                'status' => $status,
                'description' => $description,
                'old_values' => $oldValues,
                'new_values' => $newValues,
                'metadata' => $metadata,
            ]);

            $request?->attributes->set(self::REQUEST_LOGGED_ATTRIBUTE, true);

            return $log;
        } catch (Throwable $exception) {
            report($exception);

            return null;
        }
    }

    private function requestContext(?Request $request): array
    {
        if (! $request) {
            return [];
        }

        $route = $request->route();
        $routeParameters = collect($route?->parameters() ?? [])->map(
            static fn ($value) => is_object($value) && method_exists($value, 'getKey')
                ? $value->getKey()
                : (is_scalar($value) ? $value : null)
        )->filter(static fn ($value): bool => $value !== null)->all();

        $project = $routeParameters['project'] ?? $request->input('project_id');
        $actor = $request->user();

        return [
            'route_name' => $route?->getName(),
            'request_method' => $request->method(),
            'project_id' => is_numeric($project) ? (int) $project : null,
            'route_parameters' => $routeParameters,
            'actor_roles' => $actor?->roles?->pluck('title')->values()->all(),
        ];
    }
}
