<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Dependency extends Model
{
    public const TYPE_FINISH_TO_START = 'FS';

    public const TYPE_START_TO_START = 'SS';

    /** @var list<string> */
    protected $fillable = [
        'project_id',
        'predecessor_id',
        'successor_id',
        'type',
        'lag',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'lag' => 'integer',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function predecessor(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'predecessor_id');
    }

    public function successor(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'successor_id');
    }

    public function label(): string
    {
        return $this->type === self::TYPE_START_TO_START
            ? 'SS'.($this->lag > 0 ? '+'.$this->lag : '')
            : 'FS'.($this->lag > 0 ? '+'.$this->lag : '');
    }
}
