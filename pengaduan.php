<?php
/**
 * ==============================================================================
 * MODUL DATA PENGADUAN (READ, SEARCH, FILTER & DELETE)
 * ePengaduan Kabupaten Sukoharjo - UTS Pemrograman Web 2
 * ==============================================================================
 */

require_once __DIR__ . '/config/koneksi.php';
require_once __DIR__ . '/config/auth.php';

require_login();

$user = current_user();
$isAdmin = is_admin();
$userId = current_user_id();

// Penanganan Operasi Hapus Data Pengaduan (DELETE)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_pengaduan') {
    $id_hapus = (int)($_POST['id_pengaduan'] ?? 0);

    if ($id_hapus > 0) {
        // Cek kepemilikan atau hak admin
        $stmt_check = mysqli_prepare($koneksi, "SELECT id_user, foto_bukti_awal, status FROM pengaduan WHERE id_pengaduan = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt_check, "i", $id_hapus);
        mysqli_stmt_execute($stmt_check);
        $res_check = mysqli_stmt_get_result($stmt_check);
        $data_aduan = mysqli_fetch_assoc($res_check);
        mysqli_stmt_close($stmt_check);

        if ($data_aduan) {
            // Hanya Admin atau Warga pemilik aduan (selama status masih 'Baru') yang boleh menghapus
            if ($isAdmin || ($data_aduan['id_user'] == $userId && $data_aduan['status'] === 'Baru')) {
                // Hapus file fisik bukti awal jika ada di server
                if (!empty($data_aduan['foto_bukti_awal'])) {
                    $file_path = __DIR__ . '/assets/uploads/awal/' . $data_aduan['foto_bukti_awal'];
                    if (file_exists($file_path)) {
                        @unlink($file_path);
                    }
                }

                // Hapus data dari basis data (CASCADE akan menghapus tindak_lanjut otomatis)
                $stmt_del = mysqli_prepare($koneksi, "DELETE FROM pengaduan WHERE id_pengaduan = ?");
                mysqli_stmt_bind_param($stmt_del, "i", $id_hapus);
                mysqli_stmt_execute($stmt_del);
                mysqli_stmt_close($stmt_del);

                set_flash('success', 'Data pengaduan berhasil dihapus dari sistem.');
            } else {
                set_flash('error', 'Aksi ditolak. Laporan yang sudah diverifikasi atau diproses tidak dapat dihapus oleh warga.');
            }
        } else {
            set_flash('error', 'Data pengaduan tidak ditemukan.');
        }
    }
    header('Location: pengaduan.php');
    exit;
}

// Parameter Filter & Pencarian
$search   = trim($_GET['search'] ?? '');
$kategori = (int)($_GET['kategori'] ?? 0);
$status   = trim($_GET['status'] ?? '');
$tab      = trim($_GET['tab'] ?? 'all'); // 'all' atau 'saya'

// Bangun Kueri Dinamis dengan Prepared Statement
$where_clauses = ["1=1"];
$params = [];
$types = "";

if ($tab === 'saya' && !$isAdmin) {
    $where_clauses[] = "p.id_user = ?";
    $params[] = $userId;
    $types .= "i";
}

if (!empty($search)) {
    $where_clauses[] = "(p.kode_tracking LIKE ? OR p.judul_laporan LIKE ? OR p.kecamatan_lokasi LIKE ?)";
    $search_param = "%{$search}%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "sss";
}

if ($kategori > 0) {
    $where_clauses[] = "p.id_kategori = ?";
    $params[] = $kategori;
    $types .= "i";
}

if (!empty($status)) {
    $where_clauses[] = "p.status = ?";
    $params[] = $status;
    $types .= "s";
}

$where_sql = implode(" AND ", $where_clauses);
$sql = "SELECT p.*, k.nama_kategori, u.nama_lengkap, u.nik, u.kecamatan as domisili_warga
        FROM pengaduan p
        JOIN kategori k ON p.id_kategori = k.id_kategori
        JOIN users u ON p.id_user = u.id_user
        WHERE {$where_sql}
        ORDER BY p.tanggal_lapor DESC";

$stmt = mysqli_prepare($koneksi, $sql);
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Ambil Kategori untuk dropdown filter
$kategori_opts = mysqli_query($koneksi, "SELECT id_kategori, nama_kategori FROM kategori ORDER BY nama_kategori ASC");

$page_title = 'Data Pengaduan Masyarakat';
$page_heading = 'Kelola & Pantau Pengaduan';

require_once __DIR__ . '/includes/head.php';
?>

