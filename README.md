# Dokumentasi Sistem e-Pengaduan Kabupaten Sukoharjo

Dokumentasi ringkas mengenai identitas sistem, tujuan pengembangan, fitur utama, dan perancangan basis data relasional aplikasi **e-Pengaduan Kabupaten Sukoharjo**.

---

## 1. Identitas & Nama Aplikasi

* **Nama Resmi Aplikasi:** **e-Pengaduan Kabupaten Sukoharjo** *(Lapor Sukoharjo)*
* **Sub-Judul / Domain:** Sistem Layanan Aspirasi dan Pengaduan Online Rakyat Pemerintah Kabupaten Sukoharjo
* **Konteks Proyek:** Ujian Tengah Semester (UTS) Pemrograman Web 2 — Tema 13 (Sistem Pengaduan Masyarakat)
* **Instansi Studi Kasus:** Pemerintah Kabupaten Sukoharjo (Dinas Komunikasi dan Informatika & OPD Teknis)
* **Tech Stack:**
  * **Backend:** Modular Native PHP (8.x) dengan ekstensi `mysqli` (Prepared Statements)
  * **Basis Data:** MySQL / MariaDB (XAMPP Environment)
  * **Frontend & Desain:** Tailwind CSS CDN (Awesomic Design System, Zinc-Scale Palette, Font DM Sans)
  * **Peta Interaktif:** Leaflet.js Geospasial (OpenStreetMap Tiles)
  * **Ikon:** Lucide Icons

---

## 2. Tujuan Aplikasi

1. **Kanal Pengaduan Publik Terpadu:** Menyediakan platform digital terpusat bagi masyarakat di 12 kecamatan Kabupaten Sukoharjo (Sukoharjo, Kartasura, Grogol, Baki, Mojolaban, Polokarto, Bendosari, Nguter, Tawangsari, Weru, Bulu, dan Gatak) untuk melaporkan kerusakan infrastruktur, gangguan ketertiban umum, dan permasalahan fasilitas publik secara cepat dan mudah.
2. **Disposisi Cepat Berbasis SLA (Service Level Agreement):** Menghubungkan aduan warga langsung ke dinas/OPD penanggung jawab (Dinas PUPR, Dishub, DLH, Satpol PP, Disdukcapil) dengan batasan waktu penanganan terukur (1–5 hari kerja).
3. **Transparansi & Akuntabilitas:** Memberikan kepastian pelacakan progres tindak lanjut lapangan secara *real-time* kepada pelapor dan masyarakat umum melalui nomor tiket/kode tracking unik tanpa birokrasi berbelit.
4. **Perlindungan Pelapor (Whistleblower Protection):** Menjamin privasi dan keamanan identitas warga pelapor melalui opsi pelaporan anonim.
5. **Pemenuhan Rubrik Penilaian Akademik:** Mengimplementasikan standar pemrograman web yang aman (mencegah SQL Injection dan XSS), operasi CRUD menyeluruh, autentikasi berbasis peran (RBAC), serta pembuatan rekapitulasi laporan tercetak resmi.

---

## 3. Fitur Utama Aplikasi

### A. Fitur Publik & Pengguna Umum
1. **Public Live Tracking (Lacak Tanpa Login):**
   * Pelacakan instan progres status penanganan laporan cukup dengan memasukkan Kode Tracking (contoh: `SKH-2026-0001`) langsung dari halaman muka (*portal login*).
2. **Dukungan Aspirasi (Upvote System):**
   * Publik/warga dapat memberikan tanda dukungan (*"Dukung Laporan"*) pada laporan aduan untuk meningkatkan prioritas penanganan masalah bersama.

### B. Fitur Masyarakat / Pelapor (Role: Warga)
3. **Pendaftaran & Autentikasi Akun Warga:**
   * Registrasi mandiri dengan validasi format NIK 16 digit angka, domisili kecamatan Sukoharjo, kontak aktif, dan enkripsi kata sandi Bcrypt.
4. **Formulir Pengaduan dengan GIS Pin Picker & Upload Foto:**
   * Pemilihan kategori masalah dan penentuan titik koordinat kejadian menggunakan peta interaktif Leaflet.js (*draggable marker*).
   * Validasi unggah berkas foto bukti awal (*Before*, ekstensi JPG/PNG/WEBP, maksimal 2 MB).
   * Fitur *Autosave Draft* (`localStorage`) yang otomatis mengamankan ketikan uraian laporan agar tidak hilang.
