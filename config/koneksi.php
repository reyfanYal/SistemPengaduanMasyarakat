<?php
/**
 * ==============================================================================
 * KONFIGURASI KONEKSI DATABASE & HELPER GLOBAL
 * ePengaduan Kabupaten Sukoharjo - UTS Pemrograman Web 2
 * ==============================================================================
 */

// Konfigurasi Database XAMPP Default
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'db_pengaduan_sukoharjo';

// Inisialisasi koneksi MySQLi
$koneksi = @mysqli_connect($db_host, $db_user, $db_pass, $db_name);

// Penanganan kegagalan koneksi dengan tampilan panduan ramah pengguna
if (!$koneksi) {
    // Cek jika database belum dibuat
    $conn_check = @mysqli_connect($db_host, $db_user, $db_pass);
    $db_exists = false;
    if ($conn_check) {
        $result = mysqli_query($conn_check, "SHOW DATABASES LIKE '$db_name'");
        if ($result && mysqli_num_rows($result) > 0) {
            $db_exists = true;
        }
        mysqli_close($conn_check);
    }

    echo '<!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Koneksi Basis Data Gagal - ePengaduan Sukoharjo</title>
        <script src="https://cdn.tailwindcss.com"></script>
        <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    </head>
    <body class="bg-[#f4f4f5] text-[#18181b] min-h-screen flex items-center justify-center p-6 font-[\'DM_Sans\']">
        <div class="max-w-lg w-full bg-white rounded-[36px] border border-[#ececee] p-8">
            <div class="inline-flex items-center gap-2 bg-[#ff5a00] text-white text-[12px] font-semibold px-3 py-1 rounded-[12px] mb-4">
                ERROR DATABASE
            </div>
            <h1 class="text-[26px] font-bold text-[#09090b] mb-3">Koneksi Basis Data Terputus</h1>
            <p class="text-[#52525b] text-[14px] leading-relaxed mb-6">
                Aplikasi tidak dapat terhubung ke database <strong>' . htmlspecialchars($db_name) . '</strong> pada server MySQL (XAMPP).
            </p>
            <div class="bg-[#fafafa] rounded-[18px] border border-[#ececee] p-4 text-[13px] text-[#52525b] space-y-2 mb-6">
                <p><strong>Langkah Penanganan:</strong></p>
                <ol class="list-decimal pl-5 space-y-1 text-[#18181b]">
                    <li>Pastikan modul <strong>Apache & MySQL</strong> di XAMPP Control Panel telah berjalan (Status: <span class="text-emerald-600 font-semibold">Running</span>).</li>
                    <li>Buka <strong>phpMyAdmin</strong> (<a href="http://localhost/phpmyadmin" target="_blank" class="text-blue-600 underline">localhost/phpmyadmin</a>).</li>
                    <li>Buat database <code>' . htmlspecialchars($db_name) . '</code> dan impor file: <code>database/db_pengaduan_sukoharjo.sql</code>.</li>
                </ol>
            </div>
            <a href="' . $_SERVER['REQUEST_URI'] . '" class="inline-block w-full text-center bg-[#09090b] text-white rounded-[14px] py-3 text-[14px] font-medium hover:bg-[#18181b] transition">
                Coba Muat Ulang Halaman
            </a>
        </div>
    </body>
    </html>';
    exit;
}

// Set karakter encoding UTF-8
mysqli_set_charset($koneksi, "utf8mb4");

// Zona Waktu Indonesia Barat (WIB)
date_default_timezone_set('Asia/Jakarta');

/**
 * Sanitasi string input untuk mencegah XSS & spasi berlebih
 */
function clean($data) {
    global $koneksi;
    if (is_null($data)) return '';
    $data = trim($data);
    $data = stripslashes($data);
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

/**
 * Format tanggal Indonesia lengkap dengan waktu WIB
 */
function format_tanggal_indo($datetime) {
    if (empty($datetime) || $datetime == '0000-00-00 00:00:00') return '-';
    $timestamp = strtotime($datetime);
    $bulan = [
        1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'Mei', 6 => 'Jun',
        7 => 'Jul', 8 => 'Agu', 9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des'
    ];
    $tgl = date('d', $timestamp);
    $bln = $bulan[(int)date('m', $timestamp)];
    $thn = date('Y', $timestamp);
    $jam = date('H:i', $timestamp);
    return "{$tgl} {$bln} {$thn}, {$jam} WIB";
}

/**
 * Format tanggal pendek (tanpa jam)
 */
function format_tanggal_pendek($datetime) {
    if (empty($datetime)) return '-';
    $timestamp = strtotime($datetime);
    $bulan = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni',
        7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
    ];
    $tgl = date('d', $timestamp);
    $bln = $bulan[(int)date('m', $timestamp)];
    $thn = date('Y', $timestamp);
    return "{$tgl} {$bln} {$thn}";
}

