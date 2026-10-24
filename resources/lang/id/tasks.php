<?php

return [

    /* Pages */
    'index_title' => 'Tugas & Ketergantungan',
    'index_subtitle' => 'Susun tugas, sub-kegiatan, dependensi FS/SS, dan alokasikan anggota tim.',
    'add_task' => '+ Tambah Tugas',
    'add_first_task' => 'Tambah tugas pertama',
    'empty' => 'Belum ada tugas pada proyek ini.',
    'list_heading' => 'Daftar Tugas',
    'stat_main_tasks' => 'tugas utama',
    'stat_subtasks' => 'sub-kegiatan',
    'stat_critical' => 'tugas kritis',
    'recalc_note' => 'Tanggal dihitung ulang otomatis setiap ada perubahan (FR-17, FR-36).',

    /* Circular dependency */
    'circular_title' => 'Jadwal tidak dapat dihitung karena ada dependensi melingkar.',
    'circular_body' => 'Hapus salah satu ketergantungan di bawah untuk memulihkan Gantt, PERT, dan jalur kritis.',

    /* Warnings */
    'warnings_title' => 'Peringatan alokasi & cuti (FR-23 / FR-24)',

    /* Filter */
    'filter_label' => 'Filter per anggota',
    'filter_all' => 'Semua anggota',
    'filter_hint' => 'Difilter ke tugas yang ditugaskan pada anggota terpilih.',

    /* Columns */
    'col_task' => 'Tugas',
    'col_schedule' => 'Jadwal',
    'col_duration' => 'Durasi',
    'col_slack' => 'Slack',
    'col_assignees' => 'Penanggung Jawab',
    'col_percent' => 'Progres',

    /* Row */
    'toggle_subtasks' => 'Tampilkan atau sembunyikan sub-kegiatan',
    'badge_parent' => 'Induk',
    'badge_milestone' => 'Milestone',
    'badge_critical' => 'Kritis',
    'badge_summary' => 'Tugas induk',
    'subtask_count' => ':count sub-kegiatan',
    'type_subtask' => 'Sub-kegiatan',
    'type_main_task' => 'Tugas utama',
    'unassigned' => 'Belum ditugaskan',
    'to_date' => 's/d :date',
    'duration_days' => ':count hari',
    'slack_days' => ':count hari',
    'slack_title' => 'Bisa molor :count hari kerja tanpa memengaruhi proyek',
    'critical_title' => 'Slack 0: tugas kritis',
    'progress_label' => ':percent% selesai',
    'predecessor_label' => 'Predecessor',

    /* Row actions */
    'action_add_sub' => '+ Sub',
    'action_add_sub_title' => 'Tambah sub-kegiatan',
    'action_move_up' => 'Naikkan',
    'action_move_down' => 'Turunkan',
    'action_indent' => 'Jadikan sub-kegiatan',
    'action_outdent' => 'Jadikan tugas utama',
    'delete_confirm' => 'Hapus tugas ":name"?',
    'delete_confirm_summary' => 'Hapus tugas ":name" beserta seluruh sub-kegiatannya?',

    /* Keyboard hint */
    'keyboard_hint' => 'Papan ketik: ↑ ↓ pindah baris · ← → pindah kolom · Enter atau Spasi membuka editor · Delete menghapus tugas.',

    /* Create / edit */
    'create_title' => 'Tugas Baru',
    'create_sub_title' => 'Sub-Kegiatan Baru',
    'edit_title' => 'Ubah Tugas',
    'save_task' => 'Simpan Tugas',
    'field_name' => 'Nama Tugas',
    'field_name_placeholder' => 'Contoh: Analisis kebutuhan',
    'field_parent' => 'Tugas Induk',
    'parent_none' => '— Tugas utama (bukan sub-kegiatan) —',
    'parent_option_suffix' => '(induk)',
    'field_start' => 'Tanggal Mulai',
    'field_start_hint' => 'Kosongkan untuk mengikuti ketergantungan / tanggal mulai proyek.',
    'field_duration' => 'Durasi (hari kerja)',
    'field_duration_hint' => 'Isi 0 untuk menandai milestone (titik belah ketupat ◆ tanpa durasi).',
    'field_percent' => 'Persentase Selesai (0–100)',
    'field_description' => 'Deskripsi',
    'field_priority' => 'Prioritas',
    'priority_low' => 'Rendah',
    'priority_medium' => 'Sedang',
    'priority_high' => 'Tinggi',
    'priority_critical' => 'Kritis',
    'field_assignees' => 'Anggota Tim yang Ditugaskan',
    'assignees_empty' => 'Belum ada anggota tim. Tambahkan dulu di halaman :link.',
    'assignees_link' => 'Tim & Cuti',
    'duration_note' => 'Durasi dihitung dalam hari kerja efektif (akhir pekan dan hari libur dilewati). Tugas induk otomatis mengambil tanggal mulai terawal dan selesai terakhir dari sub-kegiatannya (FR-06, FR-10).',

    /* Dependencies panel */
    'dependencies_heading' => 'Ketergantungan (Predecessor)',
    'dependencies_intro' => 'FS: mulai setelah predecessor selesai. SS: mulai bersamaan. Lag dalam hari kerja (nilai negatif berarti lead).',
    'dependency_fs_label' => 'Finish-to-Start',
    'dependency_ss_label' => 'Start-to-Start',
    'dependency_lag_days' => 'lag :count hari',
    'dependency_deleted_task' => 'Tugas terhapus',
    'dependency_empty' => 'Belum ada predecessor.',
    'dependency_delete_confirm' => 'Hapus ketergantungan ini? Jadwal akan dihitung ulang.',
    'dependency_predecessor_label' => 'Tugas Pemasok',
    'dependency_pick' => '— Pilih tugas —',
    'dependency_type_label' => 'Tipe',
    'dependency_lag_label' => 'Lag (hari)',
    'dependency_add' => 'Tambah Ketergantungan',
    'schedule_heading' => 'Jadwal Terekalkulasi',
    'schedule_intro' => 'Buka :link untuk melihat tanggal, slack, dan status jalur kritis terbaru setelah perubahan ini.',
    'schedule_link' => 'halaman tugas',

    /* Controller messages */
    'created' => 'Tugas ":name" berhasil ditambahkan. Jadwal dan jalur kritis dihitung ulang otomatis.',
    'updated' => 'Tugas ":name" berhasil diperbarui.',
    'deleted' => 'Tugas ":name" dihapus.',
    'deleted_with_children' => 'Tugas ":name" dihapus. :count sub-kegiatan ikut terhapus.',
    'moved_up' => 'Tugas ":name" dipindahkan ke atas.',
    'moved_down' => 'Tugas ":name" dipindahkan ke bawah.',
    'move_blocked_first' => 'Tugas ":name" sudah berada di urutan paling atas.',
    'move_blocked_last' => 'Tugas ":name" sudah berada di urutan paling bawah.',
    'indented' => 'Tugas ":name" sekarang menjadi sub-kegiatan dari ":parent".',
    'indent_blocked' => 'Tugas ":name" tidak dapat di-indent lebih dalam.',
    'outdented' => 'Tugas ":name" dipromosikan menjadi tugas utama.',
    'outdent_blocked' => 'Tugas ":name" sudah merupakan tugas utama.',
    'validation_duration_min' => 'Durasi minimal 0 hari kerja (0 = milestone).',
    'validation_duration_max' => 'Durasi maksimal 999 hari kerja.',
    'validation_percent_range' => 'Persentase selesai harus antara 0 dan 100.',
    'validation_parent_missing' => 'Tugas induk tidak ditemukan dalam proyek ini.',
    'validation_parent_cycle' => 'Sub-kegiatan tidak boleh dijadikan induk dari tugas induknya sendiri.',

    /* Dependency controller */
    'dependency_stored' => 'Ketergantungan tugas tersimpan. Jadwal dihitung ulang otomatis.',
    'dependency_removed' => 'Ketergantungan dihapus. Jadwal dihitung ulang otomatis.',
    'dependency_self' => 'Sebuah tugas tidak boleh bergantung pada dirinya sendiri.',
    'dependency_exists' => 'Dependensi tersebut sudah terdaftar.',
    'validation_predecessor_exists' => 'Predecessor harus berasal dari tugas dalam proyek ini.',
    'validation_successor_exists' => 'Successor harus berasal dari tugas dalam proyek ini.',
    'validation_type_in' => 'Tipe dependensi hanya FS (Finish-to-Start) atau SS (Start-to-Start).',

];