5. **Mode Pelaporan Anonim (Whistleblower Protection):**
   * Opsi menyamarkan identitas nama pelapor di hadapan publik menjadi *"Disamarkan (Anonim)"*.
6. **Survei Kepuasan Masyarakat (SKM):**
   * Formulir pemberian evaluasi skor 1–5 bintang beserta ulasan testimoni setelah laporan dinyatakan *Selesai* oleh dinas terkait.

### C. Fitur Dashboard, Monitoring & Petugas (Role: Admin)
7. **Dashboard Geospasial & Metrik Awesomic:**
   * Ringkasan 4 kartu statistik (*Total Aduan, Menunggu Verifikasi [Aksen Ember Orange #ff5a00], Dalam Proses, dan Selesai Ditangani*).
   * Peta sebaran spasial 12 kecamatan Sukoharjo berkoordinat `-7.6828, 110.8407` dengan penanda titik berwarna sesuai status penanganan.
   * *Dark Card* ikhtisar standar SLA dan tabel 5 laporan terbaru.
8. **Manajemen Data Pengaduan (Read, Search, Filter & Delete):**
   * Tabel pengaduan dengan pencarian kata kunci, filter dropdown kategori, filter dropdown status, dan pemisahan tab *"Semua Laporan"* vs *"Aduan Saya"*.
   * Modal dialog konfirmasi hapus kustom berbasis JavaScript.
9. **Linimasa Penanganan & Komparasi Foto Before/After:**
   * Penyajian *audit trail* vertikal dari tabel `tindak_lanjut` serta komparasi foto kondisi awal warga (*Before*) bersandingan dengan foto hasil perbaikan petugas (*After*).
10. **Panel Disposisi & Verifikasi Tindak Lanjut (Admin OPD):**
    * Verifikasi berkas, pembaruan status laporan (*Verifikasi*, *Diproses*, *Selesai*, *Ditolak*), pengisian catatan dinas, dan pengunggahan bukti perbaikan tuntas.
11. **Master Data CRUD Kategori & Masyarakat:**
    * Pengelolaan master klasifikasi aduan, instansi tujuan, dan target hari SLA.
    * Pengelolaan basis data kependudukan pelapor dan pengaturan peran akun.
12. **Rekapitulasi & Cetak Laporan Resmi (Print / PDF):**
    * Penyaringan data berdasarkan rentang tanggal, kategori, dan kecamatan.
    * Tata letak cetak formal terintegrasi `@media print` lengkap dengan **Kop Surat Resmi Pemerintah Kabupaten Sukoharjo** dan **Lembar Pengesahan Pejabat**.

---

## 4. Struktur Basis Data (`db_pengaduan_sukoharjo`)

Basis data dirancang menggunakan 4 entitas relasional berintegritas tinggi dengan relasi *Primary Key* (PK) dan *Foreign Key* (FK):

```
+-------------------+             +-----------------------+
|       users       | 1         * |       pengaduan       |
|-------------------|-------------|-----------------------|
| id_user (PK)      |             | id_pengaduan (PK)     |
| nik (Unique)      |             | kode_tracking (Unique)|
| username (Unique) |             | id_user (FK)          |
| password          |             | id_kategori (FK)      |
| role              |             | status                |
+-------------------+             | ...                   |
        | 1                       +-----------------------+
        |                                     | 1
        |                                     |
        | *                                   | *
+-------------------+             +-----------------------+
|   tindak_lanjut   |             |       kategori        |
|-------------------|             |-----------------------|
| id_tl (PK)        |             | id_kategori (PK)      |
| id_pengaduan (FK) |             | nama_kategori         |
| id_petugas (FK)   |             | instansi_tujuan       |
| catatan_petugas   |             | target_sla_hari       |
+-------------------+             +-----------------------+
```

### Rincian Tabel dan Kolom

#### 1. Tabel `users` (Data Akun Warga & Petugas Dinas)
*Menyimpan data identitas kependudukan masyarakat dan akun petugas verifikator.*
* `id_user` (INT, Primary Key, Auto Increment) — Pengenal unik pengguna.
* `nik` (VARCHAR(16), Unique, Not Null) — Nomor Induk Kependudukan 16 digit.
* `nama_lengkap` (VARCHAR(100), Not Null) — Nama lengkap sesuai KTP.
* `username` (VARCHAR(50), Unique, Not Null) — Username untuk login.
* `email` (VARCHAR(100), Not Null) — Alamat surat elektronik aktif.
* `telepon` (VARCHAR(20), Not Null) — Nomor telepon seluler / WhatsApp.
* `kecamatan` (VARCHAR(100), Default 'Sukoharjo') — Domisili kecamatan di Kabupaten Sukoharjo.
* `password` (VARCHAR(255), Not Null) — Hash kata sandi terenkripsi (Bcrypt).
* `role` (ENUM('admin', 'warga'), Default 'warga') — Peran akses sistem.
* `created_at` (DATETIME, Default CURRENT_TIMESTAMP) — Waktu akun terdaftar.

#### 2. Tabel `kategori` (Master Klasifikasi Masalah & OPD)
*Menyimpan klasifikasi laporan, dinas penanggung jawab, serta target SLA.*
* `id_kategori` (INT, Primary Key, Auto Increment) — Pengenal unik kategori.
* `nama_kategori` (VARCHAR(100), Not Null) — Nama kategori masalah (contoh: *Infrastruktur Jalan & Jembatan*).
* `instansi_tujuan` (VARCHAR(100), Not Null) — OPD pelaksana (contoh: *Dinas PUPR Kab. Sukoharjo*).
* `target_sla_hari` (INT, Default 3) — Batas waktu pengerjaan maksimal dalam hari.
* `deskripsi` (TEXT, Nullable) — Uraian batasan ruang lingkup kategori.

#### 3. Tabel `pengaduan` (Transaksi Utama Pengaduan Warga)
*Menyimpan rincian data laporan yang diajukan oleh masyarakat.*
* `id_pengaduan` (INT, Primary Key, Auto Increment) — Pengenal unik pengaduan.
* `kode_tracking` (VARCHAR(20), Unique, Not Null) — Nomor tiket resmi (format: `SKH-YYYY-XXXX`).
* `id_user` (INT, Foreign Key -> `users.id_user`) — Referensi warga pelapor.
* `id_kategori` (INT, Foreign Key -> `kategori.id_kategori`) — Referensi kategori aduan.
* `judul_laporan` (VARCHAR(200), Not Null) — Judul ringkas keluhan warga.
* `isi_laporan` (TEXT, Not Null) — Deskripsi kronologi lengkap permasalahan.
* `kecamatan_lokasi` (VARCHAR(100), Not Null) — Titik wilayah kecamatan kejadian.
* `alamat_detail` (VARCHAR(255), Not Null) — Patokan dan alamat terperinci di lapangan.
* `latitude` (DECIMAL(10, 7), Default -7.6828000) — Koordinat garis lintang peta GIS.
* `longitude` (DECIMAL(10, 7), Default 110.8407000) — Koordinat garis bujur peta GIS.
* `foto_bukti_awal` (VARCHAR(255), Not Null) — Nama berkas foto kondisi awal (*Before*).
* `is_anonim` (TINYINT(1), Default 0) — Penanda privasi (1 = Anonim, 0 = Tampilkan Nama).
* `status` (ENUM('Baru', 'Verifikasi', 'Diproses', 'Selesai', 'Ditolak'), Default 'Baru') — Status tahapan aduan.
* `jumlah_dukungan` (INT, Default 1) — Akumulasi upvote dukungan masyarakat.
* `rating_kepuasan` (INT, Nullable) — Skor Survei Kepuasan Masyarakat (1–5 bintang).
* `ulasan_warga` (TEXT, Nullable) — Testimoni atau ulasan pelapor setelah tuntas.
* `tanggal_lapor` (DATETIME, Default CURRENT_TIMESTAMP) — Waktu pelaporan dikirim.

#### 4. Tabel `tindak_lanjut` (Histori Penanganan & Bukti Hasil)
*Menyimpan audit trail proses disposisi dan dokumentasi penyelesaian oleh dinas.*
* `id_tindak_lanjut` (INT, Primary Key, Auto Increment) — Pengenal unik entri histori.
* `id_pengaduan` (INT, Foreign Key -> `pengaduan.id_pengaduan`) — Relasi ke laporan terkait.
* `id_petugas` (INT, Foreign Key -> `users.id_user`) — Petugas/operator yang mengeksekusi aksi.
* `status_tindak_lanjut` (ENUM('Verifikasi', 'Diproses', 'Selesai', 'Ditolak')) — Status pembaruan.
* `catatan_petugas` (TEXT, Not Null) — Catatan resmi penjelasan tindakan lapangan dinas.
* `foto_bukti_selesai` (VARCHAR(255), Nullable) — Nama berkas foto hasil perbaikan (*After*).
* `tanggal_tindak_lanjut` (DATETIME, Default CURRENT_TIMESTAMP) — Waktu aksi tercatat.

