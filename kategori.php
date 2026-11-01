<?php
/**
 * ==============================================================================
 * CRUD MASTER DATA KATEGORI PENGADUAN & TARGET SLA (ADMIN ONLY)
 * ePengaduan Kabupaten Sukoharjo - UTS Pemrograman Web 2
 * ==============================================================================
 */

require_once __DIR__ . '/config/koneksi.php';
require_once __DIR__ . '/config/auth.php';

require_admin();

$user = current_user();
$errors = [];

// 1. TAMBAH KATEGORI BARU (CREATE)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_kategori') {
    $nama_kategori   = trim($_POST['nama_kategori'] ?? '');
    $instansi_tujuan = trim($_POST['instansi_tujuan'] ?? '');
    $target_sla_hari = (int)($_POST['target_sla_hari'] ?? 3);
    $deskripsi       = trim($_POST['deskripsi'] ?? '');

    if (empty($nama_kategori)) {
        $errors['nama_kategori'] = 'Nama kategori wajib diisi.';
    }
    if (empty($instansi_tujuan)) {
        $errors['instansi_tujuan'] = 'Instansi tujuan penanggung jawab wajib diisi.';
    }
    if ($target_sla_hari <= 0) {
        $target_sla_hari = 3;
    }

    if (empty($errors)) {
        $stmt = mysqli_prepare($koneksi, "INSERT INTO kategori (nama_kategori, instansi_tujuan, target_sla_hari, deskripsi) VALUES (?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "ssis", $nama_kategori, $instansi_tujuan, $target_sla_hari, $deskripsi);
        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            set_flash('success', "Kategori '{$nama_kategori}' berhasil ditambahkan.");
            header('Location: kategori.php');
            exit;
        } else {
            $errors['general'] = 'Gagal menyimpan kategori baru: ' . mysqli_error($koneksi);
            mysqli_stmt_close($stmt);
        }
    }
}

// 2. UBAH KATEGORI (UPDATE)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_kategori') {
    $id_kategori     = (int)($_POST['id_kategori'] ?? 0);
    $nama_kategori   = trim($_POST['nama_kategori'] ?? '');
    $instansi_tujuan = trim($_POST['instansi_tujuan'] ?? '');
    $target_sla_hari = (int)($_POST['target_sla_hari'] ?? 3);
    $deskripsi       = trim($_POST['deskripsi'] ?? '');

    if ($id_kategori > 0 && !empty($nama_kategori) && !empty($instansi_tujuan)) {
        $stmt = mysqli_prepare($koneksi, "UPDATE kategori SET nama_kategori = ?, instansi_tujuan = ?, target_sla_hari = ?, deskripsi = ? WHERE id_kategori = ?");
        mysqli_stmt_bind_param($stmt, "ssisi", $nama_kategori, $instansi_tujuan, $target_sla_hari, $deskripsi, $id_kategori);
        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            set_flash('success', "Kategori '{$nama_kategori}' berhasil diperbarui.");
            header('Location: kategori.php');
            exit;
        } else {
            $errors['general'] = 'Gagal memperbarui kategori.';
            mysqli_stmt_close($stmt);
        }
    }
}

// 3. HAPUS KATEGORI (DELETE)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_kategori') {
    $id_kategori = (int)($_POST['id_kategori'] ?? 0);

    if ($id_kategori > 0) {
        // Cek apakah ada aduan yang menggunakan kategori ini
        $stmt_check = mysqli_prepare($koneksi, "SELECT COUNT(*) as count FROM pengaduan WHERE id_kategori = ?");
        mysqli_stmt_bind_param($stmt_check, "i", $id_kategori);
        mysqli_stmt_execute($stmt_check);
        $res_check = mysqli_stmt_get_result($stmt_check);
        $row_check = mysqli_fetch_assoc($res_check);
        mysqli_stmt_close($stmt_check);

        if ($row_check['count'] > 0) {
            set_flash('error', "Kategori tidak dapat dihapus karena masih digunakan oleh {$row_check['count']} data pengaduan masyarakat.");
        } else {
            $stmt_del = mysqli_prepare($koneksi, "DELETE FROM kategori WHERE id_kategori = ?");
            mysqli_stmt_bind_param($stmt_del, "i", $id_kategori);
            mysqli_stmt_execute($stmt_del);
            mysqli_stmt_close($stmt_del);
            set_flash('success', 'Kategori pengaduan berhasil dihapus.');
        }
    }
    header('Location: kategori.php');
    exit;
}

// 4. READ & PENCARIAN
$search = trim($_GET['search'] ?? '');
$sql = "SELECT k.*, COUNT(p.id_pengaduan) as total_aduan 
        FROM kategori k 
        LEFT JOIN pengaduan p ON k.id_kategori = p.id_kategori ";
