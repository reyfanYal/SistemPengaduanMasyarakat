<?php
/**
 * ==============================================================================
 * DETAIL PENGADUAN, LIVE TRACKING, BEFORE/AFTER & SURVEI KEPUASAN (SKM)
 * ePengaduan Kabupaten Sukoharjo - UTS Pemrograman Web 2
 * ==============================================================================
 */

require_once __DIR__ . '/config/koneksi.php';
require_once __DIR__ . '/config/auth.php';

$user = current_user();
$isAdmin = is_admin();
$userId = current_user_id();

// Ambil aduan berdasarkan ID atau Kode Tracking
$id_pengaduan = (int)($_GET['id'] ?? 0);
$kode_tracking = trim($_GET['kode'] ?? '');

$aduan = null;
if ($id_pengaduan > 0) {
    $stmt = mysqli_prepare($koneksi, "SELECT p.*, k.nama_kategori, k.instansi_tujuan, k.target_sla_hari, 
                                             u.nama_lengkap, u.nik, u.email, u.telepon, u.kecamatan as domisili_warga
                                      FROM pengaduan p
                                      JOIN kategori k ON p.id_kategori = k.id_kategori
                                      JOIN users u ON p.id_user = u.id_user
                                      WHERE p.id_pengaduan = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "i", $id_pengaduan);
    mysqli_stmt_execute($stmt);
    $aduan = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
} elseif (!empty($kode_tracking)) {
    $stmt = mysqli_prepare($koneksi, "SELECT p.*, k.nama_kategori, k.instansi_tujuan, k.target_sla_hari, 
                                             u.nama_lengkap, u.nik, u.email, u.telepon, u.kecamatan as domisili_warga
                                      FROM pengaduan p
                                      JOIN kategori k ON p.id_kategori = k.id_kategori
                                      JOIN users u ON p.id_user = u.id_user
                                      WHERE p.kode_tracking = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "s", $kode_tracking);
    mysqli_stmt_execute($stmt);
    $aduan = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
}

if (!$aduan) {
    echo '<!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Pengaduan Tidak Ditemukan — ePengaduan Sukoharjo</title>
        <script src="https://cdn.tailwindcss.com"></script>
        <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    </head>
    <body class="bg-[#f4f4f5] text-[#18181b] min-h-screen flex items-center justify-center p-6 font-[\'DM_Sans\']">
        <div class="max-w-md w-full bg-white rounded-[36px] border border-[#ececee] p-8 text-center">
            <span class="w-12 h-12 rounded-full bg-[#f4f4f5] text-[#71717a] inline-flex items-center justify-center mb-4 text-[20px] font-bold">?</span>
            <h1 class="text-[22px] font-semibold text-[#09090b] mb-2">Tiket Tidak Ditemukan</h1>
            <p class="text-[14px] text-[#52525b] mb-6">
                Kode tracking atau nomor identifikasi pengaduan yang Anda cari tidak terdaftar pada sistem basis data Pemkab Sukoharjo.
            </p>
            <div class="flex items-center justify-center gap-3">
                <a href="login.php" class="px-5 py-2.5 rounded-[12px] bg-[#f4f4f5] text-[#18181b] text-[13px] font-medium">Ke Beranda</a>
                <a href="index.php" class="px-5 py-2.5 rounded-[12px] bg-[#09090b] text-white text-[13px] font-medium">Dashboard</a>
            </div>
        </div>
    </body>
    </html>';
    exit;
}

$id_pengaduan = (int)$aduan['id_pengaduan'];

// Penanganan Upvote / Dukung Laporan (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upvote') {
    if (!isset($_SESSION['upvoted_aduan'])) {
        $_SESSION['upvoted_aduan'] = [];
    }

    if (!in_array($id_pengaduan, $_SESSION['upvoted_aduan'])) {
        $stmt_up = mysqli_prepare($koneksi, "UPDATE pengaduan SET jumlah_dukungan = jumlah_dukungan + 1 WHERE id_pengaduan = ?");
        mysqli_stmt_bind_param($stmt_up, "i", $id_pengaduan);
        mysqli_stmt_execute($stmt_up);
        mysqli_stmt_close($stmt_up);

        $_SESSION['upvoted_aduan'][] = $id_pengaduan;
        set_flash('success', 'Terima kasih! Dukungan Anda terhadap laporan ini telah dicatat.');
    } else {
        set_flash('error', 'Anda telah memberikan dukungan pada laporan ini sebelumnya.');
    }
    header("Location: detail_pengaduan.php?id={$id_pengaduan}");
    exit;
}

