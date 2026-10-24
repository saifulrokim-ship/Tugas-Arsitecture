<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectCalendar extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'project_id',
        'workdays',
        'weekend_days',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'workdays' => 'array',
        'weekend_days' => 'array',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
