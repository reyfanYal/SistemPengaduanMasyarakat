<?php
/**
 * ==============================================================================
 * MODUL TINDAK LANJUT & VERIFIKASI DISPOSISI PETUGAS PEMKAB (ADMIN)
 * ePengaduan Kabupaten Sukoharjo - UTS Pemrograman Web 2
 * ==============================================================================
 */

require_once __DIR__ . '/config/koneksi.php';
require_once __DIR__ . '/config/auth.php';

// Khusus Administrator Diskominfo / OPD Pemkab
require_admin();

$user = current_user();
$userId = current_user_id();

$selected_id = (int)($_GET['id'] ?? 0);
$errors = [];

// Proses Simpan Tindak Lanjut Baru (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_tindak_lanjut') {
    $id_pengaduan = (int)($_POST['id_pengaduan'] ?? 0);
    $status_baru  = trim($_POST['status_tindak_lanjut'] ?? '');
    $catatan      = trim($_POST['catatan_petugas'] ?? '');
    $foto_selesai = null;

    if ($id_pengaduan <= 0) {
        $errors['id_pengaduan'] = 'Silakan pilih pengaduan yang hendak ditindaklanjuti.';
    }

    $valid_statuses = ['Verifikasi', 'Diproses', 'Selesai', 'Ditolak'];
    if (!in_array($status_baru, $valid_statuses)) {
        $errors['status'] = 'Pilihan status tindak lanjut tidak valid.';
    }

    if (empty($catatan)) {
        $errors['catatan'] = 'Catatan resmi penanganan petugas wajib diisi.';
    } elseif (strlen($catatan) < 10) {
        $errors['catatan'] = 'Uraian catatan minimal 10 karakter.';
    }

    // Penanganan Unggah Foto Bukti Selesai (After)
    if (isset($_FILES['foto_bukti_selesai']) && $_FILES['foto_bukti_selesai']['error'] === UPLOAD_ERR_OK) {
        $file_tmp  = $_FILES['foto_bukti_selesai']['tmp_name'];
        $file_name = $_FILES['foto_bukti_selesai']['name'];
        $file_size = $_FILES['foto_bukti_selesai']['size'];
        $ext       = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        $allowed_exts = ['jpg', 'jpeg', 'png', 'webp'];
        if (!in_array($ext, $allowed_exts)) {
            $errors['foto_bukti_selesai'] = 'Format foto hasil perbaikan harus JPG, PNG, atau WEBP.';
        } elseif ($file_size > 2 * 1024 * 1024) {
            $errors['foto_bukti_selesai'] = 'Ukuran berkas foto maksimal 2 MB.';
        } else {
            $foto_selesai = 'selesai_' . $id_pengaduan . '_' . time() . '.' . $ext;
            $upload_target = __DIR__ . '/assets/uploads/selesai/' . $foto_selesai;
            if (!move_uploaded_file($file_tmp, $upload_target)) {
                $errors['foto_bukti_selesai'] = 'Gagal menyimpan berkas foto perbaikan ke server.';
            }
        }
    }

    // Jika status 'Selesai' dan belum ada upload, gunakan sample placeholder jika kosong
    if ($status_baru === 'Selesai' && empty($foto_selesai)) {
        $foto_selesai = 'sample_jalan_selesai.jpg';
    }

    if (empty($errors)) {
        // 1. Simpan ke tabel tindak_lanjut (Audit Log)
        $stmt_tl = mysqli_prepare($koneksi, "INSERT INTO tindak_lanjut (id_pengaduan, id_petugas, status_tindak_lanjut, catatan_petugas, foto_bukti_selesai, tanggal_tindak_lanjut) VALUES (?, ?, ?, ?, ?, NOW())");
        mysqli_stmt_bind_param($stmt_tl, "iisss", $id_pengaduan, $userId, $status_baru, $catatan, $foto_selesai);
        mysqli_stmt_execute($stmt_tl);
        mysqli_stmt_close($stmt_tl);

        // 2. Perbarui status tabel master pengaduan
        $stmt_up = mysqli_prepare($koneksi, "UPDATE pengaduan SET status = ? WHERE id_pengaduan = ?");
        mysqli_stmt_bind_param($stmt_up, "si", $status_baru, $id_pengaduan);
        mysqli_stmt_execute($stmt_up);
        mysqli_stmt_close($stmt_up);

        set_flash('success', "Status pengaduan berhasil diperbarui menjadi '{$status_baru}' dan tercatat dalam linimasa.");
        header("Location: tindak_lanjut.php?id={$id_pengaduan}");
        exit;
    }
}

