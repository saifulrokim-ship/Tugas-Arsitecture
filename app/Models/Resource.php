<?php

namespace App\Models;

use App\Casts\EncryptedInteger;
use Database\Factories\ResourceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Resource extends Model
{
    /** @use HasFactory<ResourceFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'project_id',
        'name',
        'contact',
        'role',
        'salary',
    ];

    /** @var list<string> */
    protected $hidden = [
        'salary',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'salary' => EncryptedInteger::class,
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /** @return HasMany<ResourceLeave> */
    public function leaves(): HasMany
    {
        return $this->hasMany(ResourceLeave::class);
    }

    /** @return BelongsToMany<Task, TaskAssignment> */
    public function tasks(): BelongsToMany
    {
        return $this->belongsToMany(Task::class, 'task_assignments')->withTimestamps();
    }
}