if (!empty($search)) {
    $sql .= "WHERE k.nama_kategori LIKE ? OR k.instansi_tujuan LIKE ? ";
}
$sql .= "GROUP BY k.id_kategori ORDER BY k.id_kategori ASC";

$stmt = mysqli_prepare($koneksi, $sql);
if (!empty($search)) {
    $search_param = "%{$search}%";
    mysqli_stmt_bind_param($stmt, "ss", $search_param, $search_param);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$page_title = 'Data Kategori Pengaduan';
$page_heading = 'Master Data Kategori & Target SLA';

require_once __DIR__ . '/includes/head.php';
?>

<div class="flex min-h-screen">
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>

    <main class="ml-[260px] flex-1 p-8 max-w-[1200px] w-full">
        <?php require_once __DIR__ . '/includes/topbar.php'; ?>
        <?= render_flash_alerts(); ?>

        <!-- Toolbar & Tambah Kategori Button -->
        <div class="bg-white rounded-[36px] border border-[#ececee] p-[28px] mb-8">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h2 class="text-[22px] font-semibold text-[#09090b] tracking-tight">
                        Daftar Kategori Masalah
                    </h2>
                    <p class="text-[13px] text-[#52525b] mt-0.5">
                        Konfigurasi bidang aduan, OPD pelaksana tugas, serta batas waktu target penanganan (SLA).
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <button type="button" 
                            onclick="openCreateModal()" 
                            class="bg-[#09090b] hover:bg-[#18181b] text-white px-5 py-2.5 rounded-[14px] text-[13px] font-medium flex items-center gap-2 transition cursor-pointer">
                        <i data-lucide="plus-circle" class="w-4 h-4"></i>
                        <span>Tambah Kategori Baru</span>
                    </button>
                </div>
            </div>

            <!-- Form Pencarian Kategori -->
            <form action="kategori.php" method="GET" class="mt-5 flex items-center gap-3">
                <div class="relative flex-1 max-w-md">
                    <i data-lucide="search" class="w-4 h-4 text-[#71717a] absolute left-3.5 top-3.5 pointer-events-none"></i>
                    <input type="text" 
                           name="search" 
                           value="<?= htmlspecialchars($search); ?>" 
                           placeholder="Cari nama kategori atau dinas tujuan..." 
                           class="w-full h-11 pl-10 pr-4 bg-[#fafafa] border border-[#ececee] focus:border-[#09090b] rounded-[14px] text-[13px] text-[#18181b] outline-none transition">
                </div>
                <button type="submit" class="h-11 px-5 bg-[#09090b] text-white rounded-[14px] text-[13px] font-medium transition">
                    Cari
                </button>
                <?php if (!empty($search)): ?>
                    <a href="kategori.php" class="h-11 px-3 bg-[#f4f4f5] text-[#71717a] hover:text-[#09090b] rounded-[14px] flex items-center justify-center transition">
                        <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                    </a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Tabel Data Kategori (Awesomic Rounded 36px Card) -->
        <div class="bg-white rounded-[36px] border border-[#ececee] p-[28px] overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-[#ececee] text-[12px] text-[#71717a] uppercase font-semibold">
                            <th class="py-3.5 px-3">No</th>
                            <th class="py-3.5 px-4">Nama Kategori & Deskripsi</th>
                            <th class="py-3.5 px-4">Instansi Tujuan (OPD)</th>
                            <th class="py-3.5 px-3 text-center">Target SLA</th>
                            <th class="py-3.5 px-3 text-center">Total Aduan</th>
                            <th class="py-3.5 px-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#f4f4f5] text-[13px]">
                        <?php if (mysqli_num_rows($result) > 0): ?>
                            <?php $no = 1; while ($row = mysqli_fetch_assoc($result)): ?>
                                <tr class="hover:bg-[#fafafa] transition">
                                    <td class="py-4 px-3 text-[#71717a] font-medium"><?= $no++; ?></td>
                                    <td class="py-4 px-4 max-w-sm">
                                        <div class="font-semibold text-[#09090b]">
                                            <?= htmlspecialchars($row['nama_kategori']); ?>
                                        </div>
                                        <div class="text-[12px] text-[#52525b] mt-0.5 line-clamp-1">
                                            <?= htmlspecialchars($row['deskripsi']); ?>
                                        </div>
                                    </td>
                                    <td class="py-4 px-4 font-medium text-[#18181b]">
                                        <?= htmlspecialchars($row['instansi_tujuan']); ?>
                                    </td>
                                    <td class="py-4 px-3 text-center whitespace-nowrap">
                                        <span class="inline-flex items-center gap-1 bg-[#f4f4f5] text-[#18181b] border border-[#ececee] px-2.5 py-1 rounded-[10px] text-[12px] font-semibold">
                                            <i data-lucide="clock" class="w-3.5 h-3.5 text-[#ff5a00]"></i>
                                            <?= (int)$row['target_sla_hari']; ?> Hari
                                        </span>
                                    </td>
                                    <td class="py-4 px-3 text-center whitespace-nowrap">
                                        <span class="font-bold text-[#09090b]"><?= (int)$row['total_aduan']; ?></span> laporan
                                    </td>
                                    <td class="py-4 px-3 text-right whitespace-nowrap">
                                        <div class="inline-flex items-center gap-1.5">
                                            <!-- Edit Button -->
                                            <button type="button" 
                                                    onclick="openEditModal('<?= $row['id_kategori']; ?>', '<?= htmlspecialchars(addslashes($row['nama_kategori'])); ?>', '<?= htmlspecialchars(addslashes($row['instansi_tujuan'])); ?>', '<?= $row['target_sla_hari']; ?>', '<?= htmlspecialchars(addslashes($row['deskripsi'])); ?>')"
                                                    class="h-8 px-2.5 bg-[#f4f4f5] hover:bg-[#09090b] hover:text-white rounded-[10px] text-[12px] font-medium flex items-center gap-1 transition"
                                                    title="Ubah Kategori">
                                                <i data-lucide="edit-3" class="w-3.5 h-3.5"></i>
                                                <span>Ubah</span>
                                            </button>

                                            <!-- Delete Button -->
                                            <button type="button" 
                                                    onclick="openDeleteModal('<?= $row['id_kategori']; ?>', '<?= htmlspecialchars(addslashes($row['nama_kategori'])); ?>', <?= (int)$row['total_aduan']; ?>)"
                                                    class="h-8 px-2.5 bg-[#fee2e2] text-[#991b1b] hover:bg-rose-600 hover:text-white rounded-[10px] text-[12px] font-medium flex items-center gap-1 transition"
                                                    title="Hapus Kategori">
                                                <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="py-8 text-center text-[#71717a]">
                                    Tidak ada data kategori yang ditemukan.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>
</div>

<!-- Modal Tambah Kategori -->
<div id="create-modal" class="fixed inset-0 bg-black/40 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-[32px] border border-[#ececee] max-w-lg w-full p-7">
        <div class="flex items-center justify-between pb-4 mb-4 border-b border-[#ececee]">
            <h3 class="text-[18px] font-semibold text-[#09090b]">Tambah Kategori Baru</h3>
            <button type="button" onclick="closeCreateModal()" class="text-[#71717a] hover:text-[#09090b]">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
        <form action="kategori.php" method="POST" class="space-y-4">
            <input type="hidden" name="action" value="create_kategori">
            <div>
                <label class="block text-[13px] font-medium text-[#18181b] mb-1">Nama Kategori <span class="text-[#ff5a00]">*</span></label>
                <input type="text" name="nama_kategori" required placeholder="Contoh: Infrastruktur Jalan" class="w-full h-11 px-3.5 bg-white border border-[#ececee] rounded-[12px] text-[13px] outline-none">
            </div>
            <div>
                <label class="block text-[13px] font-medium text-[#18181b] mb-1">Instansi Penanggung Jawab (OPD) <span class="text-[#ff5a00]">*</span></label>
                <input type="text" name="instansi_tujuan" required placeholder="Contoh: Dinas PUPR Kab. Sukoharjo" class="w-full h-11 px-3.5 bg-white border border-[#ececee] rounded-[12px] text-[13px] outline-none">
            </div>
            <div>
                <label class="block text-[13px] font-medium text-[#18181b] mb-1">Target SLA Pengerjaan (Hari) <span class="text-[#ff5a00]">*</span></label>
                <input type="number" name="target_sla_hari" min="1" max="30" value="3" required class="w-full h-11 px-3.5 bg-white border border-[#ececee] rounded-[12px] text-[13px] outline-none">
            </div>
            <div>
                <label class="block text-[13px] font-medium text-[#18181b] mb-1">Deskripsi Ruang Lingkup</label>
                <textarea name="deskripsi" rows="3" placeholder="Uraikan batasan dan jenis aduan yang masuk kategori ini..." class="w-full p-3 bg-white border border-[#ececee] rounded-[12px] text-[13px] outline-none"></textarea>
            </div>
            <div class="pt-3 border-t border-[#ececee] flex items-center justify-end gap-3">
                <button type="button" onclick="closeCreateModal()" class="px-5 py-2.5 rounded-[12px] border border-[#ececee] text-[13px] text-[#52525b]">Batal</button>
                <button type="submit" class="px-6 py-2.5 bg-[#09090b] text-white rounded-[12px] text-[13px] font-medium">Simpan Kategori</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit Kategori -->
<div id="edit-modal" class="fixed inset-0 bg-black/40 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-[32px] border border-[#ececee] max-w-lg w-full p-7">
        <div class="flex items-center justify-between pb-4 mb-4 border-b border-[#ececee]">
            <h3 class="text-[18px] font-semibold text-[#09090b]">Ubah Data Kategori</h3>
            <button type="button" onclick="closeEditModal()" class="text-[#71717a] hover:text-[#09090b]">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
        <form action="kategori.php" method="POST" class="space-y-4">
            <input type="hidden" name="action" value="update_kategori">
            <input type="hidden" name="id_kategori" id="edit-id">
            <div>
                <label class="block text-[13px] font-medium text-[#18181b] mb-1">Nama Kategori <span class="text-[#ff5a00]">*</span></label>
                <input type="text" name="nama_kategori" id="edit-nama" required class="w-full h-11 px-3.5 bg-white border border-[#ececee] rounded-[12px] text-[13px] outline-none">
            </div>
            <div>
                <label class="block text-[13px] font-medium text-[#18181b] mb-1">Instansi Penanggung Jawab (OPD) <span class="text-[#ff5a00]">*</span></label>
                <input type="text" name="instansi_tujuan" id="edit-instansi" required class="w-full h-11 px-3.5 bg-white border border-[#ececee] rounded-[12px] text-[13px] outline-none">
            </div>
            <div>
                <label class="block text-[13px] font-medium text-[#18181b] mb-1">Target SLA Pengerjaan (Hari) <span class="text-[#ff5a00]">*</span></label>
                <input type="number" name="target_sla_hari" id="edit-sla" min="1" max="30" required class="w-full h-11 px-3.5 bg-white border border-[#ececee] rounded-[12px] text-[13px] outline-none">
            </div>
            <div>
                <label class="block text-[13px] font-medium text-[#18181b] mb-1">Deskripsi Ruang Lingkup</label>
                <textarea name="deskripsi" id="edit-deskripsi" rows="3" class="w-full p-3 bg-white border border-[#ececee] rounded-[12px] text-[13px] outline-none"></textarea>
            </div>
            <div class="pt-3 border-t border-[#ececee] flex items-center justify-end gap-3">
                <button type="button" onclick="closeEditModal()" class="px-5 py-2.5 rounded-[12px] border border-[#ececee] text-[13px] text-[#52525b]">Batal</button>
                <button type="submit" class="px-6 py-2.5 bg-[#09090b] text-white rounded-[12px] text-[13px] font-medium">Perbarui Kategori</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Konfirmasi Hapus Kategori -->
<div id="delete-modal" class="fixed inset-0 bg-black/40 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-[32px] border border-[#ececee] max-w-md w-full p-7">
        <h3 class="text-[18px] font-semibold text-[#09090b] mb-2">Konfirmasi Hapus Kategori</h3>
        <p class="text-[14px] text-[#52525b] mb-6 leading-relaxed">
            Apakah Anda yakin ingin menghapus kategori <strong id="delete-name" class="text-[#09090b]"></strong>?
        </p>
        <form action="kategori.php" method="POST" class="flex items-center justify-end gap-3">
            <input type="hidden" name="action" value="delete_kategori">
            <input type="hidden" name="id_kategori" id="delete-id">
            <button type="button" onclick="closeDeleteModal()" class="px-5 py-2.5 rounded-[12px] border border-[#ececee] text-[13px] text-[#52525b]">Batal</button>
            <button type="submit" class="px-6 py-2.5 bg-rose-600 hover:bg-rose-700 text-white rounded-[12px] text-[13px] font-medium">Hapus</button>
        </form>
    </div>
</div>

<script>
    function openCreateModal() { document.getElementById('create-modal').classList.remove('hidden'); }
    function closeCreateModal() { document.getElementById('create-modal').classList.add('hidden'); }

    function openEditModal(id, nama, instansi, sla, deskripsi) {
        document.getElementById('edit-id').value = id;
        document.getElementById('edit-nama').value = nama;
        document.getElementById('edit-instansi').value = instansi;
        document.getElementById('edit-sla').value = sla;
        document.getElementById('edit-deskripsi').value = deskripsi;
        document.getElementById('edit-modal').classList.remove('hidden');
    }
    function closeEditModal() { document.getElementById('edit-modal').classList.add('hidden'); }

    function openDeleteModal(id, nama, count) {
        if (count > 0) {
            alert("Kategori '" + nama + "' tidak dapat dihapus karena saat ini masih digunakan oleh " + count + " laporan pengaduan.");
            return;
        }
        document.getElementById('delete-id').value = id;
        document.getElementById('delete-name').textContent = nama;
        document.getElementById('delete-modal').classList.remove('hidden');
    }
    function closeDeleteModal() { document.getElementById('delete-modal').classList.add('hidden'); }
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