// Ambil aduan yang sedang dipilih (jika ada)
$selected_aduan = null;
if ($selected_id > 0) {
    $stmt_sel = mysqli_prepare($koneksi, "SELECT p.*, k.nama_kategori, k.instansi_tujuan, u.nama_lengkap 
                                          FROM pengaduan p 
                                          JOIN kategori k ON p.id_kategori = k.id_kategori 
                                          JOIN users u ON p.id_user = u.id_user 
                                          WHERE p.id_pengaduan = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt_sel, "i", $selected_id);
    mysqli_stmt_execute($stmt_sel);
    $selected_aduan = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_sel));
    mysqli_stmt_close($stmt_sel);
}

// Ambil Daftar Pengaduan yang Membutuhkan Respon (Baru, Verifikasi, Diproses)
$query_pending = "SELECT p.id_pengaduan, p.kode_tracking, p.judul_laporan, p.status, p.kecamatan_lokasi, p.tanggal_lapor, k.nama_kategori 
                  FROM pengaduan p
                  JOIN kategori k ON p.id_kategori = k.id_kategori
                  ORDER BY FIELD(p.status, 'Baru', 'Verifikasi', 'Diproses', 'Selesai', 'Ditolak'), p.tanggal_lapor DESC";
$res_pending = mysqli_query($koneksi, $query_pending);

// Ambil 8 Riwayat Tindak Lanjut Terkini untuk Monitoring
$query_logs = "SELECT tl.*, p.kode_tracking, p.judul_laporan, u.nama_lengkap as nama_petugas 
               FROM tindak_lanjut tl
               JOIN pengaduan p ON tl.id_pengaduan = p.id_pengaduan
               JOIN users u ON tl.id_petugas = u.id_user
               ORDER BY tl.tanggal_tindak_lanjut DESC 
               LIMIT 8";
$res_logs = mysqli_query($koneksi, $query_logs);

$page_title = 'Tindak Lanjut & Disposisi';
$page_heading = 'Verifikasi & Tindak Lanjut Pengaduan';

require_once __DIR__ . '/includes/head.php';
?>