// Penanganan Survei Kepuasan Masyarakat (SKM) Rating (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_rating') {
    require_login();
    $rating_val = (int)($_POST['rating_kepuasan'] ?? 5);
    $ulasan_val = trim($_POST['ulasan_warga'] ?? '');

    if ($rating_val >= 1 && $rating_val <= 5) {
        $stmt_rate = mysqli_prepare($koneksi, "UPDATE pengaduan SET rating_kepuasan = ?, ulasan_warga = ? WHERE id_pengaduan = ?");
        mysqli_stmt_bind_param($stmt_rate, "isi", $rating_val, $ulasan_val, $id_pengaduan);
        mysqli_stmt_execute($stmt_rate);
        mysqli_stmt_close($stmt_rate);

        set_flash('success', 'Matur nuwun! Penilaian Survei Kepuasan Masyarakat (SKM) Anda telah berhasil dikirim.');
    }
    header("Location: detail_pengaduan.php?id={$id_pengaduan}");
    exit;
}

// Ambil Riwayat Audit Trail Linimasa (Tindak Lanjut)
$stmt_tl = mysqli_prepare($koneksi, "SELECT tl.*, u.nama_lengkap as nama_petugas, u.role as role_petugas 
                                     FROM tindak_lanjut tl 
                                     JOIN users u ON tl.id_petugas = u.id_user 
                                     WHERE tl.id_pengaduan = ? 
                                     ORDER BY tl.tanggal_tindak_lanjut ASC");
mysqli_stmt_bind_param($stmt_tl, "i", $id_pengaduan);
mysqli_stmt_execute($stmt_tl);
$res_tl = mysqli_stmt_get_result($stmt_tl);

$timeline_rows = [];
$foto_selesai = null;
while ($tl = mysqli_fetch_assoc($res_tl)) {
    $timeline_rows[] = $tl;
    if (!empty($tl['foto_bukti_selesai'])) {
        $foto_selesai = $tl['foto_bukti_selesai'];
    }
}
mysqli_stmt_close($stmt_tl);

$hasUpvoted = isset($_SESSION['upvoted_aduan']) && in_array($id_pengaduan, $_SESSION['upvoted_aduan']);
$page_title = 'Lacak Tiket ' . $aduan['kode_tracking'];
$page_heading = 'Detail Pengaduan & Linimasa Penanganan';

require_once __DIR__ . '/includes/head.php';
?>

<div class="flex min-h-screen">
    <?php if (is_logged_in()): ?>
        <?php require_once __DIR__ . '/includes/sidebar.php'; ?>
    <?php endif; ?>

    <main class="<?= is_logged_in() ? 'ml-[260px]' : 'mx-auto'; ?> flex-1 p-6 md:p-10 max-w-[1200px] w-full">
        <?php if (is_logged_in()): ?>
            <?php require_once __DIR__ . '/includes/topbar.php'; ?>
        <?php else: ?>
            <!-- Public Guest Navbar Header -->
            <header class="flex items-center justify-between mb-8 pb-6 border-b border-[#ececee]">
                <div class="flex items-center gap-3">
                    <span class="text-[20px] font-semibold text-[#09090b]">Lapor Sukoharjo</span>
                    <span class="bg-[#ff5a00] text-white text-[10px] font-bold px-2 py-0.5 rounded-[8px] uppercase tracking-wider">
                        PORTAL RESMI
                    </span>
                </div>
                <div class="flex items-center gap-3">
                    <a href="login.php" class="px-5 py-2.5 bg-[#09090b] text-white rounded-[14px] text-[13px] font-medium hover:bg-[#18181b] transition">
                        Masuk Akun
                    </a>
                    <a href="register.php" class="px-5 py-2.5 bg-white border border-[#ececee] text-[#18181b] rounded-[14px] text-[13px] font-medium hover:bg-[#f4f4f5] transition">
                        Daftar Warga
                    </a>
                </div>
            </header>
        <?php endif; ?>

        <?= render_flash_alerts(); ?>

        <!-- Hero Status Card (Awesomic Rounded 36px) -->
        <div class="bg-white rounded-[36px] border border-[#ececee] p-8 sm:p-10 mb-8">
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6 pb-6 border-b border-[#f4f4f5]">
                <div>
                    <div class="flex flex-wrap items-center gap-3 mb-2">
                        <span class="font-mono text-[14px] font-bold text-[#09090b]">
                            <?= htmlspecialchars($aduan['kode_tracking']); ?>
                        </span>
                        <span><?= render_status_badge($aduan['status']); ?></span>
                        <span class="text-[12px] text-[#71717a]">
                            Tanggal Lapor: <?= format_tanggal_indo($aduan['tanggal_lapor']); ?>
                        </span>
                    </div>
                    <h1 class="text-[24px] sm:text-[28px] font-semibold text-[#09090b] tracking-tight leading-snug">
                        <?= htmlspecialchars($aduan['judul_laporan']); ?>
                    </h1>
                </div>

                <!-- Upvote & Admin Actions Toolbar -->
                <div class="flex flex-wrap items-center gap-3 shrink-0">
                    <!-- Upvote Button -->
                    <form action="detail_pengaduan.php?id=<?= $id_pengaduan; ?>" method="POST">
                        <input type="hidden" name="action" value="upvote">
                        <button type="submit" 
                                <?= $hasUpvoted ? 'disabled' : ''; ?>
                                class="h-11 px-5 rounded-[14px] <?= $hasUpvoted ? 'bg-[#f4f4f5] text-[#71717a] cursor-not-allowed' : 'bg-white hover:bg-[#fafafa] text-[#09090b] border border-[#ececee]'; ?> text-[13px] font-medium flex items-center gap-2 transition">
                            <i data-lucide="thumbs-up" class="w-4 h-4 text-[#ff5a00]"></i>
                            <span><?= $hasUpvoted ? 'Didukung' : 'Dukung Laporan'; ?> (<?= (int)$aduan['jumlah_dukungan']; ?>)</span>
                        </button>
                    </form>

                    <?php if ($isAdmin): ?>
                        <a href="tindak_lanjut.php?id=<?= $id_pengaduan; ?>" 
                           class="h-11 px-5 bg-[#09090b] hover:bg-[#18181b] text-white rounded-[14px] text-[13px] font-medium flex items-center gap-2 transition">
                            <i data-lucide="check-square" class="w-4 h-4"></i>
                            <span>Perbarui Tindak Lanjut</span>
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Meta Attributes Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 pt-6 text-[13px]">
                <div>
                    <span class="block text-[#71717a] mb-1">Kategori Permasalahan</span>
                    <span class="font-semibold text-[#09090b]"><?= htmlspecialchars($aduan['nama_kategori']); ?></span>
                </div>
                <div>
                    <span class="block text-[#71717a] mb-1">Instansi Penanggung Jawab</span>
                    <span class="font-semibold text-[#09090b]"><?= htmlspecialchars($aduan['instansi_tujuan']); ?></span>
                </div>
                <div>
                    <span class="block text-[#71717a] mb-1">Wilayah / Lokasi</span>
                    <span class="font-semibold text-[#09090b]">Kec. <?= htmlspecialchars($aduan['kecamatan_lokasi']); ?></span>
                </div>
                <div>
                    <span class="block text-[#71717a] mb-1">Identitas Pelapor</span>
                    <?php if ($aduan['is_anonim'] == 1): ?>
                        <?php if ($isAdmin): ?>
                            <span class="font-semibold text-[#09090b]"><?= htmlspecialchars($aduan['nama_lengkap']); ?></span>
                            <span class="text-[10px] bg-[#fff5f0] text-[#ff5a00] px-1.5 py-0.5 rounded font-bold ml-1">Anonim</span>
                        <?php else: ?>
                            <span class="text-[#71717a] italic">Disamarkan (Anonim)</span>
                        <?php endif; ?>
                    <?php else: ?>
                        <span class="font-semibold text-[#09090b]"><?= htmlspecialchars($aduan['nama_lengkap']); ?></span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- 2-Column Section: Before-After Proof Photo Cards (Side-by-Side Photographic Comparison) -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
            
            <!-- Before Card (Foto Bukti Awal Warga) -->
            <div class="bg-white rounded-[36px] border border-[#ececee] p-[28px] flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between mb-4">
                        <span class="inline-flex items-center gap-1.5 bg-[#f4f4f5] text-[#18181b] border border-[#ececee] text-[11px] font-bold uppercase tracking-wider px-2.5 py-1 rounded-[10px]">
                            BEFORE &bull; KONDISI AWAL
                        </span>
                        <span class="text-[12px] text-[#71717a]">Diunggah oleh Warga</span>
                    </div>

                    <div class="aspect-[4/3] rounded-[24px] overflow-hidden bg-[#fafafa] border border-[#ececee] mb-4">
                        <img src="<?= get_bukti_image_url($aduan['foto_bukti_awal'], 'awal'); ?>" 
                             alt="Kondisi Awal" 
                             class="w-full h-full object-cover">
                    </div>
                </div>

                <div class="text-[12px] text-[#71717a]">
                    Dokumentasi otentik saat pengaduan diajukan ke sistem.
                </div>
            </div>

            <!-- After Card (Foto Bukti Perbaikan Petugas) -->
            <div class="bg-white rounded-[36px] border border-[#ececee] p-[28px] flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between mb-4">
                        <span class="inline-flex items-center gap-1.5 bg-[#09090b] text-white text-[11px] font-bold uppercase tracking-wider px-2.5 py-1 rounded-[10px]">
                            AFTER &bull; HASIL TUNTAS
                        </span>
                        <span class="text-[12px] text-[#71717a]">Diverifikasi Petugas OPD</span>
                    </div>

                    <div class="aspect-[4/3] rounded-[24px] overflow-hidden bg-[#fafafa] border border-[#ececee] mb-4 flex items-center justify-center">
                        <?php if (!empty($foto_selesai)): ?>
                            <img src="<?= get_bukti_image_url($foto_selesai, 'selesai'); ?>" 
                                 alt="Hasil Perbaikan" 
                                 class="w-full h-full object-cover">
                        <?php else: ?>
                            <div class="flex flex-col items-center justify-center p-6 text-center text-[#71717a]">
                                <i data-lucide="clock" class="w-10 h-10 mb-2 text-[#a1a1aa]"></i>
                                <span class="text-[14px] font-medium text-[#18181b]">Pengerjaan Masih Berlangsung</span>
                                <span class="text-[12px] mt-1 max-w-xs">Foto bukti pengerjaan tuntas akan diunggah oleh dinas terkait setelah penanganan selesai.</span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="text-[12px] text-[#71717a]">
                    <?= !empty($foto_selesai) ? 'Dokumentasi resmi hasil perbaikan lapangan oleh dinas.' : 'Target penyelesaian SLA: ' . $aduan['target_sla_hari'] . ' Hari Kerja.'; ?>
                </div>
            </div>

        </div>

        <!-- 2-Column Section: Kronologi & Lokasi Peta + Live Tracking Timeline -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
            
            <!-- Left (2 Cols): Uraian Kronologi & Peta Leaflet Lokasi -->
            <div class="lg:col-span-2 space-y-8">
                
                <!-- Deskripsi Laporan Lengkap -->
                <div class="bg-white rounded-[36px] border border-[#ececee] p-[28px]">
                    <h3 class="text-[18px] font-semibold text-[#09090b] mb-4">
                        Uraian Lengkap Kronologi Pengaduan
                    </h3>
                    <div class="text-[14px] text-[#3f3f46] leading-relaxed whitespace-pre-line bg-[#fafafa] p-5 rounded-[20px] border border-[#ececee]">
                        <?= nl2br(htmlspecialchars($aduan['isi_laporan'])); ?>
                    </div>

                    <div class="mt-6 pt-4 border-t border-[#f4f4f5]">
                        <span class="text-[12px] text-[#71717a] font-medium uppercase tracking-wider block mb-1">Patokan Alamat Detail:</span>
                        <p class="text-[14px] text-[#18181b] font-medium flex items-center gap-2">
                            <i data-lucide="map-pin" class="w-4 h-4 text-[#ff5a00] shrink-0"></i>
                            <span><?= htmlspecialchars($aduan['alamat_detail']); ?></span>
                        </p>
                    </div>
                </div>

                <!-- Leaflet Mini Map Lokasi Kejadian -->
                <div class="bg-white rounded-[36px] border border-[#ececee] p-[28px]">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-[18px] font-semibold text-[#09090b]">
                            Titik Koordinat Peta Geospasial
                        </h3>
                        <span class="text-[12px] text-[#71717a] font-mono">
                            <?= (float)$aduan['latitude']; ?>, <?= (float)$aduan['longitude']; ?>
                        </span>
                    </div>

                    <div id="detail-map" class="w-full h-[280px] rounded-[20px] border border-[#ececee] overflow-hidden"></div>
                </div>

                <!-- Survei Kepuasan Masyarakat (SKM) Card (Saat Status = Selesai) -->
                <?php if ($aduan['status'] === 'Selesai'): ?>
                    <div class="bg-white rounded-[36px] border border-[#ececee] p-[28px]">
                        <div class="flex items-center gap-3 mb-4">
                            <span class="w-9 h-9 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold">
                                ★
                            </span>
                            <div>
                                <h3 class="text-[18px] font-semibold text-[#09090b]">Survei Kepuasan Masyarakat (SKM)</h3>
                                <p class="text-[12px] text-[#71717a]">Evaluasi kualitas pelayanan penyelesaian penanganan aduan</p>
                            </div>
                        </div>

                        <?php if (!empty($aduan['rating_kepuasan'])): ?>
                            <!-- Hasil SKM yang Sudah Diisi -->
                            <div class="p-5 rounded-[20px] bg-[#fafafa] border border-[#ececee]">
                                <div class="flex items-center gap-2 text-amber-500 text-[20px] mb-2">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <span><?= ($i <= $aduan['rating_kepuasan']) ? '★' : '☆'; ?></span>
                                    <?php endfor; ?>
                                    <span class="text-[14px] text-[#09090b] font-bold ml-2">(Skor: <?= $aduan['rating_kepuasan']; ?>/5)</span>
                                </div>
                                <p class="text-[14px] text-[#3f3f46] italic">
                                    "<?= htmlspecialchars($aduan['ulasan_warga']); ?>"
                                </p>
                            </div>
                        <?php elseif ($userId == $aduan['id_user']): ?>
                            <!-- Formulir Pengisian Rating untuk Pelapor -->
                            <form action="detail_pengaduan.php?id=<?= $id_pengaduan; ?>" method="POST" class="space-y-4">
                                <input type="hidden" name="action" value="submit_rating">
                                <div>
                                    <label class="block text-[13px] font-medium text-[#18181b] mb-1.5">
                                        Beri Bintang Kepuasan Penanganan (1 - 5)
                                    </label>
                                    <div class="flex items-center gap-4 text-[14px]">
                                        <?php for ($r = 5; $r >= 1; $r--): ?>
                                            <label class="flex items-center gap-1.5 cursor-pointer">
                                                <input type="radio" name="rating_kepuasan" value="<?= $r; ?>" <?= ($r == 5) ? 'checked' : ''; ?>>
                                                <span><?= $r; ?> ★</span>
                                            </label>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-[13px] font-medium text-[#18181b] mb-1.5">
                                        Ulasan / Testimoni Anda
                                    </label>
                                    <textarea name="ulasan_warga" rows="3" placeholder="Tuliskan apresiasi atau masukan terhadap kecepatan respon petugas dinas..." required class="w-full p-3 bg-white border border-[#ececee] rounded-[14px] text-[13px] outline-none"></textarea>
                                </div>
                                <button type="submit" class="px-6 py-2.5 bg-[#09090b] text-white rounded-[12px] text-[13px] font-medium hover:bg-[#18181b] transition">
                                    Kirim Survei SKM
                                </button>
                            </form>
                        <?php else: ?>
                            <div class="text-[13px] text-[#71717a] italic">
                                Pelapor belum mengisi survei kepuasan masyarakat untuk laporan ini.
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

            </div>

            <!-- Right (1 Col): Vertical Live Tracking Timeline (Audit Trail) -->
            <div class="bg-white rounded-[36px] border border-[#ececee] p-[28px] flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between mb-6 pb-4 border-b border-[#f4f4f5]">
                        <div>
                            <h3 class="text-[18px] font-semibold text-[#09090b] tracking-tight">
                                Linimasa Penanganan
                            </h3>
                            <p class="text-[12px] text-[#71717a]">Audit trail riwayat status resmi</p>
                        </div>
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    </div>

                    <!-- Vertical Timeline Steps -->
                    <div class="relative pl-6 space-y-8 before:absolute before:left-2.5 before:top-2 before:bottom-2 before:w-0.5 before:bg-[#ececee]">
                        
                        <!-- Step 1: Laporan Awal Dikirim Warga -->
                        <div class="relative">
                            <span class="absolute -left-6 top-1 w-3.5 h-3.5 rounded-full bg-[#09090b] border-2 border-white ring-2 ring-[#ececee]"></span>
                            <div>
                                <span class="text-[11px] font-bold text-[#ff5a00] uppercase tracking-wider block">Laporan Diterima Sistem</span>
                                <div class="text-[13px] font-semibold text-[#09090b]">Pengaduan Diajukan</div>
                                <div class="text-[12px] text-[#71717a] mt-0.5">
                                    <?= format_tanggal_indo($aduan['tanggal_lapor']); ?>
                                </div>
                                <p class="text-[12px] text-[#52525b] mt-1">
                                    Laporan masuk ke antrean verifikasi operator Diskominfo Kabupaten Sukoharjo.
                                </p>
                            </div>
                        </div>

                        <!-- Step 2+: Riwayat dari tabel tindak_lanjut -->
                        <?php foreach ($timeline_rows as $step): ?>
                            <div class="relative">
                                <span class="absolute -left-6 top-1 w-3.5 h-3.5 rounded-full bg-[#18181b] border-2 border-white ring-2 ring-[#ececee]"></span>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <?= render_status_badge($step['status_tindak_lanjut']); ?>
                                    </div>
                                    <div class="text-[12px] text-[#71717a] mt-1">
                                        <?= format_tanggal_indo($step['tanggal_tindak_lanjut']); ?>
                                    </div>
                                    <div class="text-[12px] font-medium text-[#18181b] mt-0.5">
                                        Petugas: <?= htmlspecialchars($step['nama_petugas']); ?>
                                    </div>
                                    <div class="text-[13px] text-[#3f3f46] mt-1.5 p-3 bg-[#fafafa] rounded-[14px] border border-[#ececee]">
                                        <?= nl2br(htmlspecialchars($step['catatan_petugas'])); ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>

                    </div>
                </div>

                <div class="mt-8 pt-4 border-t border-[#f4f4f5] text-[12px] text-[#71717a] text-center">
                    Terdata resmi di Sistem Informasi Pengaduan Pemkab Sukoharjo
                </div>
            </div>

        </div>

    </main>
</div>

<!-- Script Peta Detail Leaflet -->
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const aduanLat = <?= (float)$aduan['latitude']; ?>;
        const aduanLng = <?= (float)$aduan['longitude']; ?>;
        
        const map = L.map('detail-map').setView([aduanLat, aduanLng], 14);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 18,
            attribution: '&copy; OpenStreetMap'
        }).addTo(map);

        L.circleMarker([aduanLat, aduanLng], {
            radius: 9,
            fillColor: '#ff5a00',
            color: '#ffffff',
            weight: 2,
            opacity: 1,
            fillOpacity: 1
        }).addTo(map).bindPopup("<strong><?= htmlspecialchars(addslashes($aduan['kode_tracking'])); ?></strong><br><?= htmlspecialchars(addslashes($aduan['judul_laporan'])); ?>").openPopup();
    });
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

