<?php

return [

    /* Pages */
    'index_title' => 'Semua Proyek',
    'index_heading' => 'Daftar Proyek',
    'index_intro' => 'Buat proyek, atur kalender kerja, susun tugas, lalu pantau Gantt, PERT, dan jalur kritisnya.',
    'new_project' => '+ Proyek Baru',
    'open_from_file' => 'Buka dari File',
    'empty' => 'Belum ada proyek.',
    'empty_cta' => 'Buat proyek pertama',

    /* Table */
    'col_name' => 'Nama Proyek',
    'col_organization' => 'Organisasi',
    'col_tasks' => 'Tugas',
    'col_team' => 'Tim',
    'col_holidays' => 'Hari Libur',
    'col_start' => 'Mulai',
    'delete_confirm' => 'Hapus proyek ":name" beserta seluruh tugas, tim, dan jadwalnya?',

    /* Show */
    'subtitle_start' => 'Mulai :date',
    'save_to_file' => 'Simpan ke File',
    'save_to_file_title' => 'Simpan seluruh isi proyek ke file lokal',
    'schedule_error_title' => 'Jadwal tidak dapat dihitung.',
    'schedule_error_fix' => 'Perbaiki di halaman Tugas',
    'total_duration' => 'Durasi Total Proyek',
    'planned_start' => 'Mulai Rencana',
    'predicted_finish' => 'Selesai Terprediksi',
    'critical_tasks' => 'Tugas Kritis',
    'workdays_unit' => 'hari kerja',
    'detail_heading' => 'Detail Proyek',
    'detail_organization' => 'Organisasi',
    'detail_description' => 'Deskripsi',
    'detail_team' => 'Anggota Tim',
    'detail_holidays' => 'Hari Libur Khusus',
    'detail_calendar' => 'Kalender Kerja',
    'calendar_weekend' => 'libur: :days',
    'calendar_default' => 'Senin – Jumat',
    'people_unit' => 'orang',
    'days_unit' => 'hari',
    'critical_heading' => 'Jalur Kritis (Critical Path)',
    'critical_slack_zero' => 'slack = 0',
    'critical_empty_no_tasks' => 'Belum ada tugas. Tambahkan tugas untuk menghitung jalur kritis.',
    'critical_summary_tag' => '(tugas induk)',
    'critical_note' => 'Tugas-tugas ini tidak boleh molor, karena akan membuat seluruh proyek molor. Lihat detailnya pada :link.',
    'pert_link_label' => 'PERT Chart',
    'delete_confirm_summary' => 'Hapus proyek beserta seluruh datanya?',

    /* Create / edit */
    'create_title' => 'Proyek Baru',
    'create_subtitle' => 'Langkah 1 dari alur utama: buat proyek lalu atur kalender kerjanya.',
    'edit_title' => 'Ubah Proyek',
    'save_project' => 'Simpan Proyek',
    'field_name' => 'Nama Proyek',
    'field_organization' => 'Organisasi',
    'field_description' => 'Deskripsi',
    'field_start_date' => 'Tanggal Mulai',

    /* Import */
    'import_title' => 'Buka Proyek dari File',
    'import_subtitle' => 'Pilih file ekspor Proyekta (.json) untuk memuat ulang seluruh isi proyek.',
    'import_intro' => 'File berisi seluruh isi proyek: data proyek, kalender kerja, hari libur, tugas dan sub-kegiatan, ketergantungan, anggota tim beserta cuti, dan alokasi tugas — sehingga proyek dapat dibuka lagi kapan saja tanpa database server (single-user, gaya GanttProject).',
    'import_file_label' => 'File proyek',
    'import_submit' => 'Buka File',

    /* Controller messages */
    'created' => 'Proyek ":name" berhasil dibuat. Silakan atur kalender kerja dan tim.',
    'updated' => 'Proyek berhasil diperbarui.',
    'deleted' => 'Proyek ":name" beserta seluruh datanya telah dihapus.',
    'imported' => 'Proyek ":name" berhasil dibuka dari file.',
    'import_invalid_json' => 'File tidak valid: bukan JSON yang dapat dibaca.',
    'import_invalid_format' => 'File bukan ekspor Proyekta yang sah.',
    'import_failed' => 'File gagal dibuka. Pastikan file merupakan hasil ekspor Proyekta yang tidak teredit.',
    'validation_name_required' => 'Nama proyek wajib diisi.',
    'validation_start_required' => 'Tanggal mulai wajib diisi.',
    'import_validation_file_required' => 'Pilih file proyek yang akan dibuka.',
    'import_validation_file_file' => 'File tidak dapat dibaca.',
    'import_validation_file_max' => 'Ukuran file maksimal 5 MB.',

];
