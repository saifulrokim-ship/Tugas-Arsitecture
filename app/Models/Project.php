<?php

namespace App\Models;

use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    /** @use HasFactory<ProjectFactory> */
    use HasFactory;

    use SoftDeletes;

    /** @var list<string> */
    protected $fillable = [
        'name',
        'organization',
        'description',
        'start_date',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'start_date' => 'date',
    ];

    public function calendar(): HasOne
    {
        return $this->hasOne(ProjectCalendar::class);
    }

    public function holidays(): HasMany
    {
        return $this->hasMany(Holiday::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function dependencies(): HasMany
    {
        return $this->hasMany(Dependency::class);
    }

    public function resources(): HasMany
    {
        return $this->hasMany(Resource::class);
    }
}
