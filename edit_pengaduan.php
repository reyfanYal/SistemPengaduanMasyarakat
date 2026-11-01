<?php
/**
 * ==============================================================================
 * UBAH DATA PENGADUAN (UPDATE COMPLAINT)
 * ePengaduan Kabupaten Sukoharjo - UTS Pemrograman Web 2
 * ==============================================================================
 */

require_once __DIR__ . '/config/koneksi.php';
require_once __DIR__ . '/config/auth.php';

require_login();

$user = current_user();
$userId = current_user_id();
$isAdmin = is_admin();

$id_pengaduan = (int)($_GET['id'] ?? 0);
if ($id_pengaduan <= 0) {
    set_flash('error', 'ID Pengaduan tidak valid.');
    header('Location: pengaduan.php');
    exit;
}

// Ambil data pengaduan yang akan diedit
$stmt = mysqli_prepare($koneksi, "SELECT * FROM pengaduan WHERE id_pengaduan = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $id_pengaduan);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$aduan = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

if (!$aduan) {
    set_flash('error', 'Data pengaduan tidak ditemukan.');
    header('Location: pengaduan.php');
    exit;
}

// Cek hak akses edit: Admin ATAU Warga pemilik saat status masih 'Baru'
if (!$isAdmin && ($aduan['id_user'] != $userId || $aduan['status'] !== 'Baru')) {
    set_flash('error', 'Akses ditolak. Anda hanya dapat mengubah laporan pribadi yang masih berstatus "Baru".');
    header('Location: pengaduan.php');
    exit;
}

$errors = [];
$judul_laporan    = $aduan['judul_laporan'];
$isi_laporan      = $aduan['isi_laporan'];
$id_kategori      = (int)$aduan['id_kategori'];
$kecamatan_lokasi = $aduan['kecamatan_lokasi'];
$alamat_detail    = $aduan['alamat_detail'];
$latitude         = (float)$aduan['latitude'];
$longitude        = (float)$aduan['longitude'];
$is_anonim        = (int)$aduan['is_anonim'];

// Proses Pembaruan Data (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_pengaduan') {
    $judul_laporan    = trim($_POST['judul_laporan'] ?? '');
    $isi_laporan      = trim($_POST['isi_laporan'] ?? '');
    $id_kategori      = (int)($_POST['id_kategori'] ?? 0);
    $kecamatan_lokasi = trim($_POST['kecamatan_lokasi'] ?? 'Sukoharjo');
    $alamat_detail    = trim($_POST['alamat_detail'] ?? '');
    $latitude         = !empty($_POST['latitude']) ? (float)$_POST['latitude'] : $latitude;
    $longitude        = !empty($_POST['longitude']) ? (float)$_POST['longitude'] : $longitude;
    $is_anonim        = isset($_POST['is_anonim']) ? 1 : 0;
    $foto_filename    = $aduan['foto_bukti_awal'];

    if (empty($judul_laporan)) {
        $errors['judul_laporan'] = 'Judul pengaduan wajib diisi.';
    }
    if ($id_kategori <= 0) {
        $errors['id_kategori'] = 'Silakan pilih kategori pengaduan.';
    }
    if (empty($isi_laporan)) {
        $errors['isi_laporan'] = 'Uraian laporan wajib diisi.';
    }
    if (empty($alamat_detail)) {
        $errors['alamat_detail'] = 'Alamat detail wajib diisi.';
    }

    // Cek jika ada unggahan foto pengganti
    if (isset($_FILES['foto_bukti']) && $_FILES['foto_bukti']['error'] === UPLOAD_ERR_OK) {
        $file_tmp  = $_FILES['foto_bukti']['tmp_name'];
        $file_name = $_FILES['foto_bukti']['name'];
        $file_size = $_FILES['foto_bukti']['size'];
        $ext       = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        $allowed_exts = ['jpg', 'jpeg', 'png', 'webp'];
        if (!in_array($ext, $allowed_exts)) {
            $errors['foto_bukti'] = 'Format foto harus berupa JPG, PNG, atau WEBP.';
        } elseif ($file_size > 2 * 1024 * 1024) {
            $errors['foto_bukti'] = 'Ukuran berkas foto maksimal 2 MB.';
        } else {
            // Hapus file lama jika ada
            if (!empty($aduan['foto_bukti_awal'])) {
                $old_file = __DIR__ . '/assets/uploads/awal/' . $aduan['foto_bukti_awal'];
                if (file_exists($old_file)) {
                    @unlink($old_file);
                }
            }

            $foto_filename = 'awal_' . $aduan['kode_tracking'] . '_' . time() . '.' . $ext;
            $upload_target = __DIR__ . '/assets/uploads/awal/' . $foto_filename;
            move_uploaded_file($file_tmp, $upload_target);
        }
    }

    if (empty($errors)) {
        $stmt_up = mysqli_prepare($koneksi, "UPDATE pengaduan SET 
            id_kategori = ?, 
            judul_laporan = ?, 
            isi_laporan = ?, 
            kecamatan_lokasi = ?, 
            alamat_detail = ?, 
            latitude = ?, 
            longitude = ?, 
            foto_bukti_awal = ?, 
            is_anonim = ? 
            WHERE id_pengaduan = ?");

        mysqli_stmt_bind_param($stmt_up, "issssddsii", 
            $id_kategori, 
            $judul_laporan, 
            $isi_laporan, 
            $kecamatan_lokasi, 
            $alamat_detail, 
            $latitude, 
            $longitude, 
            $foto_filename, 
            $is_anonim, 
            $id_pengaduan
        );

        if (mysqli_stmt_execute($stmt_up)) {
            mysqli_stmt_close($stmt_up);
            set_flash('success', 'Data pengaduan ' . $aduan['kode_tracking'] . ' berhasil diperbarui.');
            header("Location: detail_pengaduan.php?id={$id_pengaduan}");
            exit;
        } else {
            $errors['general'] = 'Gagal memperbarui data pengaduan: ' . mysqli_error($koneksi);
            mysqli_stmt_close($stmt_up);
        }
    }
}

$kategori_res = mysqli_query($koneksi, "SELECT id_kategori, nama_kategori, instansi_tujuan, target_sla_hari FROM kategori ORDER BY nama_kategori ASC");
$kecamatan_list = get_kecamatan_sukoharjo();

$page_title = 'Ubah Pengaduan ' . $aduan['kode_tracking'];
$page_heading = 'Perbarui Data Pengaduan';

require_once __DIR__ . '/includes/head.php';
?>

<div class="flex min-h-screen">
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>

    <main class="ml-[260px] flex-1 p-8 max-w-[1200px] w-full">
        <?php require_once __DIR__ . '/includes/topbar.php'; ?>
        <?= render_flash_alerts(); ?>

        <div class="bg-white rounded-[36px] border border-[#ececee] p-8 sm:p-12 mb-8">
            
            <div class="flex items-center justify-between pb-6 mb-8 border-b border-[#ececee]">
                <div>
                    <div class="inline-flex items-center gap-2 bg-[#09090b] text-white text-[11px] font-bold uppercase tracking-wider px-3 py-1 rounded-[12px] mb-2">
                        TIKET: <?= htmlspecialchars($aduan['kode_tracking']); ?>
                    </div>
                    <h2 class="text-[26px] font-semibold text-[#09090b] tracking-tight">
                        Ubah Laporan Pengaduan
                    </h2>
                    <p class="text-[14px] text-[#52525b] mt-0.5">
                        Status saat ini: <?= render_status_badge($aduan['status']); ?>
                    </p>
                </div>
                <a href="detail_pengaduan.php?id=<?= $id_pengaduan; ?>" class="text-[13px] text-[#71717a] hover:text-[#09090b] flex items-center gap-1 font-medium transition">
                    <i data-lucide="arrow-left" class="w-4 h-4"></i>
                    <span>Kembali ke Detail</span>
                </a>
            </div>

            <?php if (!empty($errors['general'])): ?>
                <div class="mb-6 p-4 rounded-[18px] bg-[#fff5f0] border border-[#ffcca8] text-[#18181b] flex items-center gap-3">
                    <span class="w-5 h-5 rounded-full bg-[#ff5a00] text-white flex items-center justify-center text-[12px] font-bold shrink-0">!</span>
                    <span class="text-[13px]"><?= htmlspecialchars($errors['general']); ?></span>
                </div>
            <?php endif; ?>

            <form action="edit_pengaduan.php?id=<?= $id_pengaduan; ?>" method="POST" enctype="multipart/form-data" class="space-y-6">
                <input type="hidden" name="action" value="update_pengaduan">
                <input type="hidden" name="latitude" id="input-latitude" value="<?= htmlspecialchars($latitude); ?>">
                <input type="hidden" name="longitude" id="input-longitude" value="<?= htmlspecialchars($longitude); ?>">

                <!-- Judul -->
                <div>
                    <label class="block text-[13px] font-medium text-[#18181b] mb-1.5">
                        Judul Pengaduan <span class="text-[#ff5a00]">*</span>
                    </label>
                    <input type="text" 
                           name="judul_laporan" 
                           value="<?= htmlspecialchars($judul_laporan); ?>" 
                           required
                           class="w-full h-12 px-4 bg-white border border-[#ececee] focus:border-[#09090b] rounded-[14px] text-[14px] text-[#18181b] outline-none transition">
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Kategori -->
                    <div>
                        <label class="block text-[13px] font-medium text-[#18181b] mb-1.5">
                            Kategori Permasalahan <span class="text-[#ff5a00]">*</span>
                        </label>
                        <select name="id_kategori" 
                                required
                                class="w-full h-12 px-4 bg-white border border-[#ececee] focus:border-[#09090b] rounded-[14px] text-[14px] text-[#18181b] outline-none transition cursor-pointer">
                            <?php while ($cat = mysqli_fetch_assoc($kategori_res)): ?>
                                <option value="<?= $cat['id_kategori']; ?>" <?= ($id_kategori == $cat['id_kategori']) ? 'selected' : ''; ?>>
                                    <?= htmlspecialchars($cat['nama_kategori']); ?> (SLA <?= $cat['target_sla_hari']; ?> Hari)
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <!-- Kecamatan -->
                    <div>
                        <label class="block text-[13px] font-medium text-[#18181b] mb-1.5">
                            Kecamatan Lokasi Kejadian <span class="text-[#ff5a00]">*</span>
                        </label>
                        <select name="kecamatan_lokasi" 
                                class="w-full h-12 px-4 bg-white border border-[#ececee] focus:border-[#09090b] rounded-[14px] text-[14px] text-[#18181b] outline-none transition cursor-pointer">
                            <?php foreach ($kecamatan_list as $kec): ?>
                                <option value="<?= htmlspecialchars($kec); ?>" <?= ($kecamatan_lokasi === $kec) ? 'selected' : ''; ?>>
                                    Kecamatan <?= htmlspecialchars($kec); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Alamat Detail -->
                <div>
                    <label class="block text-[13px] font-medium text-[#18181b] mb-1.5">
                        Alamat Detail & Patokan Tempat <span class="text-[#ff5a00]">*</span>
                    </label>
                    <input type="text" 
                           name="alamat_detail" 
                           value="<?= htmlspecialchars($alamat_detail); ?>" 
                           required
                           class="w-full h-12 px-4 bg-white border border-[#ececee] focus:border-[#09090b] rounded-[14px] text-[14px] text-[#18181b] outline-none transition">
                </div>

                <!-- GIS Map Picker -->
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label class="block text-[13px] font-medium text-[#18181b]">
                            Pembaruan Titik Lokasi Peta (GIS Pin Picker)
                        </label>
                        <span class="text-[12px] text-[#71717a]">
                            Koordinat: <span id="display-coords" class="font-mono text-[#09090b] font-semibold"><?= $latitude; ?>, <?= $longitude; ?></span>
                        </span>
                    </div>
                    <div id="picker-map" class="w-full h-[300px] rounded-[20px] border border-[#ececee] overflow-hidden"></div>
                </div>

                <!-- Foto Bukti Awal Saat Ini & Opsi Penggantian -->
                <div>
                    <label class="block text-[13px] font-medium text-[#18181b] mb-1.5">
                        Foto Bukti Lapangan Saat Ini
                    </label>
                    <div class="flex items-center gap-4 mb-3">
                        <img src="<?= get_bukti_image_url($aduan['foto_bukti_awal'], 'awal'); ?>" 
                             alt="Bukti Awal" 
                             class="w-24 h-24 object-cover rounded-[16px] border border-[#ececee]">
                        <div class="text-[13px] text-[#52525b]">
                            <div>File: <code><?= htmlspecialchars($aduan['foto_bukti_awal']); ?></code></div>
                            <div class="text-[11px] text-[#71717a] mt-1">Unggah berkas baru di bawah jika ingin mengganti foto bukti ini.</div>
                        </div>
                    </div>
                    <input type="file" 
                           name="foto_bukti" 
                           accept=".jpg,.jpeg,.png,.webp"
                           class="w-full text-[13px] text-[#52525b] file:mr-4 file:py-2.5 file:px-4 file:rounded-[12px] file:border-0 file:text-[13px] file:font-medium file:bg-[#09090b] file:text-white hover:file:bg-[#18181b] cursor-pointer">
                </div>

                <!-- Deskripsi Laporan -->
                <div>
                    <label class="block text-[13px] font-medium text-[#18181b] mb-1.5">
                        Isi Laporan / Kronologi Kejadian <span class="text-[#ff5a00]">*</span>
                    </label>
                    <textarea name="isi_laporan" 
                              rows="5" 
                              required
                              class="w-full p-4 bg-white border border-[#ececee] focus:border-[#09090b] rounded-[14px] text-[14px] text-[#18181b] outline-none transition"><?= htmlspecialchars($isi_laporan); ?></textarea>
                </div>

                <!-- Opsi Anonim -->
                <div class="p-4 bg-[#fafafa] rounded-[18px] border border-[#ececee] flex items-start gap-3">
                    <input type="checkbox" 
                           name="is_anonim" 
                           id="is_anonim" 
                           value="1" 
                           <?= ($is_anonim == 1) ? 'checked' : ''; ?>
                           class="mt-1 w-4 h-4 rounded border-[#ececee] text-[#09090b] focus:ring-0">
                    <div>
                        <label for="is_anonim" class="text-[13px] font-semibold text-[#09090b] cursor-pointer">
                            Rahasiakan Identitas Saya (Tampil sebagai Anonim)
                        </label>
                        <p class="text-[12px] text-[#52525b] mt-0.5">
                            Identitas disamarkan dari hadapan publik umum.
                        </p>
                    </div>
                </div>

                <!-- Tombol Aksi -->
                <div class="pt-4 border-t border-[#ececee] flex items-center justify-end gap-3">
                    <a href="detail_pengaduan.php?id=<?= $id_pengaduan; ?>" class="px-6 py-3 rounded-[14px] border border-[#ececee] text-[#52525b] hover:text-[#09090b] hover:bg-[#f4f4f5] text-[13px] font-medium transition">
                        Batal
                    </a>
                    <button type="submit" 
                            class="px-8 py-3 bg-[#09090b] hover:bg-[#18181b] text-white rounded-[14px] font-medium text-[13px] flex items-center gap-2 transition cursor-pointer">
                        <span>Simpan Perubahan</span>
                        <i data-lucide="check" class="w-4 h-4"></i>
                    </button>
                </div>

            </form>
        </div>

    </main>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const defaultLat = <?= $latitude; ?>;
        const defaultLng = <?= $longitude; ?>;
        
        const pickerMap = L.map('picker-map').setView([defaultLat, defaultLng], 13);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 18,
            attribution: '&copy; OpenStreetMap'
        }).addTo(pickerMap);

        let pinMarker = L.marker([defaultLat, defaultLng], {
            draggable: true
        }).addTo(pickerMap);

        function updateCoordinates(lat, lng) {
            document.getElementById('input-latitude').value = lat.toFixed(7);
            document.getElementById('input-longitude').value = lng.toFixed(7);
            document.getElementById('display-coords').textContent = lat.toFixed(6) + ', ' + lng.toFixed(6);
        }

        pinMarker.on('dragend', function () {
            const position = pinMarker.getLatLng();
            updateCoordinates(position.lat, position.lng);
        });

        pickerMap.on('click', function (e) {
            pinMarker.setLatLng(e.latlng);
            updateCoordinates(e.latlng.lat, e.latlng.lng);
        });
    });
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