<div class="flex min-h-screen">
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>

    <main class="ml-[260px] flex-1 p-8 max-w-[1200px] w-full">
        <?php require_once __DIR__ . '/includes/topbar.php'; ?>
        <?= render_flash_alerts(); ?>

        <!-- Toolbar & Filter Area -->
        <div class="bg-white rounded-[36px] border border-[#ececee] p-[28px] mb-8">
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 mb-6">
                <div>
                    <h2 class="text-[22px] font-semibold text-[#09090b] tracking-tight">
                        Daftar Laporan Pengaduan
                    </h2>
                    <p class="text-[13px] text-[#52525b] mt-0.5">
                        Telusuri, saring status, dan kelola seluruh aspirasi pelayanan publik masyarakat.
                    </p>
                </div>

                <!-- Tab Pemilihan (Khusus Warga) & Tombol Tambah -->
                <div class="flex flex-wrap items-center gap-3">
                    <?php if (!$isAdmin): ?>
                        <div class="bg-[#f4f4f5] p-1 rounded-[14px] flex items-center text-[13px]">
                            <a href="pengaduan.php?tab=all" class="<?= ($tab !== 'saya') ? 'bg-white text-[#09090b] font-medium' : 'text-[#71717a] hover:text-[#09090b]'; ?> px-3 py-1.5 rounded-[10px] transition">
                                Semua Laporan
                            </a>
                            <a href="pengaduan.php?tab=saya" class="<?= ($tab === 'saya') ? 'bg-white text-[#09090b] font-medium' : 'text-[#71717a] hover:text-[#09090b]'; ?> px-3 py-1.5 rounded-[10px] transition">
                                Aduan Saya
                            </a>
                        </div>
                    <?php endif; ?>

                    <a href="tambah_pengaduan.php" class="bg-[#09090b] hover:bg-[#18181b] text-white px-5 py-2.5 rounded-[14px] text-[13px] font-medium flex items-center gap-2 transition">
                        <i data-lucide="plus-circle" class="w-4 h-4"></i>
                        <span>Buat Pengaduan Baru</span>
                    </a>
                </div>
            </div>

            <!-- Form Pencarian & Multi-Filter Dropdown -->
            <form action="pengaduan.php" method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 pt-2">
                <?php if ($tab === 'saya'): ?>
                    <input type="hidden" name="tab" value="saya">
                <?php endif; ?>

                <!-- Input Pencarian -->
                <div class="relative sm:col-span-2">
                    <i data-lucide="search" class="w-4 h-4 text-[#71717a] absolute left-3.5 top-3.5 pointer-events-none"></i>
                    <input type="text" 
                           name="search" 
                           value="<?= htmlspecialchars($search); ?>" 
                           placeholder="Cari kode tiket, judul aduan, atau kecamatan..." 
                           class="w-full h-11 pl-10 pr-4 bg-[#fafafa] border border-[#ececee] focus:border-[#09090b] rounded-[14px] text-[13px] text-[#18181b] outline-none transition">
                </div>

                <!-- Dropdown Kategori -->
                <div>
                    <select name="kategori" 
                            class="w-full h-11 px-3 bg-[#fafafa] border border-[#ececee] focus:border-[#09090b] rounded-[14px] text-[13px] text-[#18181b] outline-none transition cursor-pointer">
                        <option value="0">Semua Kategori</option>
                        <?php while ($cat = mysqli_fetch_assoc($kategori_opts)): ?>
                            <option value="<?= $cat['id_kategori']; ?>" <?= ($kategori == $cat['id_kategori']) ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($cat['nama_kategori']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <!-- Dropdown Status & Tombol Reset -->
                <div class="flex items-center gap-2">
                    <select name="status" 
                            class="flex-1 h-11 px-3 bg-[#fafafa] border border-[#ececee] focus:border-[#09090b] rounded-[14px] text-[13px] text-[#18181b] outline-none transition cursor-pointer">
                        <option value="">Semua Status</option>
                        <option value="Baru" <?= ($status === 'Baru') ? 'selected' : ''; ?>>Baru</option>
                        <option value="Verifikasi" <?= ($status === 'Verifikasi') ? 'selected' : ''; ?>>Verifikasi</option>
                        <option value="Diproses" <?= ($status === 'Diproses') ? 'selected' : ''; ?>>Diproses</option>
                        <option value="Selesai" <?= ($status === 'Selesai') ? 'selected' : ''; ?>>Selesai</option>
                        <option value="Ditolak" <?= ($status === 'Ditolak') ? 'selected' : ''; ?>>Ditolak</option>
                    </select>

                    <button type="submit" class="h-11 px-4 bg-[#09090b] text-white rounded-[14px] text-[13px] font-medium hover:bg-[#18181b] transition">
                        Filter
                    </button>
                    <?php if (!empty($search) || $kategori > 0 || !empty($status)): ?>
                        <a href="pengaduan.php<?= ($tab === 'saya') ? '?tab=saya' : ''; ?>" class="h-11 px-3 bg-[#f4f4f5] text-[#71717a] hover:text-[#09090b] rounded-[14px] flex items-center justify-center transition" title="Reset Filter">
                            <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Tabel Data Pengaduan (Awesomic Rounded 36px Card) -->
        <div class="bg-white rounded-[36px] border border-[#ececee] p-[28px] overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-[#ececee] text-[12px] text-[#71717a] uppercase font-semibold">
                            <th class="py-3.5 px-3">Kode Tiket</th>
                            <th class="py-3.5 px-4">Judul & Kategori</th>
                            <th class="py-3.5 px-3">Pelapor & Wilayah</th>
                            <th class="py-3.5 px-3">Status</th>
                            <th class="py-3.5 px-3 text-center">Dukungan</th>
                            <th class="py-3.5 px-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#f4f4f5] text-[13px]">
                        <?php if (mysqli_num_rows($result) > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                <?php
                                    $isOwner = ($row['id_user'] == $userId);
                                    $canEdit = ($isAdmin || ($isOwner && $row['status'] === 'Baru'));
                                    $canDelete = ($isAdmin || ($isOwner && $row['status'] === 'Baru'));
                                ?>
                                <tr class="hover:bg-[#fafafa] transition">
                                    <!-- 1. Kode Tiket -->
                                    <td class="py-4 px-3 whitespace-nowrap">
                                        <div class="font-bold text-[#09090b]">
                                            <?= htmlspecialchars($row['kode_tracking']); ?>
                                        </div>
                                        <div class="text-[11px] text-[#71717a]">
                                            <?= format_tanggal_indo($row['tanggal_lapor']); ?>
                                        </div>
                                    </td>

                                    <!-- 2. Judul & Kategori -->
                                    <td class="py-4 px-4 max-w-xs">
                                        <div class="font-medium text-[#18181b] line-clamp-1" title="<?= htmlspecialchars($row['judul_laporan']); ?>">
                                            <?= htmlspecialchars($row['judul_laporan']); ?>
                                        </div>
                                        <div class="text-[12px] text-[#52525b] mt-0.5">
                                            <?= htmlspecialchars($row['nama_kategori']); ?>
                                        </div>
                                    </td>

                                    <!-- 3. Pelapor & Wilayah (Whistleblower Anonymity Rule) -->
                                    <td class="py-4 px-3 whitespace-nowrap">
                                        <?php if ($row['is_anonim'] == 1): ?>
                                            <?php if ($isAdmin): ?>
                                                <div class="flex items-center gap-1.5 font-medium text-[#18181b]">
                                                    <span><?= htmlspecialchars($row['nama_lengkap']); ?></span>
                                                    <span class="bg-[#fff5f0] text-[#ff5a00] border border-[#ffcca8] text-[10px] font-bold px-1.5 py-0.5 rounded-[6px]">
                                                        Anonim
                                                    </span>
                                                </div>
                                            <?php else: ?>
                                                <div class="text-[#71717a] italic font-medium">
                                                    Disamarkan (Anonim)
                                                </div>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <div class="font-medium text-[#18181b]">
                                                <?= htmlspecialchars($row['nama_lengkap']); ?>
                                            </div>
                                        <?php endif; ?>
                                        <div class="text-[12px] text-[#71717a]">
                                            Kec. <?= htmlspecialchars($row['kecamatan_lokasi']); ?>
                                        </div>
                                    </td>

                                    <!-- 4. Status Badge -->
                                    <td class="py-4 px-3 whitespace-nowrap">
                                        <?= render_status_badge($row['status']); ?>
                                    </td>

                                    <!-- 5. Counter Upvote -->
                                    <td class="py-4 px-3 text-center whitespace-nowrap">
                                        <span class="inline-flex items-center gap-1 bg-[#f4f4f5] text-[#52525b] px-2.5 py-1 rounded-[10px] text-[12px] font-medium">
                                            <i data-lucide="thumbs-up" class="w-3 h-3 text-[#ff5a00]"></i>
                                            <?= (int)$row['jumlah_dukungan']; ?>
                                        </span>
                                    </td>

                                    <!-- 6. Aksi (Detail, Edit, Delete) -->
                                    <td class="py-4 px-3 text-right whitespace-nowrap">
                                        <div class="inline-flex items-center gap-1.5">
                                            <!-- Detail Button -->
                                            <a href="detail_pengaduan.php?id=<?= $row['id_pengaduan']; ?>" 
                                               class="h-8 px-2.5 bg-[#f4f4f5] hover:bg-[#09090b] hover:text-white rounded-[10px] text-[12px] font-medium flex items-center gap-1 transition"
                                               title="Lihat Detail & Timeline">
                                                <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                                <span>Detail</span>
                                            </a>

                                            <!-- Edit Button (Warga saat 'Baru' atau Admin) -->
                                            <?php if ($canEdit): ?>
                                                <a href="edit_pengaduan.php?id=<?= $row['id_pengaduan']; ?>" 
                                                   class="h-8 px-2.5 bg-[#f4f4f5] hover:bg-[#09090b] hover:text-white rounded-[10px] text-[12px] font-medium flex items-center gap-1 transition"
                                                   title="Ubah Data Pengaduan">
                                                    <i data-lucide="edit-3" class="w-3.5 h-3.5"></i>
                                                </a>
                                            <?php endif; ?>

                                            <!-- Delete Button with Modal Trigger -->
                                            <?php if ($canDelete): ?>
                                                <button type="button" 
                                                        onclick="openDeleteModal('<?= $row['id_pengaduan']; ?>', '<?= htmlspecialchars(addslashes($row['kode_tracking'])); ?>', '<?= htmlspecialchars(addslashes($row['judul_laporan'])); ?>')" 
                                                        class="h-8 px-2.5 bg-[#fee2e2] text-[#991b1b] hover:bg-rose-600 hover:text-white rounded-[10px] text-[12px] font-medium flex items-center gap-1 transition"
                                                        title="Hapus Pengaduan">
                                                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="py-12 text-center text-[#71717a]">
                                    <div class="flex flex-col items-center justify-center gap-2">
                                        <i data-lucide="inbox" class="w-8 h-8 text-[#a1a1aa]"></i>
                                        <span class="text-[14px]">Tidak ada data pengaduan yang sesuai dengan kriteria saringan.</span>
                                        <a href="pengaduan.php" class="text-[13px] text-[#09090b] font-medium underline mt-1">
                                            Tampilkan Seluruh Laporan
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>
</div>

<!-- Modal Dialog Konfirmasi Hapus Awesomic (Custom Vanilla JS) -->
<div id="delete-modal" class="fixed inset-0 bg-black/40 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-[32px] border border-[#ececee] max-w-md w-full p-7">
        <div class="flex items-center gap-3 mb-4">
            <span class="w-10 h-10 rounded-full bg-[#fee2e2] text-[#991b1b] flex items-center justify-center">
                <i data-lucide="alert-triangle" class="w-5 h-5"></i>
            </span>
            <div>
                <h3 class="text-[18px] font-semibold text-[#09090b]">Konfirmasi Hapus</h3>
                <p class="text-[12px] text-[#71717a]">Aksi ini tidak dapat dibatalkan</p>
            </div>
        </div>

        <p class="text-[14px] text-[#52525b] mb-6 leading-relaxed">
            Apakah Anda yakin ingin menghapus data pengaduan <strong id="modal-aduan-code" class="text-[#09090b]"></strong> (<span id="modal-aduan-title"></span>)? Seluruh riwayat tindak lanjut terkait juga akan dihapus.
        </p>

        <form action="pengaduan.php" method="POST" class="flex items-center justify-end gap-3">
            <input type="hidden" name="action" value="delete_pengaduan">
            <input type="hidden" name="id_pengaduan" id="modal-delete-id" value="">

            <button type="button" 
                    onclick="closeDeleteModal()" 
                    class="px-5 py-2.5 rounded-[12px] border border-[#ececee] text-[#52525b] hover:text-[#09090b] hover:bg-[#f4f4f5] text-[13px] font-medium transition">
                Batal
            </button>
            <button type="submit" 
                    class="px-5 py-2.5 rounded-[12px] bg-rose-600 hover:bg-rose-700 text-white text-[13px] font-medium transition">
                Hapus Data
            </button>
        </form>
    </div>
</div>

<script>
    function openDeleteModal(id, code, title) {
        document.getElementById('modal-delete-id').value = id;
        document.getElementById('modal-aduan-code').textContent = code;
        document.getElementById('modal-aduan-title').textContent = title;
        document.getElementById('delete-modal').classList.remove('hidden');
    }

    function closeDeleteModal() {
        document.getElementById('delete-modal').classList.add('hidden');
    }
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