/**
 * Render Pill / Badge Status Awesomic Design System
 * Aturan: Status "Baru" menggunakan warna Ember Orange (#ff5a00)
 */
function render_status_badge($status) {
    switch ($status) {
        case 'Baru':
            return '<span class="inline-flex items-center gap-1.5 bg-[#ff5a00] text-white rounded-[12px] px-2.5 py-1 text-[12px] font-medium tracking-wide">
                        <span class="w-1.5 h-1.5 rounded-full bg-white animate-pulse"></span> Baru
                    </span>';
        case 'Verifikasi':
            return '<span class="inline-flex items-center gap-1.5 bg-[#e4e4e7] text-[#18181b] border border-[#d4d4d8] rounded-[12px] px-2.5 py-1 text-[12px] font-medium">
                        <span class="w-1.5 h-1.5 rounded-full bg-[#71717a]"></span> Verifikasi
                    </span>';
        case 'Diproses':
            return '<span class="inline-flex items-center gap-1.5 bg-[#27272a] text-white rounded-[12px] px-2.5 py-1 text-[12px] font-medium">
                        <span class="w-1.5 h-1.5 rounded-full bg-amber-400"></span> Diproses
                    </span>';
        case 'Selesai':
            return '<span class="inline-flex items-center gap-1.5 bg-[#09090b] text-white rounded-[12px] px-2.5 py-1 text-[12px] font-medium">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span> Selesai
                    </span>';
        case 'Ditolak':
            return '<span class="inline-flex items-center gap-1.5 bg-[#fee2e2] text-[#991b1b] border border-[#fecaca] rounded-[12px] px-2.5 py-1 text-[12px] font-medium">
                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span> Ditolak
                    </span>';
        default:
            return '<span class="inline-flex items-center bg-[#f4f4f5] text-[#52525b] rounded-[12px] px-2.5 py-1 text-[12px] font-medium">' . htmlspecialchars($status) . '</span>';
    }
}

/**
 * Daftar 12 Kecamatan Resmi di Kabupaten Sukoharjo
 */
function get_kecamatan_sukoharjo() {
    return [
        'Sukoharjo',
        'Kartasura',
        'Grogol',
        'Baki',
        'Mojolaban',
        'Polokarto',
        'Bendosari',
        'Nguter',
        'Tawangsari',
        'Weru',
        'Bulu',
        'Gatak'
    ];
}

/**
 * Helper Resolusi URL Gambar / Fallback jika file fisik tidak ada
 */
function get_bukti_image_url($filename, $subfolder = 'awal') {
    if (empty($filename)) {
        return 'assets/img/placeholder_empty.svg';
    }
    
    $local_path = __DIR__ . '/../assets/uploads/' . $subfolder . '/' . $filename;
    if (file_exists($local_path) && filesize($local_path) > 300) {
        return 'assets/uploads/' . $subfolder . '/' . htmlspecialchars($filename);
    }
    
    // Fallback placeholder visual elegan berbasis SVG
    $label = ($subfolder === 'awal') ? 'Foto Bukti Lapangan (Kondisi Awal)' : 'Foto Bukti Perbaikan (Hasil Tuntas)';
    $subtext = htmlspecialchars($filename);
    $bgColor = ($subfolder === 'awal') ? '%2318181b' : '%2309090b';
    return "data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='800' height='550' viewBox='0 0 800 550'><rect width='100%25' height='100%25' fill='{$bgColor}'/><circle cx='400' cy='220' r='48' fill='%2327272a'/><path d='M384 212h32M400 196v32' stroke='%23ff5a00' stroke-width='4' stroke-linecap='round'/><text x='400' y='320' fill='%23ffffff' font-family='sans-serif' font-size='20' font-weight='600' text-anchor='middle'>{$label}</text><text x='400' y='360' fill='%23a1a1aa' font-family='sans-serif' font-size='14' text-anchor='middle'>Dokumentasi: {$subtext}</text><text x='400' y='395' fill='%2371717a' font-family='sans-serif' font-size='12' text-anchor='middle'>Pemerintah Kabupaten Sukoharjo</text></svg>";
}
