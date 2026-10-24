<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Holiday extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'project_id',
        'date',
        'end_date',
        'description',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'date' => 'date',
        'end_date' => 'date',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
