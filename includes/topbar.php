<?php
/**
 * ==============================================================================
 * TOPBAR UTILITY HEADER (QUICK TRACKING SEARCH & PROFILE PILL)
 * ePengaduan Kabupaten Sukoharjo - UTS Pemrograman Web 2
 * ==============================================================================
 */
$user = current_user();
$isAdmin = is_admin();
?>

<!-- Top Utility Bar -->
<header class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8 pb-6 border-b border-[#ececee]">
    <!-- Breadcrumb / Headline Context -->
    <div class="flex flex-col">
        <div class="flex items-center gap-2 mb-1">
            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
            <span class="text-[12px] font-semibold text-[#71717a] uppercase tracking-wider">
                Sistem Penanganan Pengaduan Terpadu
            </span>
        </div>
        <h1 class="text-[26px] font-semibold text-[#09090b] tracking-tight">
            <?= htmlspecialchars($page_heading ?? $page_title ?? 'Dashboard'); ?>
        </h1>
    </div>

    <!-- Quick Tracking Search & Role Badge -->
    <div class="flex flex-wrap items-center gap-3">
        <!-- Quick Tracking Search Form -->
        <form action="detail_pengaduan.php" method="GET" class="relative flex items-center min-w-[280px] sm:min-w-[320px]">
            <i data-lucide="search" class="w-4 h-4 text-[#71717a] absolute left-3.5 pointer-events-none"></i>
            <input type="text" 
                   name="kode" 
                   placeholder="Cek tiket (misal: SKH-2026-0001)..." 
                   required
                   class="w-full h-11 pl-10 pr-12 bg-white text-[#18181b] placeholder:text-[#71717a] rounded-[14px] border border-[#ececee] focus:border-[#09090b] text-[13px] outline-none transition">
            <button type="submit" 
                    title="Lacak Tiket"
                    class="absolute right-1.5 bg-[#09090b] hover:bg-[#27272a] text-white rounded-[10px] w-8 h-8 flex items-center justify-center transition">
                <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
            </button>
        </form>

        <!-- Role Indicator Pill -->
        <div class="flex items-center gap-2.5 bg-white border border-[#ececee] rounded-[14px] px-3.5 py-2">
            <div class="w-7 h-7 rounded-full bg-[#f4f4f5] text-[#09090b] flex items-center justify-center text-[12px] font-semibold">
                <i data-lucide="shield" class="w-3.5 h-3.5"></i>
            </div>
            <div class="flex flex-col">
                <span class="text-[12px] font-semibold text-[#09090b] leading-tight">
                    <?= $isAdmin ? 'Admin OPD / Diskominfo' : ($user ? 'Warga Sukoharjo' : 'Publik / Tamu'); ?>
                </span>
                <span class="text-[11px] text-[#71717a]">
                    <?= $user ? htmlspecialchars($user['kecamatan']) : 'Kab. Sukoharjo'; ?>
                </span>
            </div>
        </div>
    </div>
</header>

