<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min(max($request->integer('per_page', 20), 1), 100);
        $query = AuditLog::query()->with('actor:id,name')->latest();

        if ($search = $request->query('search')) {
            $query->where(function ($builder) use ($search) {
                $builder->where('action', 'ilike', "%{$search}%")
                    ->orWhere('auditable_type', 'ilike', "%{$search}%")
                    ->orWhereHas('actor', function ($actorQuery) use ($search) {
                        $actorQuery->where('name', 'ilike', "%{$search}%")
                            ->orWhere('email', 'ilike', "%{$search}%");
                    });
            });
        }

        if ($action = $request->query('action')) {
            $query->where('action', $action);
        }

        $paginator = $query->paginate($perPage);
        $paginator->through(function (AuditLog $log) {
            $before = $this->safeValues($log->before_values);
            $after = $this->safeValues($log->after_values);

            return [
                'id' => $log->id,
                'action' => $log->action,
                'target_name' => $this->targetName($log, $before, $after),
                'before_values' => $before,
                'after_values' => $after,
                'result' => $log->result,
                'created_at' => $log->created_at,
                'actor' => $log->actor,
            ];
        });

        return response()->json($paginator);
    }

    private function safeValues(?array $values): ?array
    {
        if (!$values) {
            return null;
        }

        return collect($values)->only([
            'name',
            'description',
            'plate',
            'brand',
            'model',
            'status',
            'is_active',
            'start_time',
            'end_time',
            'start_date',
            'end_date',
            'state_name',
            'permissions',
        ])->all();
    }

    private function targetName(AuditLog $log, ?array $before, ?array $after): string
    {
        $values = $before ?: $after ?: [];
        return $values['name']
            ?? $values['plate']
            ?? $values['brand']
            ?? $values['state_name']
            ?? 'Registro administrativo';
    }
}
