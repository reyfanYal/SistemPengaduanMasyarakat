"""
==============================================================================
BUILD REPORT UTS PEMROGRAMAN WEB 2
SISTEM PENGADUAN MASYARAKAT KABUPATEN SUKOHARJO (e-Pengaduan Sukoharjo)
FAKULTAS ILMU KOMPUTER — UNIVERSITAS DUTA BANGSA SURAKARTA
==============================================================================
Nama Mahasiswa : Muhammad Reyfan Nur Fadly
NIM            : 250103126
Kelas          : TI 25A5
Dosen Pengampu : M. Muhtarom, SE, S.KOM, M.KOM
Berkas Output  : Laporan_UTS_250103126_Muhammad_Reyfan_Nur_Fadly_PengaduanMasyarakat.docx
"""

import os
import sys
if sys.stdout.encoding != 'utf-8':
    sys.stdout.reconfigure(encoding='utf-8')
from pathlib import Path
from docx import Document
from docx.shared import Inches, Pt, Cm, RGBColor
from docx.enum.text import WD_ALIGN_PARAGRAPH
from docx.enum.table import WD_TABLE_ALIGNMENT
from docx.oxml.ns import qn
from docx.oxml import OxmlElement

BASE_DIR = Path(__file__).resolve().parent
SCREENSHOTS_DIR = BASE_DIR / "screenshots"
OUTPUT_FILE = BASE_DIR / "Laporan_UTS_250103126_Muhammad_Reyfan_Nur_Fadly_PengaduanMasyarakat.docx"

# 10 Berkas Screenshot & Keterangannya
SCREENSHOT_DATA = [
    (
        "01_halaman_login.png",
        "Gambar 2.1: Antarmuka Halaman Masuk (login.php) — Tata Letak Split-Screen Awesomic Design System dengan Formulir Autentikasi Pengguna/Admin, Fitur Lacak Langsung Publik Tanpa Login, Statistik Agregat Layanan, dan Tombol Pintas Kredensial Demo."
    ),
    (
        "02_halaman_register.png",
        "Gambar 2.2: Antarmuka Formulir Registrasi Akun Warga (register.php) — Pendaftaran Akun Masyarakat Sukoharjo dengan Validasi Format NIK 16 Digit Angka, Nama Lengkap KTP, Email, Telepon/WA, Dropdown 12 Kecamatan Domisili, dan Enkripsi Bcrypt."
    ),
    (
        "03_dashboard_statistik.png",
        "Gambar 2.3: Dashboard Utama & Peta Geospasial Sukoharjo (index.php) — Menampilkan 4 Kartu Metrik Awesomic (Total Aduan, Menunggu Verifikasi Beraksen Ember Orange #ff5a00, Dalam Proses, Selesai), Peta Interaktif Leaflet.js dengan Titik Marker Koordinat per Wilayah, Dark Card SLA OPD, dan Tabel Aduan Terkini."
    ),
    (
        "04_form_tambah_pengaduan.png",
        "Gambar 2.4: Formulir Input Pengaduan Baru (tambah_pengaduan.php) — Generator Kode Tiket Otomatis (SKH-2026-XXXX), Pemilihan Kategori OPD, Interaktif Leaflet GIS Pin Picker (Draggable Marker), Upload Foto Bukti Lapangan (Before), Opsi Pelaporan Anonim (Whistleblower Protection), dan Autosave Draft LocalStorage."
    ),
    (
        "05_tabel_data_pengaduan.png",
        "Gambar 2.5: Tabel Manajemen Data Pengaduan (pengaduan.php) — Fasilitas Pencarian Kata Kunci, Filter Multi-Kriteria (Kategori & Status), Penyamaran Identitas Pelapor Anonim, Indikator Dukungan Upvote Warga, serta Modal Dialog Konfirmasi Hapus Data."
    ),
    (
        "06_detail_dan_timeline_pengaduan.png",
        "Gambar 2.6: Halaman Detail Pengaduan & Linimasa Penanganan (detail_pengaduan.php) — Informasi Lengkap Laporan, Komparasi Foto Berdampingan (Before/After), Peta Titik Koordinat Kejadian, Vertical Live Tracking Timeline (Audit Trail), Tombol Dukung Laporan (Upvote), dan Formulir Survei Kepuasan Masyarakat (SKM Rating 1-5 Bintang)."
    ),
    (
        "07_form_tindak_lanjut.png",
        "Gambar 2.7: Modul Tindak Lanjut & Disposisi Petugas OPD (tindak_lanjut.php) — Panel Khusus Administrator/Petugas untuk Mengubah Status Laporan (Verifikasi, Diproses, Selesai, Ditolak), Menuliskan Catatan Lapangan Resmi, Mengunggah Bukti Perbaikan Tuntas, dan Memantau Antrean Aduan Prioritas."
    ),
    (
        "08_kelola_kategori.png",
        "Gambar 2.8: Modul Master Kategori Pengaduan (kategori.php) — Operasi CRUD Lengkap Master Bidang Permasalahan, Penetapan Instansi Penanggung Jawab (OPD), Konfigurasi Batas Waktu Pengerjaan (Target SLA Hari), dan Proteksi Integritas Relasional Basis Data."
    ),
    (
        "09_kelola_masyarakat.png",
        "Gambar 2.9: Modul Master Pengguna & Pelapor (masyarakat.php) — Operasi CRUD Basis Data Kependudukan Pelapor Kabupaten Sukoharjo, Rekapitulasi Jumlah Aduan yang Diajukan per Warga, Manajemen Hak Akses Role (Warga vs Admin OPD), dan Pengaturan Reset Sandi."
    ),
    (
        "10_laporan_dan_cetak.png",
        "Gambar 2.10: Halaman Rekapitulasi & Cetak Laporan Resmi (laporan.php) — Saringan Periode Tanggal, Kategori, dan Kecamatan, Kartu Metrik Rekapitulasi Terfilter, serta Tampilan Cetak Fisik / PDF Dilengkapi Kop Surat Resmi Pemkab Sukoharjo dan Lembar Pengesahan Pejabat (@media print)."
    ),
]

