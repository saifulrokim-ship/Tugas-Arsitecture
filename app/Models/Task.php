<?php

namespace App\Models;

use Database\Factories\TaskFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Task extends Model
{
    /** @use HasFactory<TaskFactory> */
    use HasFactory;

    public const PRIORITY_LOW = 'low';

    public const PRIORITY_MEDIUM = 'medium';

    public const PRIORITY_HIGH = 'high';

    public const PRIORITY_CRITICAL = 'critical';

    /** @var list<string> */
    protected $fillable = [
        'project_id',
        'parent_id',
        'name',
        'description',
        'start_date',
        'duration',
        'percent_complete',
        'priority',
        'sort_order',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'start_date' => 'date',
        'duration' => 'integer',
        'percent_complete' => 'integer',
        'sort_order' => 'integer',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /** @return HasMany<self> */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order')->orderBy('id');
    }

    /** @return HasMany<Dependency> */
    public function successors(): HasMany
    {
        return $this->hasMany(Dependency::class, 'predecessor_id');
    }

    /** @return HasMany<Dependency> */
    public function predecessors(): HasMany
    {
        return $this->hasMany(Dependency::class, 'successor_id');
    }

    /** @return BelongsToMany<resource, TaskAssignment> */
    public function resources(): BelongsToMany
    {
        return $this->belongsToMany(Resource::class, 'task_assignments')->withTimestamps();
    }

    public function isSummary(): bool
    {
        return $this->children->isNotEmpty();
    }

    /** Milestone (PRD TSK-5): durasi 0, menandai titik penting tanpa rentang waktu. */
    public function isMilestone(): bool
    {
        return (int) $this->duration === 0;
    }
}
