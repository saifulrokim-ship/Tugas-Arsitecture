<?php

return [

    /* Pages */
    'index_title' => 'All Projects',
    'index_heading' => 'Project List',
    'index_intro' => 'Create a project, set its working calendar, arrange tasks, then track its Gantt, PERT, and critical path.',
    'new_project' => '+ New Project',
    'open_from_file' => 'Open from File',
    'empty' => 'No projects yet.',
    'empty_cta' => 'Create your first project',

    /* Table */
    'col_name' => 'Project Name',
    'col_organization' => 'Organization',
    'col_tasks' => 'Tasks',
    'col_team' => 'Team',
    'col_holidays' => 'Holidays',
    'col_start' => 'Start',
    'delete_confirm' => 'Delete project ":name" along with all of its tasks, team, and schedule?',

    /* Show */
    'subtitle_start' => 'Start :date',
    'save_to_file' => 'Save to File',
    'save_to_file_title' => 'Save the whole project to a local file',
    'schedule_error_title' => 'The schedule cannot be computed.',
    'schedule_error_fix' => 'Fix it on the Tasks page',
    'total_duration' => 'Total Project Duration',
    'planned_start' => 'Planned Start',
    'predicted_finish' => 'Predicted Finish',
    'critical_tasks' => 'Critical Tasks',
    'workdays_unit' => 'workdays',
    'detail_heading' => 'Project Details',
    'detail_organization' => 'Organization',
    'detail_description' => 'Description',
    'detail_team' => 'Team Members',
    'detail_holidays' => 'Special Holidays',
    'detail_calendar' => 'Working Calendar',
    'calendar_weekend' => 'weekend: :days',
    'calendar_default' => 'Monday – Friday',
    'people_unit' => 'people',
    'days_unit' => 'days',
    'critical_heading' => 'Critical Path',
    'critical_slack_zero' => 'slack = 0',
    'critical_empty_no_tasks' => 'No tasks yet. Add tasks to compute the critical path.',
    'critical_summary_tag' => '(summary task)',
    'critical_note' => 'These tasks must not slip, or the whole project slips. See details in the :link.',
    'pert_link_label' => 'PERT Chart',
    'delete_confirm_summary' => 'Delete this project along with all of its data?',

    /* Create / edit */
    'create_title' => 'New Project',
    'create_subtitle' => 'Step 1 of the main flow: create the project, then set its working calendar.',
    'edit_title' => 'Edit Project',
    'save_project' => 'Save Project',
    'field_name' => 'Project Name',
    'field_organization' => 'Organization',
    'field_description' => 'Description',
    'field_start_date' => 'Start Date',

    /* Import */
    'import_title' => 'Open Project from File',
    'import_subtitle' => 'Choose a Proyekta export file (.json) to reload the whole project.',
    'import_intro' => 'The file contains the entire project: project data, working calendar, holidays, tasks and subtasks, dependencies, team members with their leave, and task assignments — so the project can be reopened any time without a database server (single-user, GanttProject style).',
    'import_file_label' => 'Project file',
    'import_submit' => 'Open File',

    /* Controller messages */
    'created' => 'Project ":name" created. Set up the working calendar and team next.',
    'updated' => 'Project updated.',
    'deleted' => 'Project ":name" and all of its data has been deleted.',
    'imported' => 'Project ":name" opened from file.',
    'import_invalid_json' => 'Invalid file: not readable JSON.',
    'import_invalid_format' => 'File is not a valid Proyekta export.',
    'import_failed' => 'The file could not be opened. Make sure it is an unedited Proyekta export file.',
    'validation_name_required' => 'Project name is required.',
    'validation_start_required' => 'Start date is required.',
    'import_validation_file_required' => 'Choose the project file to open.',
    'import_validation_file_file' => 'The file cannot be read.',
    'import_validation_file_max' => 'Maximum file size is 5 MB.',

];