# Daftar Seluruh Source Code Proyek
SOURCE_CODE_FILES = [
    (
        "database/db_pengaduan_sukoharjo.sql",
        "Skema Basis Data MySQL (DDL, DML & Data Uji)",
        "Skrip SQL lengkap pembuatan database db_pengaduan_sukoharjo, 4 tabel berelasi (users, kategori, pengaduan, tindak_lanjut) dengan Foreign Key constraints, serta dataset uji realistis Kabupaten Sukoharjo."
    ),
    (
        "config/koneksi.php",
        "Koneksi Database & Helper Global",
        "Konfigurasi koneksi MySQLi XAMPP, penetapan zona waktu WIB, fungsi sanitasi clean() anti-XSS, generator badge status Awesomic (#ff5a00), dan helper resolusi foto fallback SVG."
    ),
    (
        "config/auth.php",
        "Middleware Autentikasi & RBAC",
        "Manajemen sesi login, penguncian hak akses peran (Role-Based Access Control: Admin vs Warga), verifikasi password Bcrypt dengan dukungan akun demo 123456, dan sistem notifikasi flash alerts."
    ),
    (
        "includes/head.php",
        "Header Dokumen & Konfigurasi CDN",
        "Struktur meta HTML5, integrasi Google Fonts 'DM Sans', konfigurasi tema warna Zinc-Scale Tailwind CSS, pustaka Leaflet.js GIS, Lucide Icons, dan aturan cetak @media print."
    ),
    (
        "includes/sidebar.php",
        "Fixed Left Sidebar Navigation (260px)",
        "Komponen navigasi bilah kiri tetap bersudut Awesomic, pendeteksi menu aktif, pemisahan menu dinamis khusus admin, dan penampil identitas akun pengguna aktif."
    ),
    (
        "includes/topbar.php",
        "Topbar Header & Quick Tracking Search",
        "Bilah atas pencarian cepat nomor tiket aduan (SKH-2026-XXXX) yang dapat melacak status secara instan dan penanda lencana peran aktif."
    ),
    (
        "includes/footer.php",
        "Footer Halaman & Inisialisasi Script",
        "Penutup kontainer aplikasi, informasi hak cipta resmi instansi Pemkab Sukoharjo, serta inisialisasi modul ikon Lucide."
    ),
    (
        "index.php",
        "Dashboard Utama & Peta Geospasial",
        "Pusat kendali aplikasi dengan 4 kartu metrik performa, peta interaktif Leaflet.js dengan pin marker warna status, kartu ringkasan komitmen SLA, dan tabel aduan masuk terbaru."
    ),
    (
        "login.php",
        "Portal Masuk & Live Tracking Publik",
        "Halaman login terpadu dengan tata letak editorial hero split-screen, formulir cek tiket langsung tanpa login, kartu login dengan validasi input, dan tombol demo instan 1-klik."
    ),
    (
        "register.php",
        "Formulir Pendaftaran Akun Warga",
        "Pendaftaran identitas masyarakat Sukoharjo dengan validasi regex NIK 16 digit, pengecekan duplikasi NIK/Username, pemilihan 12 kecamatan domisili, dan hashing kata sandi."
    ),
    (
        "logout.php",
        "Terminasi Sesi Pengguna",
        "Menghancurkan seluruh variabel sesi pengguna secara aman dan mengalihkan ke halaman awal dengan pesan flash sukses."
    ),
    (
        "tambah_pengaduan.php",
        "Form Input Aduan & Pin Geospasial",
        "Formulir laporan keluhan baru dengan auto-generator kode SKH-2026-XXXX, peta penanda koordinat interaktif Leaflet, upload foto bukti awal (max 2MB), opsi anonim, dan autosave draft localStorage."
    ),
    (
        "pengaduan.php",
        "Data Pengaduan (Read, Filter & Delete)",
        "Tabel manajemen pengaduan dengan live query search, filter multi-kategori dan status, perlindungan privasi nama anonim, counter upvote, dan modal dialog konfirmasi hapus data."
    ),
    (
        "edit_pengaduan.php",
        "Formulir Pembaruan Data Pengaduan",
        "Antarmuka modifikasi isi laporan, sinkronisasi pembaruan koordinat peta, dan opsi penggantian berkas foto bukti lapangan (khusus pemilik berstatus Baru atau Admin)."
    ),
    (
        "detail_pengaduan.php",
        "Detail Aduan, Linimasa & Survei SKM",
        "Halaman rincian laporan yang dapat diakses publik via kode tracking, kartu komparasi visual foto Before & After, linimasa audit trail bertingkat, upvote warga, dan formulir rating SKM."
    ),
    (
        "tindak_lanjut.php",
        "Verifikasi Status & Disposisi OPD",
        "Panel kerja operator dinas untuk memeriksa kelengkapan aduan, memperbarui tahapan status (Verifikasi/Diproses/Selesai/Ditolak), mencatat riwayat tindakan, dan mengunggah foto bukti tuntas."
    ),
    (
        "kategori.php",
        "Master Data Kategori Permasalahan (CRUD)",
        "Modul master pengelolaan klasifikasi pengaduan, instansi penanggung jawab (OPD), target SLA penyelesaian, dan pengecekan proteksi integritas sebelum penghapusan."
    ),
    (
        "masyarakat.php",
        "Master Data Kependudukan & Pengguna (CRUD)",
        "Modul manajemen seluruh akun pengguna (warga dan admin), filter pencarian identitas, aggregasi total laporan per warga, dan utilitas reset kredensial akun."
    ),
    (
        "laporan.php",
        "Rekapitulasi Periode & Cetak Laporan",
        "Modul pelaporan dengan filter rentang tanggal, kategori, dan kecamatan, 4 kartu ringkasan terfilter, tabel rekap data, serta layout siap cetak ber-Kop Surat Resmi Pemkab Sukoharjo."
    ),
]

