<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $fillable = [
        'user_id',
        'action',
        'auditable_type',
        'auditable_id',
        'before_values',
        'after_values',
        'result',
    ];

    protected function casts(): array
    {
        return [
            'before_values' => 'array',
            'after_values' => 'array',
        ];
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'user_id')->withTrashed();
    }

    public static function record(
        string $action,
        mixed $auditable = null,
        array $before = [],
        array $after = [],
        string $result = 'success'
    ): void {
        try {
            $actor = auth('api')->user();
            $type = null;
            $id = null;

            if ($auditable instanceof Model) {
                $type = $auditable->getMorphClass();
                $id = $auditable->getKey();
            } elseif (is_int($auditable) || ctype_digit((string) $auditable)) {
                $id = (int) $auditable;
            }

            static::create([
                'user_id' => $actor?->id,
                'action' => $action,
                'auditable_type' => $type,
                'auditable_id' => $id,
                'before_values' => $before ?: null,
                'after_values' => $after ?: null,
                'result' => $result,
            ]);
        } catch (\Throwable $exception) {
            report($exception);
        }
    }
}
