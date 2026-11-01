<?php
/**
 * ==============================================================================
 * DASHBOARD UTAMA & GIS MAP EXPLORER (AWESOMIC DESIGN SYSTEM)
 * ePengaduan Kabupaten Sukoharjo - UTS Pemrograman Web 2
 * ==============================================================================
 */

require_once __DIR__ . '/config/koneksi.php';
require_once __DIR__ . '/config/auth.php';

// Pastikan pengguna telah login
require_login();

$user = current_user();
$isAdmin = is_admin();

// Ambil Statistik Agregat untuk 4 Kartu Metrik Awesomic
$query_stats = "SELECT 
    COUNT(*) as total_aduan,
    SUM(CASE WHEN status = 'Baru' THEN 1 ELSE 0 END) as menunggu_verifikasi,
    SUM(CASE WHEN status IN ('Verifikasi', 'Diproses') THEN 1 ELSE 0 END) as dalam_proses,
    SUM(CASE WHEN status = 'Selesai' THEN 1 ELSE 0 END) as selesai,
    AVG(CASE WHEN status = 'Selesai' AND rating_kepuasan IS NOT NULL THEN rating_kepuasan ELSE NULL END) as avg_rating
FROM pengaduan";
$res_stats = mysqli_query($koneksi, $query_stats);
$stats = mysqli_fetch_assoc($res_stats);

$total_aduan         = (int)($stats['total_aduan'] ?? 0);
$menunggu_verifikasi = (int)($stats['menunggu_verifikasi'] ?? 0);
$dalam_proses        = (int)($stats['dalam_proses'] ?? 0);
$selesai             = (int)($stats['selesai'] ?? 0);
$avg_rating          = $stats['avg_rating'] ? round($stats['avg_rating'], 1) : 4.8;
$persen_selesai      = $total_aduan > 0 ? round(($selesai / $total_aduan) * 100, 1) : 100;

// Ambil Seluruh Data Titik Koordinat Peta (Leaflet GIS)
$query_map = "SELECT p.id_pengaduan, p.kode_tracking, p.judul_laporan, p.kecamatan_lokasi, 
                     p.latitude, p.longitude, p.status, p.tanggal_lapor, k.nama_kategori
              FROM pengaduan p
              JOIN kategori k ON p.id_kategori = k.id_kategori
              ORDER BY p.tanggal_lapor DESC";
$res_map = mysqli_query($koneksi, $query_map);
$map_markers = [];
while ($row = mysqli_fetch_assoc($res_map)) {
    $map_markers[] = [
        'id'        => $row['id_pengaduan'],
        'kode'      => $row['kode_tracking'],
        'judul'     => htmlspecialchars($row['judul_laporan']),
        'kecamatan' => htmlspecialchars($row['kecamatan_lokasi']),
        'lat'       => (float)$row['latitude'],
        'lng'       => (float)$row['longitude'],
        'status'    => $row['status'],
        'kategori'  => htmlspecialchars($row['nama_kategori']),
        'tanggal'   => format_tanggal_indo($row['tanggal_lapor'])
    ];
}

// Ambil 5 Pengaduan Terbaru untuk Tabel Ringkasan
$query_recent = "SELECT p.*, k.nama_kategori, u.nama_lengkap 
                 FROM pengaduan p
                 JOIN kategori k ON p.id_kategori = k.id_kategori
                 JOIN users u ON p.id_user = u.id_user
                 ORDER BY p.tanggal_lapor DESC
                 LIMIT 5";
$res_recent = mysqli_query($koneksi, $query_recent);

$page_title = 'Dashboard & Peta Sebaran';
$page_heading = 'Dashboard & Geospasial Sukoharjo';

require_once __DIR__ . '/includes/head.php';
?>