def set_cell_shading(cell, color_hex):
    """Memberikan warna latar belakang (shading) pada sel tabel."""
    tc_pr = cell._tc.get_or_add_tcPr()
    shd = OxmlElement('w:shd')
    shd.set(qn('w:fill'), color_hex)
    shd.set(qn('w:val'), 'clear')
    tc_pr.append(shd)

def set_cell_borders(cell, color_hex='CBD5E1', sz='4'):
    """Memberikan garis tepi halus pada kotak kode program."""
    tc_pr = cell._tc.get_or_add_tcPr()
    tcBorders = OxmlElement('w:tcBorders')
    for edge in ('top', 'left', 'bottom', 'right'):
        el = OxmlElement(f'w:{edge}')
        el.set(qn('w:val'), 'single')
        el.set(qn('w:sz'), sz)
        el.set(qn('w:space'), '0')
        el.set(qn('w:color'), color_hex)
        tcBorders.append(el)
    tc_pr.append(tcBorders)

def set_table_borders_none(table):
    """Menghilangkan seluruh border pada tabel identitas mahasiswa."""
    tbl_pr = table._tbl.tblPr
    tblBorders = OxmlElement('w:tblBorders')
    for edge in ('top', 'left', 'bottom', 'right', 'insideH', 'insideV'):
        el = OxmlElement(f'w:{edge}')
        el.set(qn('w:val'), 'none')
        tblBorders.append(el)
    tbl_pr.append(tblBorders)

