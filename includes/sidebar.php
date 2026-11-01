<?php
/**
 * ==============================================================================
 * FIXED LEFT SIDEBAR NAVIGATION (AWESOMIC DESIGN SYSTEM)
 * ePengaduan Kabupaten Sukoharjo - UTS Pemrograman Web 2
 * ==============================================================================
 */

// Cek file aktif saat ini untuk penandaan status menu
$current_page = basename($_SERVER['PHP_SELF']);
$user = current_user();
$isAdmin = is_admin();
?>

<!-- Fixed Left Sidebar (260px) -->
<aside class="w-[260px] fixed inset-y-0 left-0 bg-white border-r border-[#ececee] p-6 flex flex-col justify-between z-30 select-none">
    <div class="flex flex-col">
        <!-- Brand Header Section -->
        <div class="mb-8">
            <a href="index.php" class="flex flex-col group">
                <div class="flex items-center gap-2 mb-1">
                    <span class="text-[20px] font-semibold text-[#09090b] tracking-tight group-hover:text-black">
                        Lapor Sukoharjo
                    </span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center bg-[#ff5a00] text-white px-2 py-0.5 rounded-[12px] text-[10px] uppercase font-bold tracking-wider leading-none">
                        KAB. SUKOHARJO
                    </span>
                    <span class="text-[11px] font-medium text-[#71717a]">Portal Resmi Warga</span>
                </div>
            </a>
        </div>

        <!-- Navigation Menu Links -->
        <nav class="flex flex-col gap-1.5" aria-label="Menu Utama">
            <!-- 1. Dashboard -->
            <a href="index.php" 
               class="<?= ($current_page == 'index.php') ? 'bg-[#09090b] text-white font-medium' : 'text-[#3f3f46] hover:bg-[#f4f4f5] font-normal'; ?> rounded-[14px] px-4 py-3 text-[14px] flex items-center gap-3 transition">
                <i data-lucide="layout-dashboard" class="w-4 h-4 <?= ($current_page == 'index.php') ? 'text-white' : 'text-[#71717a]'; ?>"></i>
                <span>Dashboard</span>
            </a>

            <!-- 2. Data Pengaduan -->
            <a href="pengaduan.php" 
               class="<?= in_array($current_page, ['pengaduan.php', 'edit_pengaduan.php', 'detail_pengaduan.php']) ? 'bg-[#09090b] text-white font-medium' : 'text-[#3f3f46] hover:bg-[#f4f4f5] font-normal'; ?> rounded-[14px] px-4 py-3 text-[14px] flex items-center gap-3 transition">
                <i data-lucide="inbox" class="w-4 h-4 <?= in_array($current_page, ['pengaduan.php', 'edit_pengaduan.php', 'detail_pengaduan.php']) ? 'text-white' : 'text-[#71717a]'; ?>"></i>
                <span>Data Pengaduan</span>
            </a>

            <!-- 3. Buat Pengaduan -->
            <a href="tambah_pengaduan.php" 
               class="<?= ($current_page == 'tambah_pengaduan.php') ? 'bg-[#09090b] text-white font-medium' : 'text-[#3f3f46] hover:bg-[#f4f4f5] font-normal'; ?> rounded-[14px] px-4 py-3 text-[14px] flex items-center gap-3 transition">
                <i data-lucide="plus-circle" class="w-4 h-4 <?= ($current_page == 'tambah_pengaduan.php') ? 'text-white' : 'text-[#71717a]'; ?>"></i>
                <span>Buat Pengaduan</span>
            </a>

            <!-- 4. Tindak Lanjut -->
            <a href="tindak_lanjut.php" 
               class="<?= ($current_page == 'tindak_lanjut.php') ? 'bg-[#09090b] text-white font-medium' : 'text-[#3f3f46] hover:bg-[#f4f4f5] font-normal'; ?> rounded-[14px] px-4 py-3 text-[14px] flex items-center gap-3 transition">
                <i data-lucide="check-square" class="w-4 h-4 <?= ($current_page == 'tindak_lanjut.php') ? 'text-white' : 'text-[#71717a]'; ?>"></i>
                <span>Tindak Lanjut</span>
            </a>

            <?php if ($isAdmin): ?>
            <!-- Separator Menu Khusus Admin -->
            <div class="pt-3 pb-1 px-4">
                <span class="text-[11px] font-semibold text-[#a1a1aa] uppercase tracking-wider">Master Data (Admin)</span>
            </div>

            <!-- 5. Data Kategori (Admin Only) -->
            <a href="kategori.php" 
               class="<?= ($current_page == 'kategori.php') ? 'bg-[#09090b] text-white font-medium' : 'text-[#3f3f46] hover:bg-[#f4f4f5] font-normal'; ?> rounded-[14px] px-4 py-3 text-[14px] flex items-center gap-3 transition">
                <i data-lucide="tags" class="w-4 h-4 <?= ($current_page == 'kategori.php') ? 'text-white' : 'text-[#71717a]'; ?>"></i>
                <span>Data Kategori</span>
            </a>

            <!-- 6. Data Masyarakat (Admin Only) -->
            <a href="masyarakat.php" 
               class="<?= ($current_page == 'masyarakat.php') ? 'bg-[#09090b] text-white font-medium' : 'text-[#3f3f46] hover:bg-[#f4f4f5] font-normal'; ?> rounded-[14px] px-4 py-3 text-[14px] flex items-center gap-3 transition">
                <i data-lucide="users" class="w-4 h-4 <?= ($current_page == 'masyarakat.php') ? 'text-white' : 'text-[#71717a]'; ?>"></i>
                <span>Data Masyarakat</span>
            </a>
            <?php endif; ?>

            <!-- 7. Laporan & Rekap -->
            <a href="laporan.php" 
               class="<?= ($current_page == 'laporan.php') ? 'bg-[#09090b] text-white font-medium' : 'text-[#3f3f46] hover:bg-[#f4f4f5] font-normal'; ?> rounded-[14px] px-4 py-3 text-[14px] flex items-center gap-3 transition">
                <i data-lucide="file-text" class="w-4 h-4 <?= ($current_page == 'laporan.php') ? 'text-white' : 'text-[#71717a]'; ?>"></i>
                <span>Laporan & Rekap</span>
            </a>
        </nav>
    </div>

    <!-- Bottom User Section & Logout -->
    <div class="pt-4 border-t border-[#ececee] flex flex-col gap-3">
        <?php if ($user): ?>
            <!-- Info Pengguna Aktif -->
            <div class="flex items-center gap-3 px-2 py-1">
                <div class="w-9 h-9 rounded-full bg-[#18181b] text-white flex items-center justify-center font-bold text-[13px] shrink-0">
                    <?= strtoupper(substr($user['nama_lengkap'], 0, 2)); ?>
                </div>
                <div class="flex flex-col min-w-0">
                    <span class="text-[14px] font-semibold text-[#09090b] truncate" title="<?= htmlspecialchars($user['nama_lengkap']); ?>">
                        <?= htmlspecialchars($user['nama_lengkap']); ?>
                    </span>
                    <span class="text-[12px] text-[#71717a] truncate">
                        <?= $isAdmin ? 'Admin OPD Sukoharjo' : 'Warga (' . htmlspecialchars($user['kecamatan']) . ')'; ?>
                    </span>
                </div>
            </div>

            <!-- Tombol Keluar (Logout) -->
            <a href="logout.php" 
               onclick="return confirm('Apakah Anda yakin ingin keluar dari sistem?')"
               class="rounded-[14px] px-4 py-2.5 text-[13px] font-medium text-[#71717a] hover:text-[#09090b] hover:bg-[#f4f4f5] flex items-center gap-3 transition">
                <i data-lucide="log-out" class="w-4 h-4"></i>
                <span>Keluar Akun</span>
            </a>
        <?php else: ?>
            <!-- Pengunjung Tamu / Belum Login -->
            <a href="login.php" 
               class="bg-[#09090b] text-white rounded-[14px] px-4 py-3 text-[14px] font-medium flex items-center justify-center gap-2 hover:bg-[#18181b] transition">
                <i data-lucide="log-in" class="w-4 h-4"></i>
                <span>Masuk Portal</span>
            </a>
        <?php endif; ?>
    </div>
</aside>

