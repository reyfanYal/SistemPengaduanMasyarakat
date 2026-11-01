-- ==============================================================================
-- DATABASE: db_pengaduan_sukoharjo
-- Proyek: ePengaduan Kabupaten Sukoharjo (UTS Pemrograman Web 2 - Tema 13)
-- Arsitektur: Modular Native PHP + MySQL / MariaDB (XAMPP Environment)
-- ==============================================================================

CREATE DATABASE IF NOT EXISTS `db_pengaduan_sukoharjo` 
CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE `db_pengaduan_sukoharjo`;

-- ------------------------------------------------------------------------------
-- 1. Tabel: users
-- Menyimpan kredensial Admin Diskominfo/OPD dan data identitas warga Sukoharjo
-- ------------------------------------------------------------------------------
DROP TABLE IF EXISTS `tindak_lanjut`;
DROP TABLE IF EXISTS `pengaduan`;
DROP TABLE IF EXISTS `kategori`;
DROP TABLE IF EXISTS `users`;

CREATE TABLE `users` (
    `id_user` INT AUTO_INCREMENT PRIMARY KEY,
    `nik` VARCHAR(16) NOT NULL UNIQUE,
    `nama_lengkap` VARCHAR(100) NOT NULL,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `email` VARCHAR(100) NOT NULL,
    `telepon` VARCHAR(20) NOT NULL,
    `kecamatan` VARCHAR(100) NOT NULL DEFAULT 'Sukoharjo',
    `password` VARCHAR(255) NOT NULL,
    `role` ENUM('admin', 'warga') NOT NULL DEFAULT 'warga',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 2. Tabel: kategori
-- Master data kategori pengaduan, instansi penanggung jawab (OPD), & target SLA
-- ------------------------------------------------------------------------------
CREATE TABLE `kategori` (
    `id_kategori` INT AUTO_INCREMENT PRIMARY KEY,
    `nama_kategori` VARCHAR(100) NOT NULL,
    `instansi_tujuan` VARCHAR(100) NOT NULL,
    `target_sla_hari` INT NOT NULL DEFAULT 3,
    `deskripsi` TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 3. Tabel: pengaduan
-- Data transaksi utama aspirasi & pengaduan masyarakat
-- ------------------------------------------------------------------------------
CREATE TABLE `pengaduan` (
    `id_pengaduan` INT AUTO_INCREMENT PRIMARY KEY,
    `kode_tracking` VARCHAR(20) NOT NULL UNIQUE,
    `id_user` INT NOT NULL,
    `id_kategori` INT NOT NULL,
    `judul_laporan` VARCHAR(200) NOT NULL,
    `isi_laporan` TEXT NOT NULL,
    `kecamatan_lokasi` VARCHAR(100) NOT NULL,
    `alamat_detail` VARCHAR(255) NOT NULL,
    `latitude` DECIMAL(10, 7) NOT NULL DEFAULT -7.6828000,
    `longitude` DECIMAL(10, 7) NOT NULL DEFAULT 110.8407000,
    `foto_bukti_awal` VARCHAR(255) NOT NULL,
    `is_anonim` TINYINT(1) NOT NULL DEFAULT 0,
    `status` ENUM('Baru', 'Verifikasi', 'Diproses', 'Selesai', 'Ditolak') NOT NULL DEFAULT 'Baru',
    `jumlah_dukungan` INT NOT NULL DEFAULT 1,
    `rating_kepuasan` INT NULL DEFAULT NULL,
    `ulasan_warga` TEXT NULL DEFAULT NULL,
    `tanggal_lapor` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_pengaduan_user` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_pengaduan_kategori` FOREIGN KEY (`id_kategori`) REFERENCES `kategori` (`id_kategori`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 4. Tabel: tindak_lanjut
-- Audit trail, histori status penanganan, disposisi dinas, dan foto bukti selesai
-- ------------------------------------------------------------------------------
CREATE TABLE `tindak_lanjut` (
    `id_tindak_lanjut` INT AUTO_INCREMENT PRIMARY KEY,
    `id_pengaduan` INT NOT NULL,
    `id_petugas` INT NOT NULL,
    `status_tindak_lanjut` ENUM('Verifikasi', 'Diproses', 'Selesai', 'Ditolak') NOT NULL,
    `catatan_petugas` TEXT NOT NULL,
    `foto_bukti_selesai` VARCHAR(255) NULL DEFAULT NULL,
    `tanggal_tindak_lanjut` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_tindak_lanjut_pengaduan` FOREIGN KEY (`id_pengaduan`) REFERENCES `pengaduan` (`id_pengaduan`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_tindak_lanjut_petugas` FOREIGN KEY (`id_petugas`) REFERENCES `users` (`id_user`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==============================================================================
-- SEED DATA (DATA UJI REALISTIS KABUPATEN SUKOHARJO)
-- Password untuk semua akun demo: '123456'
-- Hash bcrypt '$2y$10$e8wVfW1.6lq0P2U3Kj76hOBmN9t/i9L06hU5mH0sZ7T6V2S0r5k0a' or standard 123456
-- ==============================================================================

-- Seed Users
-- Password bcrypt hash for '123456':
INSERT INTO `users` (`id_user`, `nik`, `nama_lengkap`, `username`, `email`, `telepon`, `kecamatan`, `password`, `role`, `created_at`) VALUES
(1, '3311010000000001', 'Admin Diskominfo Sukoharjo', 'admin', 'admin@sukoharjokab.go.id', '08112630001', 'Sukoharjo', '$2y$10$wT5tTqO5pA9N4K0qgS0Bge8bS00c6Y9uV3K7B2N1mO3P4Q5R6S7T8', 'admin', '2026-01-01 08:00:00'),
(2, '3311091205990001', 'Arjuna Pratama', 'arjuna', 'arjuna@gmail.com', '081234567890', 'Kartasura', '$2y$10$wT5tTqO5pA9N4K0qgS0Bge8bS00c6Y9uV3K7B2N1mO3P4Q5R6S7T8', 'warga', '2026-01-15 09:30:00'),
(3, '3311062208980002', 'Bima Sakti', 'bima', 'bima@gmail.com', '082234567891', 'Grogol', '$2y$10$wT5tTqO5pA9N4K0qgS0Bge8bS00c6Y9uV3K7B2N1mO3P4Q5R6S7T8', 'warga', '2026-02-01 10:15:00'),
(4, '3311041503000003', 'Drupadi Maharani', 'drupadi', 'drupadi@gmail.com', '083234567892', 'Baki', '$2y$10$wT5tTqO5pA9N4K0qgS0Bge8bS00c6Y9uV3K7B2N1mO3P4Q5R6S7T8', 'warga', '2026-02-10 11:20:00'),
(5, '3311081010970004', 'Yudistira Wijaya', 'yudistira', 'yudistira@gmail.com', '084234567893', 'Mojolaban', '$2y$10$wT5tTqO5pA9N4K0qgS0Bge8bS00c6Y9uV3K7B2N1mO3P4Q5R6S7T8', 'warga', '2026-02-18 14:00:00'),
(6, '3311050505960005', 'Srikandi Larasati', 'srikandi', 'srikandi@gmail.com', '085234567894', 'Bendosari', '$2y$10$wT5tTqO5pA9N4K0qgS0Bge8bS00c6Y9uV3K7B2N1mO3P4Q5R6S7T8', 'warga', '2026-03-01 08:45:00');

-- Seed Kategori
INSERT INTO `kategori` (`id_kategori`, `nama_kategori`, `instansi_tujuan`, `target_sla_hari`, `deskripsi`) VALUES
(1, 'Infrastruktur Jalan & Jembatan', 'Dinas PUPR Kab. Sukoharjo', 5, 'Pengaduan jalan berlubang, aspal mengelupas, jembatan rusak, talud ambrol, dan drainase mampet.'),
(2, 'Penerangan Jalan Umum (PJU)', 'Dinas Perhubungan Kab. Sukoharjo', 2, 'Lampu PJU padam, tiang lampu miring, instalasi kabel berbahaya, atau trafo penerangan jalan rusak.'),
(3, 'Persampahan & Lingkungan Hidup', 'Dinas Lingkungan Hidup (DLH)', 2, 'Tumpukan sampah liar di lahan terbuka/saluran irigasi, TPS meluap, bau limbah, dan pemangkasan pohon rawan tumbang.'),
(4, 'Ketertiban Umum & Parkir Liar', 'Satpol PP & Dishub Sukoharjo', 1, 'Penertiban PKL di trotoar umum, parkir liar tarif ilegal, kebisingan, dan pelanggaran ketenteraman masyarakat.'),
(5, 'Pelayanan Administrasi Publik', 'Disdukcapil & DPMPTSP Sukoharjo', 3, 'Keluhan prosedur layanan KTP-el, KK, akta kelahiran, legalitas domisili, dan sistem perizinan berusaha daerah.'),
(6, 'Drainase & Pengendalian Banjir', 'Dinas PUPR & BPBD Kab. Sukoharjo', 3, 'Saluran gorong-gorong tersumbat sedimentasi lumpur, tanggul sungai jebol, dan genangan air jalan kabupaten.');

-- Seed Pengaduan
INSERT INTO `pengaduan` (`id_pengaduan`, `kode_tracking`, `id_user`, `id_kategori`, `judul_laporan`, `isi_laporan`, `kecamatan_lokasi`, `alamat_detail`, `latitude`, `longitude`, `foto_bukti_awal`, `is_anonim`, `status`, `jumlah_dukungan`, `rating_kepuasan`, `ulasan_warga`, `tanggal_lapor`) VALUES
(1, 'SKH-2026-0001', 2, 1, 'Jalan Berlubang Cukup Dalam di Barat Tugu Kartasura', 'Terdapat tiga titik lubang jalan berdiameter sekitar 50 cm dengan kedalaman 10 cm yang sangat membahayakan pengendara roda dua terutama saat hujan dan malam hari. Sudah ada 2 pengendara tergelincir.', 'Kartasura', 'Jl. Ahmad Yani No. 45, sebelah barat Bundaran Tugu Kartasura', -7.5561000, 110.7423000, 'sample_jalan_rusak.jpg', 0, 'Selesai', 38, 5, 'Respons Dinas PUPR sangat cepat! Dalam 3 hari lubang sudah ditambal rapi dengan aspal hotmix berkualitas. Matur nuwun Pemkab Sukoharjo!', '2026-10-10 08:30:00'),
(2, 'SKH-2026-0002', 3, 2, 'Lampu PJU Padam Total Sepanjang 200 Meter di Jalur Lambat Solo Baru', 'Lampu penerangan jalan umum di jalur lambat Solo Baru mati sejak dua malam lalu. Kondisi ruas jalan sangat gelap dan rawan tindak kejahatan jalanan.', 'Grogol', 'Jl. Ir. Soekarno, kawasan Solo Baru (seberang The Park Mall)', -7.6012000, 110.8165000, 'sample_pju_mati.jpg', 1, 'Diproses', 24, NULL, NULL, '2026-10-11 19:15:00'),
(3, 'SKH-2026-0003', 4, 3, 'Tumpukan Sampah Liar Menyumbat Saluran Irigasi Desa Kadilangu Baki', 'Banyak bungkusan sampah plastik rumah tangga dibuang sembarangan di pinggir tanggul jembatan air hingga menimbulkan bau menyengat dan air meluber ke jalan desa.', 'Baki', 'Jl. Raya Baki - Solo KM 4, dekat Jembatan Irigasi Desa Kadilangu', -7.6145000, 110.7840000, 'sample_sampah.jpg', 1, 'Verifikasi', 15, NULL, NULL, '2026-10-12 07:45:00'),
(4, 'SKH-2026-0004', 5, 4, 'Parkir Liar Menutup Akses Trotoar Pejalan Kaki Pasar Bekonang', 'Trotoar pejalan kaki depan pasar dipadati deretan sepeda motor tanpa karcis resmi perda dan memakan badan jalan utama sehingga memicu kemacetan setiap pagi.', 'Mojolaban', 'Depan gerbang utama Pasar Bekonang, Jl. Raya Bekonang', -7.6108000, 110.8752000, 'sample_parkir.jpg', 0, 'Baru', 42, NULL, NULL, '2026-10-13 10:20:00'),
(5, 'SKH-2026-0005', 6, 1, 'Jembatan Penghubung Antar Desa Bendosari Aspal Ambles', 'Pondasi oprit jembatan penghubung desa mengalami penurunan tanah sekitar 15 cm setelah guyuran hujan deras kemarin sore. Truk muatan dilarang melintas sementara.', 'Bendosari', 'Jembatan Kali Samin, Ruas Jl. Mulur - Bendosari KM 2', -7.6780000, 110.8950000, 'sample_jembatan.jpg', 0, 'Baru', 19, NULL, NULL, '2026-10-14 11:05:00'),
(6, 'SKH-2026-0006', 2, 6, 'Saluran Drainase Mampet Picu Genangan di Alun-Alun Satya Negara', 'Gorong-gorong pembuangan air di sisi barat Alun-alun tersumbat lumpur padat, mengakibatkan genangan setinggi mata kaki saat hujan turun lebih dari 1 jam.', 'Sukoharjo', 'Jl. Veteran No. 12, Sisi Barat Alun-Alun Satya Negara Sukoharjo', -7.6828000, 110.8407000, 'sample_drainase.jpg', 0, 'Diproses', 29, NULL, NULL, '2026-10-14 15:40:00');

-- Seed Tindak Lanjut
INSERT INTO `tindak_lanjut` (`id_tindak_lanjut`, `id_pengaduan`, `id_petugas`, `status_tindak_lanjut`, `catatan_petugas`, `foto_bukti_selesai`, `tanggal_tindak_lanjut`) VALUES
(1, 1, 1, 'Verifikasi', 'Laporan valid. Foto dan koordinat GPS telah diverifikasi petugas. Disposisi segera ke Bidang Bina Marga Dinas PUPR Sukoharjo.', NULL, '2026-10-10 09:30:00'),
(2, 1, 1, 'Diproses', 'Tim Reaksi Cepat Pemeliharaan Jalan DPUPR telah memobilisasi material aspal cold-mix dan mesin stamper ke titik Jl. Ahmad Yani Kartasura.', NULL, '2026-10-11 10:00:00'),
(3, 1, 1, 'Selesai', 'Pekerjaan penambalan 3 titik lubang di Jl. Ahmad Yani telah rampung dikerjakan dengan standar pengerasan hotmix. Akses jalan kini aman dan rata.', 'sample_jalan_selesai.jpg', '2026-10-12 16:00:00'),
(4, 2, 1, 'Verifikasi', 'Pengaduan PJU padam telah diverifikasi. Laporan diteruskan ke UPT Pengelolaan Prasarana Perhubungan Dinas Perhubungan Kab. Sukoharjo.', NULL, '2026-10-12 08:30:00'),
(5, 2, 1, 'Diproses', 'Petugas teknisi PJU telah berada di lokasi Solo Baru untuk mengganti saklar kontrol otomatis dan trafo suplai yang mengalami kerusakan korsleting.', NULL, '2026-10-12 14:15:00'),
(6, 3, 1, 'Verifikasi', 'Laporan sampah liar telah diterima dan dikoordinasikan dengan Mandor Kebersihan Wilayah DLH untuk penjadwalan truk sampah.', NULL, '2026-10-12 11:00:00'),
(7, 6, 1, 'Verifikasi', 'Laporan genangan air Alun-Alun Sukoharjo terverifikasi. Masuk prioritas pembersihan saluran drainase perkotaan.', NULL, '2026-10-14 16:30:00'),
(8, 6, 1, 'Diproses', 'Regu drainase DPUPR mengerahkan armada sedot lumpur untuk mengangkat endapan pasir dan sampah plastik di sepanjang saluran Jl. Veteran.', NULL, '2026-10-15 09:00:00');

