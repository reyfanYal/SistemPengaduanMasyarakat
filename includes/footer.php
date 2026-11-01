<?php
/**
 * ==============================================================================
 * FOOTER & SCRIPTS INITIALIZATION (AWESOMIC DESIGN SYSTEM)
 * ePengaduan Kabupaten Sukoharjo - UTS Pemrograman Web 2
 * ==============================================================================
 */
?>
    <!-- Global Footer -->
    <footer class="mt-16 pt-6 border-t border-[#ececee] flex flex-col sm:flex-row items-center justify-between text-[13px] text-[#71717a] gap-3 pb-8">
        <div class="flex items-center gap-2">
            <span class="font-medium text-[#18181b]">ePengaduan Sukoharjo</span>
            <span>&bull;</span>
            <span>Pemerintah Kabupaten Sukoharjo &copy; <?= date('Y'); ?></span>
        </div>
        <div class="flex items-center gap-4">
            <span>UTS Pemrograman Web 2 (Tema 13)</span>
            <span>&bull;</span>
            <span class="text-[#09090b] font-medium">Sukoharjo Makmur</span>
        </div>
    </footer>

    <!-- Inisialisasi Ikon Lucide -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        });
    </script>
</body>
</html>

