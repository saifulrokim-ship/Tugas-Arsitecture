<?php

namespace App\Services\Schedule;

use RuntimeException;

/**
 * Dependensi melingkar membuat jadwal tidak dapat dihitung (FR-16).
 */
class CircularDependencyException extends RuntimeException
{
    /** @param list<string> $taskNames */
    public function __construct(public readonly array $taskNames)
    {
        parent::__construct(__('schedule.circular_dependency', [
            'tasks' => implode(' → ', $taskNames),
        ]));
    }
}
