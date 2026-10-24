<?php

return [

    /* Pages */
    'index_title' => 'Team & Leave',
    'index_subtitle' => 'Team member data, salary, and personal leave that drive allocation warnings (FR-18 to FR-24).',
    'add_member' => '+ Add Member',
    'add_first_member' => 'Add the first member',
    'empty' => 'No team members in this project yet.',
    'empty_leave' => 'No leave recorded.',
    'no_role' => 'No role',
    'no_contact' => 'No contact',

    /* Member card */
    'assigned_count' => 'Assigned to',
    'assigned_count_unit' => 'tasks',
    'salary_label' => 'Salary',
    'salary_per_month' => '/month',
    'salary_hidden' => 'Rp ••••••••',
    'hide_salary' => 'Hide salary',
    'show_salary' => 'Show salary',
    'salary_encrypted_note' => 'Salary is stored encrypted at-rest and never appears in Gantt or PERT views (RES-4).',
    'delete_confirm' => 'Delete team member ":name"?',
    'warning_assigned' => 'This member is still assigned to :count task(s). Their name will disappear from those tasks.',

    /* Leave */
    'leave_heading' => 'Leave / Personal Holiday',
    'leave_start' => 'From',
    'leave_end' => 'To',
    'leave_description' => 'Note',
    'leave_description_placeholder' => 'Annual leave',
    'leave_record' => 'Record',
    'leave_delete_confirm' => 'Delete this leave?',
    'leave_range' => ':from – :to',
    'leave_note_prefix' => '· :note',

    /* Create / edit */
    'create_title' => 'Add Team Member',
    'edit_title' => 'Edit Team Member',
    'save_member' => 'Save Member',
    'field_name' => 'Name',
    'field_contact' => 'Contact (email / phone)',
    'field_role' => 'Role',
    'field_salary' => 'Salary (IDR, monthly)',
    'field_salary_hint' => 'Stored encrypted; only saved, never used for cost calculations.',

    /* Controller messages */
    'created' => 'Team member ":name" added.',
    'updated' => 'Data ":name" updated.',
    'deleted' => 'Team member ":name" deleted along with their leave and allocations.',
    'leave_recorded' => 'Leave for :name (:range) recorded.',
    'leave_deleted' => 'Leave deleted.',
    'salary_shown' => 'Salary is now shown on the team page.',
    'salary_hidden_flash' => 'Salary is now hidden on the team page.',
    'validation_name_required' => 'Team member name is required.',
    'validation_salary_integer' => 'Salary must be a whole number without dots or commas.',

    /* Leave controller */
    'leave_start_required' => 'Leave start date is required.',
    'leave_end_after' => 'Leave end date cannot be before the start date.',

];
