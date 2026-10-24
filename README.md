# Proyekta — Manajemen Proyek & Penjadwalan

Aplikasi web untuk mengelola jadwal proyek dengan metode **Critical Path Method (CPM)**: susun tugas berjenjang, atur dependensi, tentukan kalender kerja dan hari libur, lalu lihat jadwalnya sebagai **Gantt chart** dan **diagram PERT**.

Dibangun dengan Laravel 13, Tailwind CSS 4, dan Vite 8. Antarmuka tersedia dalam **Bahasa Indonesia dan English**.

## Fitur

### Proyek
- CRUD proyek (nama, organisasi, deskripsi, tanggal mulai) dengan *soft delete*.
- Halaman ringkasan: total durasi (hari kerja), tanggal mulai, perkiraan selesai, jumlah tugas, dan daftar tugas kritis.
- Simpan seluruh proyek ke satu file JSON, lalu buka kembali kapan saja.

### Tugas
- Struktur berjenjang (tugas induk dan sub-kegiatan) dengan operasi **indent / outdent** serta geser urutan naik/turun.
- Durasi dalam hari kerja; **milestone** adalah tugas dengan durasi `0`.
- Progres (0–100%), prioritas (low / medium / high / critical), dan deskripsi.
- Tugas induk otomatis mengambil tanggal mulai paling awal dan selesai paling akhir dari sub-kegiatannya.
- Tugas dapat ditugaskan ke beberapa anggota tim.

### Dependensi
- Tipe **Finish-to-Start (FS)** dan **Start-to-Start (SS)** dengan **lag** hari kerja; nilai negatif berfungsi sebagai *lead*.
- Banyak predecessor diperbolehkan — tanggal mulai diambil dari batas yang paling akhir.
- Dependensi melingkar ditolak dengan pesan yang menyebutkan rantai tugasnya.

### Kalender kerja & hari libur
- Hari kerja dan akhir pekan dapat diatur per proyek (default Senin–Jumat).
- Hari libur mendukung rentang tanggal, misalnya cuti bersama beberapa hari.
- **Impor template hari libur nasional Indonesia** sekali klik, aman diulang tanpa membuat duplikat: hari libur tetap setiap tahun plus hari besar yang tanggalnya bergerak untuk 2025–2027.

### Visualisasi
- **Gantt chart**: bar per tugas, penanda hari non-kerja, sorotan jalur kritis, dan filter per anggota tim.
- **Diagram PERT**: jaringan ketergantungan dengan ES / EF / LS / LF dan total slack per kegiatan.
- Render dilakukan di sisi klien dari data JSON yang dihasilkan `ScheduleCalculator`.

### Tim
- Anggota tim dengan nama, kontak, peran, dan gaji.
- **Gaji disimpan terenkripsi** di database dan dapat disembunyikan dari tampilan (preferensi per sesi).
- Cuti anggota per rentang tanggal, plus peringatan bila cuti bentrok dengan jadwal tugas yang ditugaskan.

### Lain-lain
- Impor proyek dari file ekspor; file rusak atau format asing ditolak dengan pesan jelas.
- Perpindahan bahasa ID / EN yang disimpan di sesi.
- Endpoint `GET /health` untuk pemeriksaan status aplikasi.

## Cara penjadwalan bekerja

Logika penjadwalan ada di `app/Services/ScheduleCalculator.php` dan `app/Services/WorkCalendar.php`:

1. Semua durasi dihitung dalam **hari kerja efektif** — akhir pekan dan hari libur dilewati.
2. **Forward pass** menentukan Early Start dan Early Finish:
   - FS: mulai = selesai(predecessor) + 1 hari kerja + lag.
   - SS: mulai = mulai(predecessor) + lag.
   - Tugas tanpa predecessor dan tanpa tanggal manual mulai pada tanggal mulai proyek.
3. Tanggal mulai manual tidak boleh lebih awal dari batas dependensinya.
4. **Backward pass** menentukan Late Start, Late Finish, dan total slack; tugas dengan `slack <= 0` ditandai **kritis**.
5. Slack tugas induk = slack terkecil di antara sub-kegiatannya.
6. Dependensi melingkar menghentikan perhitungan dengan `CircularDependencyException`.

## Kebutuhan

- PHP **8.3+** dengan ekstensi standar Laravel (pdo_sqlite, mbstring, openssl, dll.)
- Composer 2
- Node.js 20.19+ dan npm

## Instalasi

