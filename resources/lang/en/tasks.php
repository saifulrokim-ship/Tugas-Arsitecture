<?php

return [

    /* Pages */
    'index_title' => 'Tasks & Dependencies',
    'index_subtitle' => 'Arrange tasks, subtasks, FS/SS dependencies, and assign team members.',
    'add_task' => '+ Add Task',
    'add_first_task' => 'Add the first task',
    'empty' => 'No tasks in this project yet.',
    'list_heading' => 'Task List',
    'stat_main_tasks' => 'main tasks',
    'stat_subtasks' => 'subtasks',
    'stat_critical' => 'critical tasks',
    'recalc_note' => 'Dates are recalculated automatically on every change (FR-17, FR-36).',

    /* Circular dependency */
    'circular_title' => 'The schedule cannot be computed because of a circular dependency.',
    'circular_body' => 'Remove one of the dependencies below to restore Gantt, PERT, and the critical path.',

    /* Warnings */
    'warnings_title' => 'Allocation & leave warnings (FR-23 / FR-24)',

    /* Filter */
    'filter_label' => 'Filter by member',
    'filter_all' => 'All members',
    'filter_hint' => 'Filtered to tasks assigned to the selected member.',

    /* Columns */
    'col_task' => 'Task',
    'col_schedule' => 'Schedule',
    'col_duration' => 'Duration',
    'col_slack' => 'Slack',
    'col_assignees' => 'Assignees',
    'col_percent' => 'Progress',

    /* Row */
    'toggle_subtasks' => 'Show or hide subtasks',
    'badge_parent' => 'Parent',
    'badge_milestone' => 'Milestone',
    'badge_critical' => 'Critical',
    'badge_summary' => 'Summary task',
    'subtask_count' => ':count subtasks',
    'type_subtask' => 'Subtask',
    'type_main_task' => 'Main task',
    'unassigned' => 'Not assigned yet',
    'to_date' => 'to :date',
    'duration_days' => ':count days',
    'slack_days' => ':count days',
    'slack_title' => 'Can slip :count workdays without affecting the project',
    'critical_title' => 'Slack 0: critical task',
    'progress_label' => ':percent% complete',
    'predecessor_label' => 'Predecessor',

    /* Row actions */
    'action_add_sub' => '+ Sub',
    'action_add_sub_title' => 'Add subtask',
    'action_move_up' => 'Move up',
    'action_move_down' => 'Move down',
    'action_indent' => 'Indent (make subtask)',
    'action_outdent' => 'Outdent (promote)',
    'delete_confirm' => 'Delete task ":name"?',
    'delete_confirm_summary' => 'Delete task ":name" together with all of its subtasks?',

    /* Keyboard hint */
    'keyboard_hint' => 'Keyboard: ↑ ↓ move between rows · ← → move between columns · Enter or Space opens the editor · Delete removes the task.',

    /* Create / edit */
    'create_title' => 'New Task',
    'create_sub_title' => 'New Subtask',
    'edit_title' => 'Edit Task',
    'save_task' => 'Save Task',
    'field_name' => 'Task Name',
    'field_name_placeholder' => 'Example: Requirements analysis',
    'field_parent' => 'Parent Task',
    'parent_none' => '— Main task (not a subtask) —',
    'parent_option_suffix' => '(parent)',
    'field_start' => 'Start Date',
    'field_start_hint' => 'Leave empty to follow dependencies / the project start date.',
    'field_duration' => 'Duration (workdays)',
    'field_duration_hint' => 'Use 0 to mark a milestone (a diamond ◆ point in time with no duration).',
    'field_percent' => 'Percent Complete (0–100)',
    'field_description' => 'Description',
    'field_priority' => 'Priority',
    'priority_low' => 'Low',
    'priority_medium' => 'Medium',
    'priority_high' => 'High',
    'priority_critical' => 'Critical',
    'field_assignees' => 'Assigned Team Members',
    'assignees_empty' => 'No team members yet. Add them first on the :link page.',
    'assignees_link' => 'Team & Leave',
    'duration_note' => 'Duration is counted in effective workdays (weekends and holidays are skipped). A parent task automatically takes the earliest start and latest finish of its subtasks (FR-06, FR-10).',

    /* Dependencies panel */
    'dependencies_heading' => 'Dependencies (Predecessor)',
    'dependencies_intro' => 'FS: starts after the predecessor finishes. SS: starts together. Lag in workdays (negative values create a lead).',
    'dependency_fs_label' => 'Finish-to-Start',
    'dependency_ss_label' => 'Start-to-Start',
    'dependency_lag_days' => 'lag :count days',
    'dependency_deleted_task' => 'Deleted task',
    'dependency_empty' => 'No predecessors yet.',
    'dependency_delete_confirm' => 'Delete this dependency? The schedule will be recalculated.',
    'dependency_predecessor_label' => 'Predecessor Task',
    'dependency_pick' => '— Pick a task —',
    'dependency_type_label' => 'Type',
    'dependency_lag_label' => 'Lag (days)',
    'dependency_add' => 'Add Dependency',
    'schedule_heading' => 'Computed Schedule',
    'schedule_intro' => 'Open the :link to see the latest dates, slack, and critical-path status after this change.',
    'schedule_link' => 'task page',

    /* Controller messages */
    'created' => 'Task ":name" added. The schedule and critical path were recalculated automatically.',
    'updated' => 'Task ":name" updated.',
    'deleted' => 'Task ":name" deleted.',
    'deleted_with_children' => 'Task ":name" deleted. :count subtasks were removed with it.',
    'moved_up' => 'Task ":name" moved up.',
    'moved_down' => 'Task ":name" moved down.',
    'move_blocked_first' => 'Task ":name" is already at the top.',
    'move_blocked_last' => 'Task ":name" is already at the bottom.',
    'indented' => 'Task ":name" is now a subtask of ":parent".',
    'indent_blocked' => 'Task ":name" cannot be indented further.',
    'outdented' => 'Task ":name" was promoted to a main task.',
    'outdent_blocked' => 'Task ":name" is already a main task.',
    'validation_duration_min' => 'Duration must be at least 0 workdays (0 = milestone).',
    'validation_duration_max' => 'Duration may not exceed 999 workdays.',
    'validation_percent_range' => 'Percent complete must be between 0 and 100.',
    'validation_parent_missing' => 'The parent task was not found in this project.',
    'validation_parent_cycle' => 'A subtask cannot become the parent of its own parent.',

    /* Dependency controller */
    'dependency_stored' => 'Task dependency saved. The schedule was recalculated automatically.',
    'dependency_removed' => 'Dependency removed. The schedule was recalculated automatically.',
    'dependency_self' => 'A task cannot depend on itself.',
    'dependency_exists' => 'That dependency is already registered.',
    'validation_predecessor_exists' => 'The predecessor must be a task in this project.',
    'validation_successor_exists' => 'The successor must be a task in this project.',
    'validation_type_in' => 'Dependency type must be FS (Finish-to-Start) or SS (Start-to-Start).',

];
