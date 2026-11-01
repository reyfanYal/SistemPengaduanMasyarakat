<?php
/**
 * ==============================================================================
 * REKAPITULASI LAPORAN & CETAK LAPORAN RESMI (PRINT / PDF)
 * ePengaduan Kabupaten Sukoharjo - UTS Pemrograman Web 2
 * ==============================================================================
 */

require_once __DIR__ . '/config/koneksi.php';
require_once __DIR__ . '/config/auth.php';

require_login();

$user = current_user();
$isAdmin = is_admin();

// Parameter Filter Laporan
$tgl_mulai   = trim($_GET['tgl_mulai'] ?? date('Y-01-01'));
$tgl_selesai = trim($_GET['tgl_selesai'] ?? date('Y-m-d'));
$kategori    = (int)($_GET['kategori'] ?? 0);
$status      = trim($_GET['status'] ?? '');
$kecamatan   = trim($_GET['kecamatan'] ?? '');

// Kueri Filter Dinamis
$where = ["DATE(p.tanggal_lapor) BETWEEN ? AND ?"];
$params = [$tgl_mulai, $tgl_selesai];
$types = "ss";

if ($kategori > 0) {
    $where[] = "p.id_kategori = ?";
    $params[] = $kategori;
    $types .= "i";
}

if (!empty($status)) {
    $where[] = "p.status = ?";
    $params[] = $status;
    $types .= "s";
}

if (!empty($kecamatan)) {
    $where[] = "p.kecamatan_lokasi = ?";
    $params[] = $kecamatan;
    $types .= "s";
}

$where_sql = implode(" AND ", $where);

// Data Laporan Utama
$sql = "SELECT p.*, k.nama_kategori, k.instansi_tujuan, u.nama_lengkap, u.nik 
        FROM pengaduan p
        JOIN kategori k ON p.id_kategori = k.id_kategori
        JOIN users u ON p.id_user = u.id_user
        WHERE {$where_sql}
        ORDER BY p.tanggal_lapor DESC";

$stmt = mysqli_prepare($koneksi, $sql);
mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Hitung Statistik Ringkasan Hasil Filter
$total_rekap   = 0;
$selesai_rekap = 0;
$proses_rekap  = 0;
$baru_rekap    = 0;
$ratings_sum   = 0;
$ratings_count = 0;

$data_rows = [];
while ($row = mysqli_fetch_assoc($result)) {
    $data_rows[] = $row;
    $total_rekap++;
    if ($row['status'] === 'Selesai') $selesai_rekap++;
    elseif (in_array($row['status'], ['Verifikasi', 'Diproses'])) $proses_rekap++;
    elseif ($row['status'] === 'Baru') $baru_rekap++;

    if (!empty($row['rating_kepuasan'])) {
        $ratings_sum += (int)$row['rating_kepuasan'];
        $ratings_count++;
    }
}

$persen_selesai = $total_rekap > 0 ? round(($selesai_rekap / $total_rekap) * 100, 1) : 0;
$avg_rating_filtered = $ratings_count > 0 ? round($ratings_sum / $ratings_count, 1) : '-';

// Opsi Dropdown
$kategori_opts = mysqli_query($koneksi, "SELECT id_kategori, nama_kategori FROM kategori ORDER BY nama_kategori ASC");
$kecamatan_list = get_kecamatan_sukoharjo();

$page_title = 'Rekap & Cetak Laporan';
$page_heading = 'Laporan & Rekapitulasi Pengaduan';

require_once __DIR__ . '/includes/head.php';
?>