```bash
git clone https://github.com/kimchil04/Tugas-Arsitecture.git
cd Tugas-Arsitecture

composer setup
```

`composer setup` menjalankan seluruh langkah awal: `composer install`, menyalin `.env`, membuat application key, migrasi database, `npm install`, dan build aset.

Secara manual, langkahnya sama dengan:

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite   # bila memakai SQLite
php artisan migrate
npm install
npm run build
```

Bawaannya aplikasi memakai **SQLite** (`DB_CONNECTION=sqlite`), jadi tidak perlu menyiapkan server database. Ubah variabel `DB_*` di `.env` bila ingin memakai MySQL atau PostgreSQL. Isi juga `APP_NAME="Proyekta"` agar nama aplikasi tampil benar.

## Menjalankan

```bash
composer dev
```

Satu perintah ini menjalankan tiga proses sekaligus: HTTP server (`http://127.0.0.1:8000`), queue worker, dan Vite dev server untuk hot reload CSS/JS.

Perintah lain yang sering dipakai:

| Perintah | Kegunaan |
| --- | --- |
| `php artisan test` | Menjalankan seluruh test suite |
| `npm run build` | Build aset untuk produksi |
| `vendor/bin/pint` | Merapikan format kode PHP |
| `php artisan migrate:fresh --seed` | Reset database dari nol |

## Struktur proyek

```
app/
├── Casts/EncryptedInteger.php        # cast gaji terenkripsi
├── Http/Controllers/                 # Project, Task, Dependency, Calendar, Holiday, Resource, Visualization, Locale
├── Http/Middleware/SetLocale.php     # bahasa UI dari sesi
├── Models/                           # Project, Task, Dependency, ProjectCalendar, Holiday, Resource, ResourceLeave, TaskAssignment
└── Services/
    ├── WorkCalendar.php              # hari kerja, hari libur, aritmetika hari kerja
    ├── ScheduleCalculator.php        # CPM: forward/backward pass, slack, jalur kritis
    ├── ProjectFileService.php        # ekspor / impor proyek ke JSON
    └── HolidayTemplateService.php    # template libur nasional Indonesia
resources/
├── js/gantt.js                       # render Gantt chart di browser
├── js/pert.js                        # render diagram PERT di browser
├── lang/{en,id}/                     # terjemahan UI
└── views/                            # Blade: projects, tasks, team, calendar, visualizations
```

## Format file proyek

Ekspor menghasilkan JSON dengan penanda format berikut:

```json
{
  "format": "proyekta-project",
  "version": 1,
  "exported_at": "2026-10-24T06:00:00+00:00",
  "project": {
    "name": "Pembangunan Gedung A",
    "organization": "PT Contoh",
    "description": "Proyek percontohan",
    "start_date": "2026-11-02"
  },
  "calendar": { "workdays": [1, 2, 3, 4, 5], "weekend_days": [6, 7] },
  "holidays": [{ "date": "2026-12-25", "description": "Hari Raya Natal" }],
  "tasks": [
    { "id": 1, "parent_id": null, "name": "Persiapan", "start_date": null, "duration": 5, "priority": "high", "sort_order": 0 }
  ],
  "dependencies": [
    { "predecessor_id": 1, "successor_id": 2, "type": "FS", "lag": 0 }
  ],
  "resources": [
    { "id": 1, "name": "Budi", "contact": "budi@example.com", "role": "Site Engineer", "salary": 9000000, "leaves": [] }
  ],
  "assignments": [{ "task_id": 1, "resource_id": 1 }]
}
```

Saat impor, ID tugas dan anggota dipetakan ulang ke ID baru, sehingga file yang sama boleh diimpor berkali-kali tanpa bertabrakan. Impor yang gagal dibatalkan seluruhnya (satu transaksi database).

## Pengujian

```bash
php artisan test
```

Test suite mencakup perhitungan kalender kerja (`tests/Unit/WorkCalendarTest.php`), mesin CPM termasuk jalur kritis dan kasus dependensi melingkar (`tests/Feature/ScheduleCalculatorTest.php`), CRUD proyek dan tugas (`tests/Feature/ProjectManagementTest.php`), serta ekspor/impor file proyek (`tests/Feature/ProjectFileTest.php`).

## Catatan

- Aplikasi ini bersifat **single-user**: belum ada autentikasi maupun pembagian hak akses.
- Gaji anggota disimpan terenkripsi dengan `APP_KEY`, jadi mengganti key membuat nilai lama tidak terbaca.

## Lisensi

Dirilis di bawah lisensi MIT.
