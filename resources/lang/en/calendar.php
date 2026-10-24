<?php

return [

    /* Pages */
    'index_title' => 'Working Calendar',
    'index_subtitle' => 'Workdays, weekends, and special holidays drive every duration calculation (FR-06).',

    /* Week settings */
    'week_heading' => 'Week Settings',
    'week_intro' => 'Tick the days that are working days, then tick the weekend days. If they overlap, the weekend day wins.',
    'workdays_label' => 'Working Days',
    'weekend_label' => 'Weekend (Non-Working)',
    'save_calendar' => 'Save Calendar',
    'save_note' => 'Changes recalculate the schedule immediately.',
    'days' => 'days',
    'weekend_summary' => 'Weekend: :days',

    /* Holidays */
    'holidays_heading' => 'Special / National Holidays',
    'holiday_date' => 'Date',
    'holiday_end_date' => 'End Date (optional range)',
    'holiday_end_hint' => 'Fill in to close a multi-day holiday range.',
    'holiday_description' => 'Description',
    'holiday_description_placeholder' => 'Example: Independence Day',
    'holiday_add' => 'Add',
    'holiday_col_date' => 'Date',
    'holiday_col_description' => 'Description',
    'holiday_range' => ':from – :to',
    'holiday_empty' => 'No special holidays yet.',
    'holiday_delete_confirm' => 'Delete this holiday?',
    'holiday_note' => 'Special holidays are not counted as workdays, so durations and the critical path adjust automatically.',

    /* Template import */
    'template_import' => 'Import Indonesian national holiday template',
    'template_import_note' => 'One click adds the national holidays for the project year. Re-importing never creates duplicates (CAL-4).',
    'template_imported' => ':count national holiday(s) added for :year. :skipped duplicate(s) skipped.',
    'template_none_new' => 'All national holidays for :year are already in the list.',

    /* Day names (index 0 = Monday) */
    'day_names' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'],

    /* Controller messages */
    'updated' => 'Working calendar updated. Workdays: :workdays. Weekend: :weekend.',
    'workdays_required' => 'Pick at least one working day.',
    'holiday_created' => 'Holiday :date added.',
    'holiday_updated' => 'Holiday updated.',
    'holiday_deleted' => 'Holiday deleted. The schedule was recalculated automatically.',
    'holiday_date_required' => 'Holiday date is required.',
    'holiday_end_after' => 'The holiday end date cannot be before the start date.',

];
