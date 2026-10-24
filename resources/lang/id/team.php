<?php

return [

    /* Pages */
    'index_title' => 'Tim & Cuti',
    'index_subtitle' => 'Data anggota tim, gaji, dan cuti pribadi yang memengaruhi peringatan alokasi (FR-18 s/d FR-24).',
    'add_member' => '+ Tambah Anggota',
    'add_first_member' => 'Tambah anggota pertama',
    'empty' => 'Belum ada anggota tim pada proyek ini.',
    'empty_leave' => 'Tidak ada cuti tercatat.',
    'no_role' => 'Tanpa peran',
    'no_contact' => 'Tanpa kontak',

    /* Member card */
    'assigned_count' => 'Ditugaskan pada',
    'assigned_count_unit' => 'tugas',
    'salary_label' => 'Gaji',
    'salary_per_month' => '/bulan',
    'salary_hidden' => 'Rp ••••••••',
    'hide_salary' => 'Sembunyikan gaji',
    'show_salary' => 'Tampilkan gaji',
    'salary_encrypted_note' => 'Gaji disimpan terenkripsi dan tidak pernah muncul di tampilan Gantt maupun PERT (RES-4).',
    'delete_confirm' => 'Hapus anggota tim ":name"?',
    'warning_assigned' => 'Anggota ini masih ditugaskan pada :count tugas. Nama mereka akan hilang dari tugas-tugas tersebut.',

    /* Leave */
    'leave_heading' => 'Cuti / Hari Libur Pribadi',
    'leave_start' => 'Mulai',
    'leave_end' => 'Selesai',
    'leave_description' => 'Keterangan',
    'leave_description_placeholder' => 'Cuti tahunan',
    'leave_record' => 'Catat',
    'leave_delete_confirm' => 'Hapus cuti ini?',
    'leave_range' => ':from – :to',
    'leave_note_prefix' => '· :note',

    /* Create / edit */
    'create_title' => 'Tambah Anggota Tim',
    'edit_title' => 'Ubah Anggota Tim',
    'save_member' => 'Simpan Anggota',
    'field_name' => 'Nama',
    'field_contact' => 'Kontak (email / telepon)',
    'field_role' => 'Peran',
    'field_salary' => 'Gaji (Rp, per bulan)',
    'field_salary_hint' => 'Disimpan terenkripsi; hanya disimpan, tidak pernah dipakai untuk hitungan biaya.',

    /* Controller messages */
    'created' => 'Anggota tim ":name" berhasil ditambahkan.',
    'updated' => 'Data ":name" berhasil diperbarui.',
    'deleted' => 'Anggota tim ":name" dihapus beserta cuti dan alokasinya.',
    'leave_recorded' => 'Cuti :name (:range) tercatat.',
    'leave_deleted' => 'Cuti dihapus.',
    'salary_shown' => 'Gaji ditampilkan kembali di halaman tim.',
    'salary_hidden_flash' => 'Gaji disembunyikan di halaman tim.',
    'validation_name_required' => 'Nama anggota tim wajib diisi.',
    'validation_salary_integer' => 'Gaji harus berupa angka bulat tanpa titik atau koma.',

    /* Leave controller */
    'leave_start_required' => 'Tanggal mulai cuti wajib diisi.',
    'leave_end_after' => 'Tanggal selesai cuti tidak boleh sebelum tanggal mulai.',

];