<div class="flex min-h-screen">
    <!-- 1. Left Sidebar Navigation -->
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>

    <!-- 2. Main Content Area (ml-[260px] max-w-[1200px]) -->
    <main class="ml-[260px] flex-1 p-8 max-w-[1200px] w-full">
        <!-- Top Utility Bar -->
        <?php require_once __DIR__ . '/includes/topbar.php'; ?>

        <!-- Flash Notification Banners -->
        <?= render_flash_alerts(); ?>

        <!-- Welcome Banner Card -->
        <div class="mb-8 p-6 bg-white rounded-[36px] border border-[#ececee] flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
            <div>
                <div class="inline-flex items-center gap-2 text-[11px] font-bold text-[#ff5a00] uppercase tracking-wider mb-1">
                    <span>PORTAL RESMI KABUPATEN SUKOHARJO</span>
                </div>
                <h2 class="text-[22px] font-semibold text-[#09090b]">
                    Sugeng Rawuh, <?= htmlspecialchars($user['nama_lengkap']); ?>
                </h2>
                <p class="text-[14px] text-[#52525b] mt-0.5">
                    <?= $isAdmin ? 'Panel kendali disposisi penanganan pengaduan dinas terpadu se-Kabupaten Sukoharjo.' : 'Pantau perkembangan aspirasi dan laporkan keluhan fasilitas publik di wilayah Anda.'; ?>
                </p>
            </div>
            <div class="flex items-center gap-3">
                <a href="tambah_pengaduan.php" class="bg-[#09090b] hover:bg-[#18181b] text-white px-5 py-3 rounded-[14px] text-[13px] font-medium flex items-center gap-2 transition shrink-0">
                    <i data-lucide="plus-circle" class="w-4 h-4"></i>
                    <span>Buat Pengaduan Baru</span>
                </a>
            </div>
        </div>

        <!-- 4 Awesomic Metric Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            
            <!-- Card 1: Total Aduan -->
            <div class="bg-white rounded-[36px] border border-[#ececee] p-[28px] flex flex-col justify-between min-h-[160px]">
                <div class="flex items-center justify-between">
                    <span class="text-[13px] font-medium text-[#52525b]">Total Aduan</span>
                    <span class="w-8 h-8 rounded-full bg-[#f4f4f5] text-[#18181b] flex items-center justify-center">
                        <i data-lucide="inbox" class="w-4 h-4 text-[#71717a]"></i>
                    </span>
                </div>
                <div>
                    <div class="text-[40px] font-semibold text-[#09090b] tracking-tight leading-none">
                        <?= number_format($total_aduan); ?>
                    </div>
                    <div class="text-[12px] text-[#71717a] mt-2">
                        Tercatat di 12 Kecamatan
                    </div>
                </div>
            </div>

            <!-- Card 2: Menunggu Verifikasi (Sole Vivid Ember Orange #ff5a00 Accent) -->
            <div class="bg-white rounded-[36px] border border-[#ececee] p-[28px] flex flex-col justify-between min-h-[160px]">
                <div class="flex items-center justify-between">
                    <span class="text-[13px] font-medium text-[#52525b]">Menunggu Verifikasi</span>
                    <span class="bg-[#ff5a00] text-white px-2.5 py-1 rounded-[12px] text-[11px] font-bold uppercase tracking-wider">
                        SLA &lt; 24 Jam
                    </span>
                </div>
                <div>
                    <div class="text-[40px] font-semibold text-[#09090b] tracking-tight leading-none">
                        <?= number_format($menunggu_verifikasi); ?>
                    </div>
                    <div class="text-[12px] text-[#ff5a00] font-medium mt-2 flex items-center gap-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-[#ff5a00] animate-pulse"></span>
                        Perlu respon dinas segera
                    </div>
                </div>
            </div>

            <!-- Card 3: Dalam Proses -->
            <div class="bg-white rounded-[36px] border border-[#ececee] p-[28px] flex flex-col justify-between min-h-[160px]">
                <div class="flex items-center justify-between">
                    <span class="text-[13px] font-medium text-[#52525b]">Dalam Proses</span>
                    <span class="w-8 h-8 rounded-full bg-[#f4f4f5] text-[#18181b] flex items-center justify-center">
                        <i data-lucide="clock" class="w-4 h-4 text-[#71717a]"></i>
                    </span>
                </div>
                <div>
                    <div class="text-[40px] font-semibold text-[#09090b] tracking-tight leading-none">
                        <?= number_format($dalam_proses); ?>
                    </div>
                    <div class="text-[12px] text-[#71717a] mt-2">
                        Pengerjaan teknis lapangan OPD
                    </div>
                </div>
            </div>

            <!-- Card 4: Selesai Ditangani -->
            <div class="bg-white rounded-[36px] border border-[#ececee] p-[28px] flex flex-col justify-between min-h-[160px]">
                <div class="flex items-center justify-between">
                    <span class="text-[13px] font-medium text-[#52525b]">Selesai Ditangani</span>
                    <span class="w-8 h-8 rounded-full bg-[#f4f4f5] text-emerald-600 flex items-center justify-center">
                        <i data-lucide="check-circle-2" class="w-4 h-4"></i>
                    </span>
                </div>
                <div>
                    <div class="text-[40px] font-semibold text-[#09090b] tracking-tight leading-none">
                        <?= number_format($selesai); ?>
                    </div>
                    <div class="text-[12px] text-emerald-700 font-medium mt-2 flex items-center gap-1">
                        <span>★ <?= $avg_rating; ?>/5 SKM Kepuasan Warga</span>
                    </div>
                </div>
            </div>

        </div>

        <!-- GIS Map Explorer Card (Leaflet.js) -->
        <div class="bg-white rounded-[36px] border border-[#ececee] p-[28px] mb-8">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-5">
                <div>
                    <h3 class="text-[20px] font-semibold text-[#09090b] tracking-tight">
                        Peta Sebaran Laporan Geospasial Sukoharjo
                    </h3>
                    <p class="text-[13px] text-[#52525b] mt-0.5">
                        Pemetaan koordinat aduan warga berbasis Leaflet.js OpenStreetMap di seluruh 12 kecamatan.
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center gap-1.5 text-[12px] font-medium text-[#52525b] bg-[#f4f4f5] px-3 py-1.5 rounded-[12px] border border-[#ececee]">
                        <span class="w-2 h-2 rounded-full bg-[#ff5a00]"></span>
                        <?= count($map_markers); ?> Titik Koordinat Aktif
                    </span>
                </div>
            </div>

            <!-- Kontainer Peta -->
            <div id="sukoharjo-map" class="w-full h-[420px] rounded-[24px] border border-[#ececee] overflow-hidden z-10"></div>
            
            <div class="mt-4 flex flex-wrap items-center justify-between text-[12px] text-[#71717a] pt-3 border-t border-[#f4f4f5] gap-2">
                <div class="flex items-center gap-4">
                    <span class="flex items-center gap-1.5">
                        <span class="w-2.5 h-2.5 rounded-full bg-[#ff5a00]"></span> Baru / Verifikasi
                    </span>
                    <span class="flex items-center gap-1.5">
                        <span class="w-2.5 h-2.5 rounded-full bg-[#18181b]"></span> Diproses
                    </span>
                    <span class="flex items-center gap-1.5">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span> Selesai
                    </span>
                </div>
                <span>Koordinat Pusat: Sukoharjo (-7.6828, 110.8407)</span>
            </div>
        </div>

        <!-- 2-Column Section: Dark SLA Overview Card + Recent Complaints Table -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- Dark Feature Card: Ringkasan SLA Pelayanan (1 Column) -->
            <div class="bg-[#27272a] text-white rounded-[28px] p-7 flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between mb-4">
                        <span class="bg-[#18181b] text-white text-[11px] font-bold uppercase tracking-wider px-2.5 py-1 rounded-[10px] border border-[#3f3f46]">
                            STANDAR LAYANAN OPD
                        </span>
                        <i data-lucide="shield-check" class="w-5 h-5 text-emerald-400"></i>
                    </div>

                    <h4 class="text-[20px] font-semibold tracking-tight text-white mb-2">
                        Komitmen SLA Pemkab
                    </h4>
                    <p class="text-[13px] text-[#a1a1aa] leading-relaxed mb-6">
                        Target batas waktu penyelesaian tindak lanjut lapangan sesuai Peraturan Bupati Sukoharjo:
                    </p>

                    <div class="space-y-3.5 text-[13px]">
                        <div class="p-3 bg-[#18181b] rounded-[16px] border border-[#3f3f46]/60 flex items-center justify-between">
                            <span class="text-[#d4d4d8]">Infrastruktur & Jalan</span>
                            <span class="font-bold text-white">Maks 5 Hari</span>
                        </div>
                        <div class="p-3 bg-[#18181b] rounded-[16px] border border-[#3f3f46]/60 flex items-center justify-between">
                            <span class="text-[#d4d4d8]">Penerangan Jalan (PJU)</span>
                            <span class="font-bold text-[#ff5a00]">Maks 2 Hari</span>
                        </div>
                        <div class="p-3 bg-[#18181b] rounded-[16px] border border-[#3f3f46]/60 flex items-center justify-between">
                            <span class="text-[#d4d4d8]">Persampahan & Lingkungan</span>
                            <span class="font-bold text-[#ff5a00]">Maks 2 Hari</span>
                        </div>
                        <div class="p-3 bg-[#18181b] rounded-[16px] border border-[#3f3f46]/60 flex items-center justify-between">
                            <span class="text-[#d4d4d8]">Ketertiban Umum</span>
                            <span class="font-bold text-emerald-400">1x24 Jam</span>
                        </div>
                    </div>
                </div>

                <div class="mt-6 pt-4 border-t border-[#3f3f46] text-[12px] text-[#a1a1aa]">
                    Transparansi penuh terintegrasi dengan SP4N-LAPOR!
                </div>
            </div>

            <!-- Recent Complaints Table (2 Columns) -->
            <div class="lg:col-span-2 bg-white rounded-[36px] border border-[#ececee] p-[28px] flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between mb-5">
                        <div>
                            <h3 class="text-[20px] font-semibold text-[#09090b] tracking-tight">
                                Aduan Warga Terbaru
                            </h3>
                            <p class="text-[13px] text-[#52525b] mt-0.5">
                                5 laporan masyarakat terakhir yang masuk ke portal pengaduan.
                            </p>
                        </div>
                        <a href="pengaduan.php" class="text-[13px] font-medium text-[#09090b] hover:underline flex items-center gap-1">
                            <span>Lihat Semua</span>
                            <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                        </a>
                    </div>

                    <!-- Table Container -->
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="border-b border-[#ececee] text-[12px] text-[#71717a] uppercase font-semibold">
                                    <th class="py-3 px-2">Kode Tiket</th>
                                    <th class="py-3 px-3">Judul & Kategori</th>
                                    <th class="py-3 px-3">Kecamatan</th>
                                    <th class="py-3 px-3">Status</th>
                                    <th class="py-3 px-2 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[#f4f4f5] text-[13px]">
                                <?php if (mysqli_num_rows($res_recent) > 0): ?>
                                    <?php while ($row = mysqli_fetch_assoc($res_recent)): ?>
                                        <tr class="hover:bg-[#fafafa] transition">
                                            <td class="py-3 px-2 font-semibold text-[#09090b] whitespace-nowrap">
                                                <?= htmlspecialchars($row['kode_tracking']); ?>
                                            </td>
                                            <td class="py-3 px-3">
                                                <div class="font-medium text-[#18181b] line-clamp-1">
                                                    <?= htmlspecialchars($row['judul_laporan']); ?>
                                                </div>
                                                <div class="text-[11px] text-[#71717a]">
                                                    <?= htmlspecialchars($row['nama_kategori']); ?>
                                                </div>
                                            </td>
                                            <td class="py-3 px-3 text-[#52525b] whitespace-nowrap">
                                                <?= htmlspecialchars($row['kecamatan_lokasi']); ?>
                                            </td>
                                            <td class="py-3 px-3 whitespace-nowrap">
                                                <?= render_status_badge($row['status']); ?>
                                            </td>
                                            <td class="py-3 px-2 text-right whitespace-nowrap">
                                                <a href="detail_pengaduan.php?id=<?= $row['id_pengaduan']; ?>" 
                                                   class="inline-flex items-center gap-1 bg-[#f4f4f5] hover:bg-[#09090b] hover:text-white px-2.5 py-1.5 rounded-[10px] text-[12px] font-medium transition">
                                                    <span>Detail</span>
                                                    <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="py-8 text-center text-[#71717a]">
                                            Belum ada data pengaduan yang tersimpan.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="pt-4 border-t border-[#f4f4f5] text-right">
                    <a href="tambah_pengaduan.php" class="text-[13px] text-[#09090b] font-medium hover:underline">
                        + Laporkan Masalah Baru di Lingkungan Anda
                    </a>
                </div>
            </div>

        </div>

    </main>
</div>

<!-- Script Peta Leaflet.js Interaktif Sukoharjo -->
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Inisialisasi Peta Leaflet berpusat di Kabupaten Sukoharjo
        const sukoharjoCoords = [-7.6828, 110.8407];
        const map = L.map('sukoharjo-map').setView(sukoharjoCoords, 11);

        // Tambahkan Tile Layer OpenStreetMap
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 18,
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);

        // Data Titik Aduan dari PHP
        const markersData = <?= json_encode($map_markers); ?>;

        markersData.forEach(function (item) {
            if (item.lat && item.lng) {
                // Tentukan warna titik berdasarkan status
                let markerColor = '#ff5a00';
                if (item.status === 'Selesai') markerColor = '#10b981';
                else if (item.status === 'Diproses') markerColor = '#18181b';

                // Gunakan CircleMarker bergaya modern Awesomic
                const marker = L.circleMarker([item.lat, item.lng], {
                    radius: 8,
                    fillColor: markerColor,
                    color: '#ffffff',
                    weight: 2,
                    opacity: 1,
                    fillOpacity: 0.95
                }).addTo(map);

                // Konten Popup Peta
                const popupContent = `
                    <div style="min-width: 220px; font-family: 'DM Sans', sans-serif;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                            <span style="font-size: 11px; font-weight: 700; color: #ff5a00;">${item.kode}</span>
                            <span style="font-size: 11px; background: #f4f4f5; padding: 2px 6px; border-radius: 6px; font-weight: 500;">${item.status}</span>
                        </div>
                        <h4 style="font-size: 13px; font-weight: 600; color: #09090b; margin: 0 0 4px 0; line-height: 1.3;">${item.judul}</h4>
                        <p style="font-size: 12px; color: #52525b; margin: 0 0 8px 0;">Kec. ${item.kecamatan} &bull; ${item.kategori}</p>
                        <a href="detail_pengaduan.php?id=${item.id}" style="display: inline-block; width: 100%; text-align: center; background: #09090b; color: #ffffff; text-decoration: none; padding: 6px 10px; border-radius: 8px; font-size: 11px; font-weight: 500;">
                            Lihat Detail Aduan &rarr;
                        </a>
                    </div>
                `;
                marker.bindPopup(popupContent);
            }
        });
    });
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