<div class="flex min-h-screen">
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>

    <main class="ml-[260px] flex-1 p-8 max-w-[1200px] w-full">
        <?php require_once __DIR__ . '/includes/topbar.php'; ?>
        <?= render_flash_alerts(); ?>

        <!-- 2-Column Section: Form Pembaruan Status & Daftar Antrean Aduan -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 mb-8">
            
            <!-- Kolom Kiri (7 Cols): Formulir Tindak Lanjut -->
            <div class="lg:col-span-7 bg-white rounded-[36px] border border-[#ececee] p-8 sm:p-10">
                <div class="flex items-center justify-between pb-6 mb-6 border-b border-[#ececee]">
                    <div>
                        <div class="inline-flex items-center gap-2 bg-[#ff5a00] text-white text-[11px] font-bold uppercase tracking-wider px-3 py-1 rounded-[12px] mb-2">
                            PANEL DISPOSISI
                        </div>
                        <h2 class="text-[22px] font-semibold text-[#09090b] tracking-tight">
                            Formulir Pembaruan Status Aduan
                        </h2>
                    </div>
                    <?php if ($selected_aduan): ?>
                        <a href="detail_pengaduan.php?id=<?= $selected_aduan['id_pengaduan']; ?>" class="text-[13px] text-[#09090b] hover:underline flex items-center gap-1 font-medium">
                            <span>Lihat Tiket</span>
                            <i data-lucide="external-link" class="w-3.5 h-3.5"></i>
                        </a>
                    <?php endif; ?>
                </div>

                <?php if (!empty($errors['general'])): ?>
                    <div class="mb-6 p-4 rounded-[18px] bg-[#fff5f0] border border-[#ffcca8] text-[#18181b] flex items-center gap-3">
                        <span class="w-5 h-5 rounded-full bg-[#ff5a00] text-white flex items-center justify-center text-[12px] font-bold shrink-0">!</span>
                        <span class="text-[13px]"><?= htmlspecialchars($errors['general']); ?></span>
                    </div>
                <?php endif; ?>

                <form action="tindak_lanjut.php" method="POST" enctype="multipart/form-data" class="space-y-6">
                    <input type="hidden" name="action" value="submit_tindak_lanjut">

                    <!-- 1. Pilihan Pengaduan -->
                    <div>
                        <label class="block text-[13px] font-medium text-[#18181b] mb-1.5">
                            Pilih Pengaduan yang Ditindaklanjuti <span class="text-[#ff5a00]">*</span>
                        </label>
                        <select name="id_pengaduan" 
                                id="select_pengaduan"
                                onchange="if(this.value) window.location.href='tindak_lanjut.php?id=' + this.value"
                                required
                                class="w-full h-12 px-4 bg-white border <?= isset($errors['id_pengaduan']) ? 'border-[#ff5a00]' : 'border-[#ececee]'; ?> focus:border-[#09090b] rounded-[14px] text-[14px] text-[#18181b] outline-none transition cursor-pointer">
                            <option value="">-- Pilih Tiket Pengaduan --</option>
                            <?php 
                            mysqli_data_seek($res_pending, 0);
                            while ($p = mysqli_fetch_assoc($res_pending)): 
                            ?>
                                <option value="<?= $p['id_pengaduan']; ?>" <?= ($selected_id == $p['id_pengaduan']) ? 'selected' : ''; ?>>
                                    [<?= $p['kode_tracking']; ?>] <?= htmlspecialchars($p['judul_laporan']); ?> (<?= $p['status']; ?>)
                                </option>
                            <?php endwhile; ?>
                        </select>
                        <?php if (isset($errors['id_pengaduan'])): ?>
                            <p class="text-[#ff5a00] text-[12px] mt-1 font-medium"><?= $errors['id_pengaduan']; ?></p>
                        <?php endif; ?>
                    </div>

                    <?php if ($selected_aduan): ?>
                        <!-- Quick Context Box tentang Laporan Terpilih -->
                        <div class="p-4 bg-[#fafafa] rounded-[20px] border border-[#ececee] text-[13px] space-y-1.5">
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-[#09090b]"><?= htmlspecialchars($selected_aduan['kode_tracking']); ?></span>
                                <span>Status Sekarang: <?= render_status_badge($selected_aduan['status']); ?></span>
                            </div>
                            <div class="font-medium text-[#18181b]"><?= htmlspecialchars($selected_aduan['judul_laporan']); ?></div>
                            <div class="text-[#52525b]">
                                Dinas Tujuan: <strong><?= htmlspecialchars($selected_aduan['instansi_tujuan']); ?></strong> &bull; Kec. <?= htmlspecialchars($selected_aduan['kecamatan_lokasi']); ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- 2. Status Baru -->
                    <div>
                        <label class="block text-[13px] font-medium text-[#18181b] mb-1.5">
                            Perbarui Status Menjadi <span class="text-[#ff5a00]">*</span>
                        </label>
                        <select name="status_tindak_lanjut" 
                                required
                                class="w-full h-12 px-4 bg-white border border-[#ececee] focus:border-[#09090b] rounded-[14px] text-[14px] text-[#18181b] outline-none transition cursor-pointer">
                            <option value="Verifikasi" <?= ($selected_aduan && $selected_aduan['status'] === 'Baru') ? 'selected' : ''; ?>>
                                Verifikasi (Laporan Valid & Diteruskan ke OPD)
                            </option>
                            <option value="Diproses" <?= ($selected_aduan && $selected_aduan['status'] === 'Verifikasi') ? 'selected' : ''; ?>>
                                Diproses (Teknisi Lapangan Melakukan Perbaikan)
                            </option>
                            <option value="Selesai" <?= ($selected_aduan && $selected_aduan['status'] === 'Diproses') ? 'selected' : ''; ?>>
                                Selesai (Pekerjaan Tuntas & Sesuai Standar)
                            </option>
                            <option value="Ditolak">
                                Ditolak (Laporan Hoaks / Di Luar Kewenangan Pemkab)
                            </option>
                        </select>
                    </div>

                    <!-- 3. Catatan Resmi Petugas -->
                    <div>
                        <label class="block text-[13px] font-medium text-[#18181b] mb-1.5">
                            Catatan Resmi & Keterangan Tindak Lanjut <span class="text-[#ff5a00]">*</span>
                        </label>
                        <textarea name="catatan_petugas" 
                                  rows="4" 
                                  placeholder="Tuliskan tindakan konkret yang telah dilakukan, nomor surat dinas, atau hasil verifikasi lapangan..." 
                                  required
                                  class="w-full p-4 bg-white border <?= isset($errors['catatan']) ? 'border-[#ff5a00]' : 'border-[#ececee]'; ?> focus:border-[#09090b] rounded-[14px] text-[14px] text-[#18181b] outline-none transition"></textarea>
                        <?php if (isset($errors['catatan'])): ?>
                            <p class="text-[#ff5a00] text-[12px] mt-1 font-medium"><?= $errors['catatan']; ?></p>
                        <?php endif; ?>
                    </div>

                    <!-- 4. Upload Foto Bukti Selesai (After) -->
                    <div>
                        <label class="block text-[13px] font-medium text-[#18181b] mb-1.5">
                            Unggah Foto Bukti Perbaikan (After / Bukti Tuntas)
                        </label>
                        <input type="file" 
                               name="foto_bukti_selesai" 
                               accept=".jpg,.jpeg,.png,.webp"
                               class="w-full text-[13px] text-[#52525b] file:mr-4 file:py-2.5 file:px-4 file:rounded-[12px] file:border-0 file:text-[13px] file:font-medium file:bg-[#09090b] file:text-white hover:file:bg-[#18181b] cursor-pointer">
                        <p class="text-[11px] text-[#71717a] mt-1">Wajib diunggah saat status diubah menjadi "Selesai" sebagai bukti otentik bagi pelapor.</p>
                        <?php if (isset($errors['foto_bukti_selesai'])): ?>
                            <p class="text-[#ff5a00] text-[12px] mt-1 font-medium"><?= $errors['foto_bukti_selesai']; ?></p>
                        <?php endif; ?>
                    </div>

                    <!-- Tombol Aksi -->
                    <div class="pt-4 border-t border-[#ececee] flex items-center justify-end gap-3">
                        <button type="submit" 
                                class="px-8 py-3 bg-[#09090b] hover:bg-[#18181b] text-white rounded-[14px] font-medium text-[13px] flex items-center gap-2 transition cursor-pointer">
                            <span>Simpan Tindak Lanjut</span>
                            <i data-lucide="check" class="w-4 h-4"></i>
                        </button>
                    </div>

                </form>
            </div>

            <!-- Kolom Kanan (5 Cols): Antrean Laporan Menunggu Verifikasi & SLA -->
            <div class="lg:col-span-5 bg-white rounded-[36px] border border-[#ececee] p-8 flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between mb-4 pb-4 border-b border-[#f4f4f5]">
                        <h3 class="text-[18px] font-semibold text-[#09090b]">
                            Antrean Prioritas Aduan
                        </h3>
                        <span class="bg-[#ff5a00] text-white text-[10px] font-bold px-2 py-0.5 rounded-[8px]">
                            PERLU RESPON
                        </span>
                    </div>

                    <div class="space-y-3 max-h-[500px] overflow-y-auto pr-1">
                        <?php 
                        mysqli_data_seek($res_pending, 0);
                        $count_list = 0;
                        while ($item = mysqli_fetch_assoc($res_pending)): 
                            if ($count_list++ >= 7) break;
                        ?>
                            <a href="tindak_lanjut.php?id=<?= $item['id_pengaduan']; ?>" 
                               class="block p-3.5 rounded-[18px] border <?= ($selected_id == $item['id_pengaduan']) ? 'border-[#09090b] bg-[#f4f4f5]' : 'border-[#ececee] bg-white hover:bg-[#fafafa]'; ?> transition">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="font-bold text-[12px] text-[#09090b]"><?= $item['kode_tracking']; ?></span>
                                    <span><?= render_status_badge($item['status']); ?></span>
                                </div>
                                <div class="font-medium text-[13px] text-[#18181b] line-clamp-1">
                                    <?= htmlspecialchars($item['judul_laporan']); ?>
                                </div>
                                <div class="text-[11px] text-[#71717a] mt-1 flex items-center justify-between">
                                    <span>Kec. <?= htmlspecialchars($item['kecamatan_lokasi']); ?></span>
                                    <span><?= format_tanggal_indo($item['tanggal_lapor']); ?></span>
                                </div>
                            </a>
                        <?php endwhile; ?>
                    </div>
                </div>

                <div class="pt-4 border-t border-[#f4f4f5] text-[12px] text-[#71717a]">
                    Pilih aduan di atas untuk mengisi formulir tindakan dinas.
                </div>
            </div>

        </div>

        <!-- Tabel Log Audit Trail Tindak Lanjut Terkini -->
        <div class="bg-white rounded-[36px] border border-[#ececee] p-[28px]">
            <div class="flex items-center justify-between mb-5">
                <div>
                    <h3 class="text-[20px] font-semibold text-[#09090b] tracking-tight">
                        Riwayat Audit Trail Tindak Lanjut Terkini
                    </h3>
                    <p class="text-[13px] text-[#52525b] mt-0.5">
                        Log seluruh aksi disposisi dan pembaruan status yang dilakukan oleh petugas dinas.
                    </p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-[#ececee] text-[12px] text-[#71717a] uppercase font-semibold">
                            <th class="py-3 px-3">Waktu Eksekusi</th>
                            <th class="py-3 px-3">Kode Tiket</th>
                            <th class="py-3 px-3">Status</th>
                            <th class="py-3 px-3">Petugas Penanggung Jawab</th>
                            <th class="py-3 px-4">Catatan Tindakan Lapangan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#f4f4f5] text-[13px]">
                        <?php while ($log = mysqli_fetch_assoc($res_logs)): ?>
                            <tr class="hover:bg-[#fafafa] transition">
                                <td class="py-3.5 px-3 text-[#71717a] whitespace-nowrap">
                                    <?= format_tanggal_indo($log['tanggal_tindak_lanjut']); ?>
                                </td>
                                <td class="py-3.5 px-3 font-bold text-[#09090b] whitespace-nowrap">
                                    <a href="detail_pengaduan.php?id=<?= $log['id_pengaduan']; ?>" class="hover:underline">
                                        <?= htmlspecialchars($log['kode_tracking']); ?>
                                    </a>
                                </td>
                                <td class="py-3.5 px-3 whitespace-nowrap">
                                    <?= render_status_badge($log['status_tindak_lanjut']); ?>
                                </td>
                                <td class="py-3.5 px-3 font-medium text-[#18181b] whitespace-nowrap">
                                    <?= htmlspecialchars($log['nama_petugas']); ?>
                                </td>
                                <td class="py-3.5 px-4 text-[#3f3f46]">
                                    <?= htmlspecialchars($log['catatan_petugas']); ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

