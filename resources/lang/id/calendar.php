<?php

return [

    /* Pages */
    'index_title' => 'Kalender Kerja',
    'index_subtitle' => 'Hari kerja, akhir pekan, dan hari libur khusus menentukan seluruh perhitungan durasi (FR-06).',

    /* Week settings */
    'week_heading' => 'Pengaturan Minggu',
    'week_intro' => 'Tandai hari yang merupakan hari kerja, lalu tandai hari akhir pekan. Bila keduanya bertindihan, hari libur akhir pekan yang menang.',
    'workdays_label' => 'Hari Kerja',
    'weekend_label' => 'Akhir Pekan (Non-Kerja)',
    'save_calendar' => 'Simpan Kalender',
    'save_note' => 'Perubahan langsung menghitung ulang jadwal.',
    'days' => 'hari',
    'weekend_summary' => 'Akhir pekan: :days',

    /* Holidays */
    'holidays_heading' => 'Hari Libur Khusus / Nasional',
    'holiday_date' => 'Tanggal',
    'holiday_end_date' => 'Tanggal Selesai (rentang opsional)',
    'holiday_end_hint' => 'Isi bila rentang hari libur lebih dari satu hari.',
    'holiday_description' => 'Keterangan',
    'holiday_description_placeholder' => 'Contoh: Hari Kemerdekaan',
    'holiday_add' => 'Tambah',
    'holiday_col_date' => 'Tanggal',
    'holiday_col_description' => 'Keterangan',
    'holiday_range' => ':from – :to',
    'holiday_empty' => 'Belum ada hari libur khusus.',
    'holiday_delete_confirm' => 'Hapus hari libur ini?',
    'holiday_note' => 'Hari libur khusus tidak dihitung sebagai hari kerja, sehingga durasi dan jalur kritis menyesuaikan otomatis.',

    /* Template import */
    'template_import' => 'Import template hari libur nasional Indonesia',
    'template_import_note' => 'Satu klik menambahkan hari libur nasional untuk tahun proyek. Import ulang tidak pernah membuat duplikat (CAL-4).',
    'template_imported' => ':count hari libur nasional ditambahkan untuk :year. :skipped duplikat dilewati.',
    'template_none_new' => 'Seluruh hari libur nasional untuk :year sudah ada dalam daftar.',

    /* Day names (index 0 = Monday) */
    'day_names' => ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'],

    /* Controller messages */
    'updated' => 'Kalender kerja diperbarui. Hari kerja: :workdays. Akhir pekan: :weekend.',
    'workdays_required' => 'Pilih minimal satu hari kerja.',
    'holiday_created' => 'Hari libur :date berhasil ditambahkan.',
    'holiday_updated' => 'Hari libur berhasil diperbarui.',
    'holiday_deleted' => 'Hari libur dihapus. Jadwal dihitung ulang otomatis.',
    'holiday_date_required' => 'Tanggal hari libur wajib diisi.',
    'holiday_end_after' => 'Tanggal selesai hari libur tidak boleh sebelum tanggal mulai.',

];