<div class="flex min-h-screen">
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>

    <main class="ml-[260px] flex-1 p-8 max-w-[1200px] w-full main-content">
        <?php require_once __DIR__ . '/includes/topbar.php'; ?>
        <?= render_flash_alerts(); ?>

        <!-- ===================================================================== -->
        <!-- KOP SURAT RESMI PEMKAB SUKOHARJO (HANYA MUNCUL SAAT CETAK / PDF) -->
        <!-- ===================================================================== -->
        <div class="hidden print:block pb-6 mb-6 border-b-2 border-black text-center">
            <div class="flex items-center justify-center gap-4 mb-2">
                <div>
                    <h2 class="text-[18px] font-bold uppercase tracking-wider text-black">
                        PEMERINTAH KABUPATEN SUKOHARJO
                    </h2>
                    <h3 class="text-[16px] font-bold uppercase tracking-wide text-black">
                        DINAS KOMUNIKASI DAN INFORMATIKA
                    </h3>
                    <p class="text-[12px] text-gray-700">
                        Gedung Menara Wijaya Lt. 3, Jl. Jenderal Sudirman No. 199, Sukoharjo, Jawa Tengah (Kode Pos: 57512)
                    </p>
                    <p class="text-[11px] text-gray-600">
                        Telepon: (0271) 593003 &bull; Laman Resmi: pengaduan.sukoharjokab.go.id
                    </p>
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-black">
                <h4 class="text-[15px] font-bold uppercase tracking-wide text-black">
                    REKAPITULASI ASPIRASI & PENGADUAN PELAYANAN PUBLIK
                </h4>
                <p class="text-[12px] text-gray-800">
                    Periode Laporan: <?= format_tanggal_pendek($tgl_mulai); ?> s.d. <?= format_tanggal_pendek($tgl_selesai); ?>
                </p>
            </div>
        </div>

        <!-- Filter Area & Tombol Cetak (Layar Layar / No-Print) -->
        <div class="no-print bg-white rounded-[36px] border border-[#ececee] p-[28px] mb-8">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
                <div>
                    <h2 class="text-[22px] font-semibold text-[#09090b] tracking-tight">
                        Filter & Ekspor Rekapitulasi Laporan
                    </h2>
                    <p class="text-[13px] text-[#52525b] mt-0.5">
                        Tentukan rentang tanggal, kategori, atau kecamatan untuk menghasilkan laporan tercetak formal.
                    </p>
                </div>

                <!-- Tombol Print / Cetak PDF -->
                <button type="button" 
                        onclick="window.print()" 
                        class="bg-[#09090b] hover:bg-[#18181b] text-white px-6 py-3 rounded-[14px] text-[13px] font-medium flex items-center gap-2 transition cursor-pointer select-none shrink-0 shadow-none">
                    <i data-lucide="printer" class="w-4 h-4"></i>
                    <span>Cetak Laporan (Print / PDF)</span>
                </button>
            </div>

            <!-- Form Filter Rentang Tanggal & Kategori -->
            <form action="laporan.php" method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 pt-2">
                <div>
                    <label class="block text-[12px] font-medium text-[#71717a] mb-1">Dari Tanggal</label>
                    <input type="date" name="tgl_mulai" value="<?= htmlspecialchars($tgl_mulai); ?>" class="w-full h-11 px-3 bg-[#fafafa] border border-[#ececee] rounded-[14px] text-[13px] outline-none">
                </div>
                <div>
                    <label class="block text-[12px] font-medium text-[#71717a] mb-1">Sampai Tanggal</label>
                    <input type="date" name="tgl_selesai" value="<?= htmlspecialchars($tgl_selesai); ?>" class="w-full h-11 px-3 bg-[#fafafa] border border-[#ececee] rounded-[14px] text-[13px] outline-none">
                </div>
                <div>
                    <label class="block text-[12px] font-medium text-[#71717a] mb-1">Kategori Masalah</label>
                    <select name="kategori" class="w-full h-11 px-3 bg-[#fafafa] border border-[#ececee] rounded-[14px] text-[13px] outline-none cursor-pointer">
                        <option value="0">Semua Kategori</option>
                        <?php while ($cat = mysqli_fetch_assoc($kategori_opts)): ?>
                            <option value="<?= $cat['id_kategori']; ?>" <?= ($kategori == $cat['id_kategori']) ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($cat['nama_kategori']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-[12px] font-medium text-[#71717a] mb-1">Kecamatan</label>
                    <select name="kecamatan" class="w-full h-11 px-3 bg-[#fafafa] border border-[#ececee] rounded-[14px] text-[13px] outline-none cursor-pointer">
                        <option value="">Semua Kecamatan</option>
                        <?php foreach ($kecamatan_list as $kec): ?>
                            <option value="<?= htmlspecialchars($kec); ?>" <?= ($kecamatan === $kec) ? 'selected' : ''; ?>>
                                Kec. <?= htmlspecialchars($kec); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="flex items-end gap-2">
                    <button type="submit" class="flex-1 h-11 bg-[#09090b] text-white rounded-[14px] text-[13px] font-medium hover:bg-[#18181b] transition">
                        Terapkan
                    </button>
                    <a href="laporan.php" class="h-11 px-3 bg-[#f4f4f5] text-[#71717a] hover:text-[#09090b] rounded-[14px] flex items-center justify-center transition" title="Reset Filter">
                        <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                    </a>
                </div>
            </form>
        </div>

        <!-- 4 Kartu Metrik Hasil Filter Laporan -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <div class="bg-white rounded-[24px] border border-[#ececee] p-5">
                <span class="text-[12px] text-[#71717a] font-medium">Total Laporan</span>
                <div class="text-[28px] font-semibold text-[#09090b] mt-1"><?= $total_rekap; ?></div>
            </div>
            <div class="bg-white rounded-[24px] border border-[#ececee] p-5">
                <span class="text-[12px] text-[#71717a] font-medium">Tuntas Selesai</span>
                <div class="text-[28px] font-semibold text-emerald-600 mt-1"><?= $selesai_rekap; ?></div>
            </div>
            <div class="bg-white rounded-[24px] border border-[#ececee] p-5">
                <span class="text-[12px] text-[#71717a] font-medium">Sedang Diproses</span>
                <div class="text-[28px] font-semibold text-[#18181b] mt-1"><?= $proses_rekap; ?></div>
            </div>
            <div class="bg-white rounded-[24px] border border-[#ececee] p-5">
                <span class="text-[12px] text-[#71717a] font-medium">Rata-rata Skor SKM</span>
                <div class="text-[28px] font-semibold text-amber-500 mt-1">★ <?= $avg_rating_filtered; ?></div>
            </div>
        </div>

        <!-- Tabel Laporan Hasil Filter (Printable) -->
        <div class="bg-white rounded-[36px] border border-[#ececee] p-[28px] overflow-hidden card-awesomic">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-[12px]">
                    <thead>
                        <tr class="border-b-2 border-[#ececee] uppercase font-bold text-[#71717a]">
                            <th class="py-3 px-2">No</th>
                            <th class="py-3 px-3">Kode Tiket</th>
                            <th class="py-3 px-3">Tanggal</th>
                            <th class="py-3 px-4">Judul Pengaduan</th>
                            <th class="py-3 px-3">Kategori & OPD</th>
                            <th class="py-3 px-3">Lokasi (Kecamatan)</th>
                            <th class="py-3 px-3">Pelapor</th>
                            <th class="py-3 px-3 text-center">Status</th>
                            <th class="py-3 px-2 text-center">SKM</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#ececee]">
                        <?php if (count($data_rows) > 0): ?>
                            <?php $no = 1; foreach ($data_rows as $row): ?>
                                <tr class="hover:bg-[#fafafa] transition">
                                    <td class="py-3 px-2 text-[#71717a] font-medium"><?= $no++; ?></td>
                                    <td class="py-3 px-3 font-mono font-bold text-[#09090b] whitespace-nowrap">
                                        <?= htmlspecialchars($row['kode_tracking']); ?>
                                    </td>
                                    <td class="py-3 px-3 text-[#52525b] whitespace-nowrap">
                                        <?= format_tanggal_pendek($row['tanggal_lapor']); ?>
                                    </td>
                                    <td class="py-3 px-4 max-w-xs font-medium text-[#18181b]">
                                        <?= htmlspecialchars($row['judul_laporan']); ?>
                                    </td>
                                    <td class="py-3 px-3">
                                        <div class="font-medium text-[#09090b]"><?= htmlspecialchars($row['nama_kategori']); ?></div>
                                        <div class="text-[11px] text-[#71717a]"><?= htmlspecialchars($row['instansi_tujuan']); ?></div>
                                    </td>
                                    <td class="py-3 px-3 whitespace-nowrap">
                                        Kec. <?= htmlspecialchars($row['kecamatan_lokasi']); ?>
                                    </td>
                                    <td class="py-3 px-3 whitespace-nowrap">
                                        <?php if ($row['is_anonim'] == 1): ?>
                                            <?= $isAdmin ? htmlspecialchars($row['nama_lengkap']) . ' <span class="text-[#ff5a00]">[Anonim]</span>' : '<span class="italic text-[#71717a]">Anonim</span>'; ?>
                                        <?php else: ?>
                                            <?= htmlspecialchars($row['nama_lengkap']); ?>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-3 px-3 text-center whitespace-nowrap">
                                        <?= render_status_badge($row['status']); ?>
                                    </td>
                                    <td class="py-3 px-2 text-center whitespace-nowrap font-bold text-amber-600">
                                        <?= !empty($row['rating_kepuasan']) ? $row['rating_kepuasan'] . ' ★' : '-'; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="py-8 text-center text-[#71717a]">
                                    Tidak ada data laporan yang cocok dengan rentang filter yang dipilih.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- ===================================================================== -->
            <!-- LEMBAR PENGESAHAN RESMI PETUGAS (HANYA MUNCUL SAAT CETAK / PDF) -->
            <!-- ===================================================================== -->
            <div class="hidden print:flex justify-between items-start mt-12 pt-8 border-t border-black text-[12px]">
                <div class="w-1/2">
                    <p class="text-gray-700">Catatan:</p>
                    <p class="text-gray-600 italic mt-1">
                        Dokumen ini digenerate secara otomatis melalui Sistem Informasi Pengaduan Masyarakat (ePengaduan) Pemerintah Kabupaten Sukoharjo sebagai bukti rekapitulasi pelayanan publik yang transparan dan akuntabel.
                    </p>
                </div>
                <div class="w-64 text-center">
                    <p>Sukoharjo, <?= format_tanggal_pendek(date('Y-m-d')); ?></p>
                    <p class="font-bold mt-1">Kepala Dinas Komunikasi dan Informatika</p>
                    <p class="font-bold">Kabupaten Sukoharjo</p>
                    <div class="h-20"></div>
                    <p class="font-bold underline">JACOB PURWOKO, S.Sos., M.Si.</p>
                    <p class="text-[11px] text-gray-700">Pembina Utama Muda (IV/c)</p>
                    <p class="text-[11px] text-gray-700">NIP. 19710815 199303 1 004</p>
                </div>
            </div>
        </div>

    </main>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