def build():
    print("==================================================================")
    print("📄 MEMULAI GENERASI LAPORAN UTS PEMROGRAMAN WEB 2 (.DOCX)")
    print("==================================================================")

    doc = Document()

    # 1. Konfigurasi Margin Halaman Standar Akademik FIKOM (Top: 2.54cm, Bottom: 2.54cm, Left: 3cm, Right: 2.54cm)
    for section in doc.sections:
        section.top_margin = Cm(2.54)
        section.bottom_margin = Cm(2.54)
        section.left_margin = Cm(3.0)
        section.right_margin = Cm(2.54)

        # Header Halaman
        header_p = section.header.paragraphs[0]
        header_p.text = "e-Pengaduan Kabupaten Sukoharjo — UTS Pemrograman Web 2 (Tema 13)"
        header_p.alignment = WD_ALIGN_PARAGRAPH.RIGHT
        if header_p.runs:
            header_p.runs[0].font.name = 'Times New Roman'
            header_p.runs[0].font.size = Pt(8.5)
            header_p.runs[0].font.italic = True
            header_p.runs[0].font.color.rgb = RGBColor(0x71, 0x80, 0x96)

        # Footer Halaman
        footer_p = section.footer.paragraphs[0]
        footer_p.text = "Universitas Duta Bangsa Surakarta | Muhammad Reyfan Nur Fadly (250103126)"
        footer_p.alignment = WD_ALIGN_PARAGRAPH.CENTER
        if footer_p.runs:
            footer_p.runs[0].font.name = 'Times New Roman'
            footer_p.runs[0].font.size = Pt(9.0)
            footer_p.runs[0].font.color.rgb = RGBColor(0x71, 0x80, 0x96)

    # Gaya Font Dasar
    style = doc.styles['Normal']
    style.font.name = 'Times New Roman'
    style.font.size = Pt(12)
    style.paragraph_format.line_spacing = 1.5

    # =========================================================================
    # KOP LAPORAN RESMI UTS (SESUAI SLIDE 1, 8, 11)
    # =========================================================================
    kop_title = doc.add_paragraph()
    kop_title.alignment = WD_ALIGN_PARAGRAPH.CENTER
    kop_title.paragraph_format.space_before = Pt(0)
    kop_title.paragraph_format.space_after = Pt(4)
    run_title = kop_title.add_run(
        'LAPORAN UJIAN TENGAH SEMESTER (UTS) — PEMROGRAMAN WEB 2\n'
        'SISTEM PENGADUAN MASYARAKAT KABUPATEN SUKOHARJO\n'
        '(e-PENGADUAN SUKOHARJO)'
    )
    run_title.bold = True
    run_title.font.size = Pt(14)
    run_title.font.color.rgb = RGBColor(0x0F, 0x17, 0x2A)

    kop_sub = doc.add_paragraph()
    kop_sub.alignment = WD_ALIGN_PARAGRAPH.CENTER
    kop_sub.paragraph_format.space_before = Pt(0)
    kop_sub.paragraph_format.space_after = Pt(12)
    run_sub = kop_sub.add_run(
        'Tema 13: Sistem Pengaduan Masyarakat | Berbasis PHP Native & MySQL\n'
        'Program Studi S1 Teknik Informatika — Fakultas Ilmu Komputer\n'
        'Universitas Duta Bangsa Surakarta\n'
        'Tahun Akademik 2026/2027'
    )
    run_sub.font.size = Pt(11)
    run_sub.font.color.rgb = RGBColor(0x33, 0x41, 0x55)

    divider = doc.add_paragraph()
    divider.alignment = WD_ALIGN_PARAGRAPH.CENTER
    divider.paragraph_format.space_after = Pt(14)
    r_div = divider.add_run('―' * 52)
    r_div.bold = True
    r_div.font.size = Pt(11)
    r_div.font.color.rgb = RGBColor(0x0F, 0x17, 0x2A)

    # Identitas Mahasiswa (Tabel Bersih)
    id_table = doc.add_table(rows=6, cols=2)
    id_table.alignment = WD_TABLE_ALIGNMENT.CENTER
    set_table_borders_none(id_table)

    id_data = [
        ('Nama Lengkap', 'Muhammad Reyfan Nur Fadly'),
        ('Nomor Induk Mahasiswa (NIM)', '250103126'),
        ('Program Studi / Kelas', 'S1 Teknik Informatika / TI 25A5'),
        ('Mata Kuliah', 'Pemrograman Web 2 (Ujian Tengah Semester)'),
        ('Tema Proyek', 'Tema 13 — Sistem Pengaduan Masyarakat'),
        ('Dosen Pengampu', 'M. Muhtarom, SE, S.KOM, M.KOM'),
    ]

    for row_idx, (label, val) in enumerate(id_data):
        cell_lbl = id_table.cell(row_idx, 0)
        cell_lbl.width = Inches(2.7)
        p_lbl = cell_lbl.paragraphs[0]
        p_lbl.paragraph_format.space_before = Pt(1)
        p_lbl.paragraph_format.space_after = Pt(1)
        p_lbl.paragraph_format.line_spacing = 1.15
        r_l = p_lbl.add_run(label)
        r_l.bold = True
        r_l.font.size = Pt(10.5)

        cell_val = id_table.cell(row_idx, 1)
        cell_val.width = Inches(3.5)
        p_val = cell_val.paragraphs[0]
        p_val.paragraph_format.space_before = Pt(1)
        p_val.paragraph_format.space_after = Pt(1)
        p_val.paragraph_format.line_spacing = 1.15
        r_v = p_val.add_run(f':  {val}')
        r_v.font.size = Pt(10.5)

    doc.add_paragraph().paragraph_format.space_after = Pt(10)

    # Ringkasan Eksekutif
    p_ringkasan = doc.add_paragraph()
    p_ringkasan.paragraph_format.space_after = Pt(14)
    p_ringkasan.paragraph_format.alignment = WD_ALIGN_PARAGRAPH.JUSTIFY
    p_ringkasan.add_run('Ringkasan Eksekutif Proyek: ').bold = True
    p_ringkasan.add_run(
        'Laporan resmi ini mendokumentasikan pemenuhan seluruh kriteria penilaian Ujian Tengah Semester (UTS) '
        'mata kuliah Pemrograman Web 2 dengan mengangkat studi kasus pelayanan publik Pemerintah Kabupaten Sukoharjo. '
        'Aplikasi "e-Pengaduan Sukoharjo" dirancang dengan pola arsitektur Modular Native PHP (8.x) dan basis data relasional '
        'MySQL (MariaDB) pada lingkungan XAMPP. Mengadaptasi fungsionalitas sistem LaporSemar (SP4N-LAPOR!), aplikasi ini '
        'menyediakan kode tracking unik (SKH-2026-XXXX), perlindungan pelapor (Whistleblower Protection), pemetaan geospasial '
        'Leaflet.js pada 12 kecamatan di Sukoharjo, komparasi visual foto Before & After, penanganan disposisi dinas (OPD), '
        'Survei Kepuasan Masyarakat (SKM), serta laporan rekapitulasi tercetak ber-Kop Resmi Pemkab. Desain antarmuka secara presisi '
        'menerapkan Awesomic Design System dengan 99% Zinc-scale palette, hairline border 1px #ececee, radius sudut 36px tanpa bayangan, '
        'serta bilah samping kiri tetap (Fixed Left Sidebar 260px).'
    )

    doc.add_page_break()

    # =========================================================================
    # BAB I: DESKRIPSI SISTEM & PERANCANGAN BASIS DATA
    # =========================================================================
    h1 = doc.add_heading('BAB I — DESKRIPSI SISTEM & PERANCANGAN BASIS DATA', level=1)
    h1.paragraph_format.space_before = Pt(12)
    h1.paragraph_format.space_after = Pt(8)
    for run in h1.runs:
        run.font.name = 'Times New Roman'
        run.font.size = Pt(14)
        run.font.bold = True
        run.font.color.rgb = RGBColor(0x0F, 0x17, 0x2A)

    p_arsitektur = doc.add_paragraph()
    p_arsitektur.paragraph_format.alignment = WD_ALIGN_PARAGRAPH.JUSTIFY
    p_arsitektur.paragraph_format.space_after = Pt(10)
    p_arsitektur.add_run('1.1. Arsitektur & Ruang Lingkup Sistem\n').bold = True
    p_arsitektur.add_run(
        'Sistem e-Pengaduan Kabupaten Sukoharjo dirancang sebagai jembatan komunikasi transparan antara warga di 12 kecamatan '
        '(Sukoharjo, Kartasura, Grogol, Baki, Mojolaban, Polokarto, Bendosari, Nguter, Tawangsari, Weru, Bulu, dan Gatak) '
        'dengan dinas penanggung jawab teknis (seperti Dinas PUPR, Dinas Perhubungan, DLH, Satpol PP, dan Disdukcapil). '
        'Sistem mengimplementasikan pengamanan kueri menggunakan MySQLi Prepared Statements di seluruh transaksi guna '
        'mengeliminasi celah SQL Injection, serta fungsi htmlspecialchars() untuk menangkal serangan Cross-Site Scripting (XSS).'
    )

    p_entitas = doc.add_paragraph()
    p_entitas.paragraph_format.alignment = WD_ALIGN_PARAGRAPH.JUSTIFY
    p_entitas.paragraph_format.space_after = Pt(10)
    p_entitas.add_run('1.2. Relasi Entitas Basis Data (db_pengaduan_sukoharjo)\n').bold = True
    p_entitas.add_run(
        'Basis data relasional terdiri dari 4 tabel utama yang terhubung secara terstruktur menggunakan Primary Key dan Foreign Key:\n'
        '1. users: Menyimpan kredensial autentikasi pengguna dengan enkripsi Bcrypt, NIK 16 digit, kontak, kecamatan domisili, dan peran (admin / warga).\n'
        '2. kategori: Master klasifikasi aduan publik, OPD pelaksana tugas, batas waktu pengerjaan (Target SLA Hari), dan deskripsi ruang lingkup.\n'
        '3. pengaduan: Tabel transaksi utama yang menyimpan kode tracking unik, relasi user dan kategori, koordinat spasial (latitude/longitude), foto bukti kondisi awal (Before), opsi anonim (is_anonim), status laporan, jumlah upvote dukungan warga, dan rating survei kepuasan SKM.\n'
        '4. tindak_lanjut: Audit trail kronologi penanganan oleh petugas OPD, riwayat perubahan status, catatan teknis lapangan, dan foto bukti penyelesaian tuntas (After).'
    )

    p_tabel_desc = doc.add_paragraph()
    p_tabel_desc.paragraph_format.space_after = Pt(6)
    p_tabel_desc.add_run('Tabel 1.1: Kamus Data & Spesifikasi Tabel Relasional').bold = True

    # Tabel Kamus Data
    tbl_schema = doc.add_table(rows=5, cols=4)
    tbl_schema.alignment = WD_TABLE_ALIGNMENT.CENTER
    tbl_schema.style = 'Table Grid'

    headers_schema = ['Nama Tabel', 'Fungsi Utama', 'Kunci Utama (PK)', 'Relasi / Kunci Asing (FK)']
    for c_idx, h_text in enumerate(headers_schema):
        cell = tbl_schema.cell(0, c_idx)
        set_cell_shading(cell, 'F1F5F9')
        p = cell.paragraphs[0]
        p.alignment = WD_ALIGN_PARAGRAPH.CENTER
        r = p.add_run(h_text)
        r.bold = True
        r.font.size = Pt(9.5)

    schema_rows = [
        ('users', 'Data identitas masyarakat & akun admin dinas', 'id_user (INT, AI)', 'None (Independen)'),
        ('kategori', 'Master klasifikasi keluhan & target hari SLA', 'id_kategori (INT, AI)', 'None (Master Data)'),
        ('pengaduan', 'Transaksi aduan, koordinat GIS, status, foto before', 'id_pengaduan (INT, AI)', 'id_user -> users, id_kategori -> kategori'),
        ('tindak_lanjut', 'Audit log linimasa, tindak lanjut dinas, foto after', 'id_tindak_lanjut (INT, AI)', 'id_pengaduan -> pengaduan, id_petugas -> users'),
    ]

    for r_idx, (t_name, t_fn, t_pk, t_fk) in enumerate(schema_rows, 1):
        for c_idx, text_val in enumerate([t_name, t_fn, t_pk, t_fk]):
            cell = tbl_schema.cell(r_idx, c_idx)
            p = cell.paragraphs[0]
            p.paragraph_format.space_before = Pt(2)
            p.paragraph_format.space_after = Pt(2)
            p.paragraph_format.line_spacing = 1.15
            r = p.add_run(text_val)
            r.font.size = Pt(9)
            if c_idx == 0:
                r.bold = True

    p_alur = doc.add_paragraph()
    p_alur.paragraph_format.space_before = Pt(12)
    p_alur.paragraph_format.alignment = WD_ALIGN_PARAGRAPH.JUSTIFY
    p_alur.paragraph_format.space_after = Pt(12)
    p_alur.add_run('1.3. Alur Siklus Hidup Penanganan Pengaduan (Status Lifecycle)\n').bold = True
    p_alur.add_run(
        'Siklus penanganan aduan mengikuti tahapan: '
        '(1) [Baru] Warga mengajukan aduan dan sistem menghasilkan kode SKH-2026-XXXX; '
        '(2) [Verifikasi] Operator dinas memeriksa kevalidan bukti foto dan titik koordinat; '
        '(3) [Diproses] Laporan didisposisikan kepada regu teknis lapangan dinas penanggung jawab; '
        '(4) [Selesai] Petugas mengunggah foto bukti pengerjaan (After) dan menutup tiket; '
        '(5) [Survei SKM] Pelapor memberikan penilaian skor 1-5 bintang dan ulasan kepuasan penanganan; '
        'atau [Ditolak] apabila materi aduan tidak relevan, hoaks, atau berada di luar yurisdiksi Kabupaten Sukoharjo.'
    )

    doc.add_page_break()

    # =========================================================================
    # BAB II: PENGUJIAN FITUR & HASIL IMPLEMENTASI ANTARMUKA (SCREENSHOTS)
    # =========================================================================
    h2 = doc.add_heading('BAB II — PENGUJIAN FITUR & HASIL IMPLEMENTASI ANTARMUKA', level=1)
    h2.paragraph_format.space_before = Pt(12)
    h2.paragraph_format.space_after = Pt(8)
    for run in h2.runs:
        run.font.name = 'Times New Roman'
        run.font.size = Pt(14)
        run.font.bold = True
        run.font.color.rgb = RGBColor(0x0F, 0x17, 0x2A)

    intro_p2 = doc.add_paragraph(
        'Bab ini menyajikan dokumentasi hasil pengujian langsung seluruh modul fungsional antarmuka sistem e-Pengaduan '
        'Kabupaten Sukoharjo. Seluruh tangkapan layar diambil secara otomatis pada peladen web lokal XAMPP aktif '
        'menggunakan headless browser Puppeteer beresolusi tinggi (1366 × 768 piksel) untuk memastikan keterbacaan data yang optimal.'
    )
    intro_p2.paragraph_format.alignment = WD_ALIGN_PARAGRAPH.JUSTIFY
    intro_p2.paragraph_format.space_after = Pt(14)

    for idx, (filename, caption) in enumerate(SCREENSHOT_DATA, 1):
        img_path = SCREENSHOTS_DIR / filename
        
        # Heading per screenshot
        h_sub = doc.add_heading(f'2.{idx}. {caption.split(" — ")[0]}', level=2)
        h_sub.paragraph_format.space_before = Pt(10)
        h_sub.paragraph_format.space_after = Pt(4)
        for run in h_sub.runs:
            run.font.name = 'Times New Roman'
            run.font.size = Pt(11.5)
            run.font.bold = True
            run.font.color.rgb = RGBColor(0x0F, 0x17, 0x2A)

        if not img_path.exists():
            p_err = doc.add_paragraph()
            r_err = p_err.add_run(f'[Peringatan: Berkas tangkapan layar {filename} belum ditemukan]')
            r_err.italic = True
            r_err.font.color.rgb = RGBColor(0xDC, 0x26, 0x26)
            continue

        # Sisipkan Gambar Resolusi Tinggi (Lebar ~6.0 inci)
        p_img = doc.add_paragraph()
        p_img.alignment = WD_ALIGN_PARAGRAPH.CENTER
        p_img.paragraph_format.space_before = Pt(4)
        p_img.paragraph_format.space_after = Pt(4)
        run_img = p_img.add_run()
        run_img.add_picture(str(img_path), width=Inches(6.0))

        # Caption Gambar
        p_cap = doc.add_paragraph()
        p_cap.alignment = WD_ALIGN_PARAGRAPH.CENTER
        p_cap.paragraph_format.space_before = Pt(2)
        p_cap.paragraph_format.space_after = Pt(8)
        r_cap = p_cap.add_run(caption)
        r_cap.font.name = 'Times New Roman'
        r_cap.font.size = Pt(9.5)
        r_cap.font.italic = True
        r_cap.font.color.rgb = RGBColor(0x33, 0x41, 0x55)

        # Penjelasan Fungsionalitas
        desc_text = caption.split(" — ")[1] if " — " in caption else ""
        if desc_text:
            p_desc = doc.add_paragraph()
            p_desc.paragraph_format.alignment = WD_ALIGN_PARAGRAPH.JUSTIFY
            p_desc.paragraph_format.space_before = Pt(0)
            p_desc.paragraph_format.space_after = Pt(12)
            p_desc.paragraph_format.line_spacing = 1.15
            r_d = p_desc.add_run(f'Penjelasan Fungsional: {desc_text}')
            r_d.font.size = Pt(10)
            r_d.font.color.rgb = RGBColor(0x1E, 0x29, 0x3B)

    doc.add_page_break()

    # =========================================================================
    # BAB III: LAMPIRAN SOURCE CODE LENGKAP
    # =========================================================================
    h3 = doc.add_heading('BAB III — LAMPIRAN SOURCE CODE LENGKAP', level=1)
    h3.paragraph_format.space_before = Pt(12)
    h3.paragraph_format.space_after = Pt(8)
    for run in h3.runs:
        run.font.name = 'Times New Roman'
        run.font.size = Pt(14)
        run.font.bold = True
        run.font.color.rgb = RGBColor(0x0F, 0x17, 0x2A)

    intro_p3 = doc.add_paragraph(
        'Bab ini melampirkan seluruh kode program sumber aplikasi e-Pengaduan Kabupaten Sukoharjo secara utuh '
        'tanpa pemotongan atau placeholder komentar // TODO. Seluruh berkas disajikan dalam kotak berarsir abu-abu '
        '(shading #F8FAFC) menggunakan jenis huruf monospaced (Consolas 8pt) dan kerapatan spasi tunggal (1.0) '
        'guna memenuhi kaidah keterbacaan teknis standar FIKOM UDB.'
    )
    intro_p3.paragraph_format.alignment = WD_ALIGN_PARAGRAPH.JUSTIFY
    intro_p3.paragraph_format.space_after = Pt(14)

    for idx, (rel_path, kategori, deskripsi) in enumerate(SOURCE_CODE_FILES, 1):
        fpath = BASE_DIR / rel_path

        h_code = doc.add_heading(f'3.{idx}. Berkas: {rel_path}', level=2)
        h_code.paragraph_format.space_before = Pt(10)
        h_code.paragraph_format.space_after = Pt(2)
        for run in h_code.runs:
            run.font.name = 'Times New Roman'
            run.font.size = Pt(11)
            run.font.bold = True
            run.font.color.rgb = RGBColor(0x0F, 0x17, 0x2A)

        meta_p = doc.add_paragraph()
        meta_p.paragraph_format.space_before = Pt(0)
        meta_p.paragraph_format.space_after = Pt(4)
        meta_p.paragraph_format.line_spacing = 1.15

        r_tag = meta_p.add_run(f'Kategori: {kategori}\n')
        r_tag.bold = True
        r_tag.font.size = Pt(9.5)
        r_tag.font.color.rgb = RGBColor(0x0F, 0x17, 0x2A)

        r_desc = meta_p.add_run(f'Keterangan: {deskripsi}')
        r_desc.font.size = Pt(9.5)
        r_desc.font.color.rgb = RGBColor(0x47, 0x55, 0x69)

        if fpath.exists():
            code_text = fpath.read_text(encoding='utf-8', errors='replace')
        else:
            code_text = f'// Berkas {rel_path} tidak ditemukan pada direktori proyek.'

        # Buat Kotak Kode Ber-shading dengan Garis Tepi
        table_code = doc.add_table(rows=1, cols=1)
        table_code.alignment = WD_TABLE_ALIGNMENT.CENTER
        cell_code = table_code.cell(0, 0)
        cell_code.width = Inches(6.0)

        set_cell_shading(cell_code, 'F8FAFC')
        set_cell_borders(cell_code, color_hex='CBD5E1', sz='4')

        cell_p = cell_code.paragraphs[0]
        cell_p.paragraph_format.space_before = Pt(4)
        cell_p.paragraph_format.space_after = Pt(4)
        cell_p.paragraph_format.line_spacing = 1.0

        r_code = cell_p.add_run(code_text)
        r_code.font.name = 'Consolas'
        r_code.font.size = Pt(8.0)
        r_code.font.color.rgb = RGBColor(0x0F, 0x17, 0x2A)

        doc.add_paragraph().paragraph_format.space_after = Pt(8)

    # Simpan Berkas Laporan Resmi (.docx)
    print(f"\n💾 Menyimpan dokumen laporan ke: {OUTPUT_FILE} ...")
    doc.save(str(OUTPUT_FILE))
    file_size_mb = os.path.getsize(str(OUTPUT_FILE)) / (1024 * 1024)
    print(f"🎉 SUKSES! Berkas laporan resmi UTS berhasil tercipta ({file_size_mb:.2f} MB).")

if __name__ == "__main__":
    build()
