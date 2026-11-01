<?php
/**
 * ==============================================================================
 * FORMULIR BUAT PENGADUAN BARU (PIN PETA, FOTO UPLOAD & DRAFT AUTOSAVE)
 * ePengaduan Kabupaten Sukoharjo - UTS Pemrograman Web 2
 * ==============================================================================
 */

require_once __DIR__ . '/config/koneksi.php';
require_once __DIR__ . '/config/auth.php';

require_login();

$user = current_user();
$userId = current_user_id();

// Generator Kode Tracking Otomatis: SKH-2026-XXXX
$tahun = date('Y');
$res_count = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM pengaduan WHERE kode_tracking LIKE 'SKH-{$tahun}-%'");
$row_count = mysqli_fetch_assoc($res_count);
$next_seq = (int)($row_count['total'] ?? 0) + 1;
$generated_code = 'SKH-' . $tahun . '-' . str_pad($next_seq, 4, '0', STR_PAD_LEFT);

// Pastikan kode benar-benar unik
$stmt_check_code = mysqli_prepare($koneksi, "SELECT id_pengaduan FROM pengaduan WHERE kode_tracking = ? LIMIT 1");
mysqli_stmt_bind_param($stmt_check_code, "s", $generated_code);
mysqli_stmt_execute($stmt_check_code);
if (mysqli_num_rows(mysqli_stmt_get_result($stmt_check_code)) > 0) {
    $generated_code = 'SKH-' . $tahun . '-' . str_pad($next_seq + 1, 4, '0', STR_PAD_LEFT);
}
mysqli_stmt_close($stmt_check_code);

$errors = [];
$judul_laporan    = '';
$isi_laporan      = '';
$id_kategori      = 0;
$kecamatan_lokasi = $user['kecamatan'] ?? 'Sukoharjo';
$alamat_detail    = '';
$latitude         = -7.6828000;
$longitude        = 110.8407000;
$is_anonim        = 0;

// Proses Simpan Pengaduan Baru (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_pengaduan') {
    $judul_laporan    = trim($_POST['judul_laporan'] ?? '');
    $isi_laporan      = trim($_POST['isi_laporan'] ?? '');
    $id_kategori      = (int)($_POST['id_kategori'] ?? 0);
    $kecamatan_lokasi = trim($_POST['kecamatan_lokasi'] ?? 'Sukoharjo');
    $alamat_detail    = trim($_POST['alamat_detail'] ?? '');
    $latitude         = !empty($_POST['latitude']) ? (float)$_POST['latitude'] : -7.6828000;
    $longitude        = !empty($_POST['longitude']) ? (float)$_POST['longitude'] : 110.8407000;
    $is_anonim        = isset($_POST['is_anonim']) ? 1 : 0;
    $foto_filename    = '';

    // 1. Validasi Judul
    if (empty($judul_laporan)) {
        $errors['judul_laporan'] = 'Judul pengaduan wajib diisi.';
    } elseif (strlen($judul_laporan) < 8) {
        $errors['judul_laporan'] = 'Judul pengaduan minimal 8 karakter.';
    }

    // 2. Validasi Kategori
    if ($id_kategori <= 0) {
        $errors['id_kategori'] = 'Silakan pilih kategori pengaduan yang sesuai.';
    }

    // 3. Validasi Isi Laporan
    if (empty($isi_laporan)) {
        $errors['isi_laporan'] = 'Uraian kronologi keluhan wajib diisi lengkap.';
    } elseif (strlen($isi_laporan) < 20) {
        $errors['isi_laporan'] = 'Deskripsi laporan minimal 20 karakter agar petugas dapat menindaklanjuti secara akurat.';
    }

    // 4. Validasi Alamat
    if (empty($alamat_detail)) {
        $errors['alamat_detail'] = 'Alamat detail atau patokan lokasi kejadian wajib diisi.';
    }

    // 5. Validasi dan Upload Foto Bukti Awal
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
            // Beri nama berkas unik
            $foto_filename = 'awal_' . $generated_code . '_' . time() . '.' . $ext;
            $upload_target = __DIR__ . '/assets/uploads/awal/' . $foto_filename;

            if (!move_uploaded_file($file_tmp, $upload_target)) {
                $errors['foto_bukti'] = 'Gagal menyimpan berkas foto ke server.';
            }
        }
    } else {
        // Jika tidak upload foto, berikan fallback file nama terstandar
        $foto_filename = 'sample_default_' . time() . '.jpg';
    }

    // Jika tidak ada error, masukkan ke basis data
    if (empty($errors)) {
        $status_awal = 'Baru';
        $jumlah_dukungan = 1;

        $stmt = mysqli_prepare($koneksi, "INSERT INTO pengaduan 
            (kode_tracking, id_user, id_kategori, judul_laporan, isi_laporan, kecamatan_lokasi, alamat_detail, latitude, longitude, foto_bukti_awal, is_anonim, status, jumlah_dukungan, tanggal_lapor) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        
        mysqli_stmt_bind_param($stmt, "siissssddsisi", 
            $generated_code, 
            $userId, 
            $id_kategori, 
            $judul_laporan, 
            $isi_laporan, 
            $kecamatan_lokasi, 
            $alamat_detail, 
            $latitude, 
            $longitude, 
            $foto_filename, 
            $is_anonim, 
            $status_awal, 
            $jumlah_dukungan
        );

        if (mysqli_stmt_execute($stmt)) {
            $new_id = mysqli_insert_id($koneksi);
            mysqli_stmt_close($stmt);

            set_flash('success', "Aspirasi Anda berhasil dikirim dengan Kode Tracking: {$generated_code}. Tim verifikasi Pemkab Sukoharjo akan segera memeriksa laporan Anda.");
            header("Location: detail_pengaduan.php?kode={$generated_code}");
            exit;
        } else {
            $errors['general'] = 'Gagal menyimpan pengaduan: ' . mysqli_error($koneksi);
            mysqli_stmt_close($stmt);
        }
    }
}

// Ambil Kategori untuk opsi dropdown
$kategori_res = mysqli_query($koneksi, "SELECT id_kategori, nama_kategori, instansi_tujuan, target_sla_hari FROM kategori ORDER BY nama_kategori ASC");
$kecamatan_list = get_kecamatan_sukoharjo();

$page_title = 'Buat Pengaduan Baru';
$page_heading = 'Formulir Pengaduan Warga';

require_once __DIR__ . '/includes/head.php';
?>

<div class="flex min-h-screen">
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>

    <main class="ml-[260px] flex-1 p-8 max-w-[1200px] w-full">
        <?php require_once __DIR__ . '/includes/topbar.php'; ?>
        <?= render_flash_alerts(); ?>

        <!-- Container Utama Formulir -->
        <div class="bg-white rounded-[36px] border border-[#ececee] p-8 sm:p-12 mb-8">
            
            <!-- Header Kartu -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-6 mb-8 border-b border-[#ececee] gap-4">
                <div>
                    <div class="inline-flex items-center gap-2 bg-[#ff5a00] text-white text-[11px] font-bold uppercase tracking-wider px-3 py-1 rounded-[12px] mb-2">
                        NOMOR TIKET: <?= $generated_code; ?>
                    </div>
                    <h2 class="text-[26px] font-semibold text-[#09090b] tracking-tight">
                        Laporkan Permasalahan Pelayanan Publik
                    </h2>
                    <p class="text-[14px] text-[#52525b] mt-0.5">
                        Laporan Anda akan langsung didisposisikan kepada dinas (OPD) penanggung jawab di Kabupaten Sukoharjo.
                    </p>
                </div>

                <!-- Notifikasi Autosave Draft LocalStorage -->
                <div class="flex items-center gap-2 text-[12px] text-[#71717a] bg-[#fafafa] border border-[#ececee] px-3.5 py-2 rounded-[14px]">
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                    <span id="draft-status">Autosave draft aktif</span>
                </div>
            </div>

            <?php if (!empty($errors['general'])): ?>
                <div class="mb-6 p-4 rounded-[18px] bg-[#fff5f0] border border-[#ffcca8] text-[#18181b] flex items-center gap-3">
                    <span class="w-5 h-5 rounded-full bg-[#ff5a00] text-white flex items-center justify-center text-[12px] font-bold shrink-0">!</span>
                    <span class="text-[13px]"><?= htmlspecialchars($errors['general']); ?></span>
                </div>
            <?php endif; ?>

            <!-- Form Buat Pengaduan -->
            <form action="tambah_pengaduan.php" method="POST" enctype="multipart/form-data" id="form-pengaduan" class="space-y-6">
                <input type="hidden" name="action" value="create_pengaduan">
                <input type="hidden" name="latitude" id="input-latitude" value="<?= htmlspecialchars($latitude); ?>">
                <input type="hidden" name="longitude" id="input-longitude" value="<?= htmlspecialchars($longitude); ?>">

                <!-- 1. Judul Laporan -->
                <div>
                    <label class="block text-[13px] font-medium text-[#18181b] mb-1.5">
                        Judul Pengaduan <span class="text-[#ff5a00]">*</span>
                    </label>
                    <input type="text" 
                           name="judul_laporan" 
                           id="judul_laporan"
                           value="<?= htmlspecialchars($judul_laporan); ?>" 
                           placeholder="Contoh: Jalan Berlubang Parah di Depan Puskesmas Kartasura" 
                           required
                           class="w-full h-12 px-4 bg-white border <?= isset($errors['judul_laporan']) ? 'border-[#ff5a00]' : 'border-[#ececee]'; ?> focus:border-[#09090b] rounded-[14px] text-[14px] text-[#18181b] outline-none transition">
                    <?php if (isset($errors['judul_laporan'])): ?>
                        <p class="text-[#ff5a00] text-[12px] mt-1 font-medium"><?= $errors['judul_laporan']; ?></p>
                    <?php endif; ?>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- 2. Kategori Pengaduan -->
                    <div>
                        <label class="block text-[13px] font-medium text-[#18181b] mb-1.5">
                            Kategori Permasalahan <span class="text-[#ff5a00]">*</span>
                        </label>
                        <select name="id_kategori" 
                                id="id_kategori"
                                required
                                class="w-full h-12 px-4 bg-white border <?= isset($errors['id_kategori']) ? 'border-[#ff5a00]' : 'border-[#ececee]'; ?> focus:border-[#09090b] rounded-[14px] text-[14px] text-[#18181b] outline-none transition cursor-pointer">
                            <option value="">-- Pilih Kategori OPD --</option>
                            <?php while ($cat = mysqli_fetch_assoc($kategori_res)): ?>
                                <option value="<?= $cat['id_kategori']; ?>" <?= ($id_kategori == $cat['id_kategori']) ? 'selected' : ''; ?>>
                                    <?= htmlspecialchars($cat['nama_kategori']); ?> (SLA <?= $cat['target_sla_hari']; ?> Hari)
                                </option>
                            <?php endwhile; ?>
                        </select>
                        <?php if (isset($errors['id_kategori'])): ?>
                            <p class="text-[#ff5a00] text-[12px] mt-1 font-medium"><?= $errors['id_kategori']; ?></p>
                        <?php endif; ?>
                    </div>

                    <!-- 3. Kecamatan Titik Kejadian -->
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

                <!-- 4. Alamat Detail & Patokan Lokasi -->
                <div>
                    <label class="block text-[13px] font-medium text-[#18181b] mb-1.5">
                        Alamat Detail & Patokan Tempat <span class="text-[#ff5a00]">*</span>
                    </label>
                    <input type="text" 
                           name="alamat_detail" 
                           id="alamat_detail"
                           value="<?= htmlspecialchars($alamat_detail); ?>" 
                           placeholder="Contoh: Jl. Ahmad Yani No. 12, seberang SPBU Kartasura" 
                           required
                           class="w-full h-12 px-4 bg-white border <?= isset($errors['alamat_detail']) ? 'border-[#ff5a00]' : 'border-[#ececee]'; ?> focus:border-[#09090b] rounded-[14px] text-[14px] text-[#18181b] outline-none transition">
                    <?php if (isset($errors['alamat_detail'])): ?>
                        <p class="text-[#ff5a00] text-[12px] mt-1 font-medium"><?= $errors['alamat_detail']; ?></p>
                    <?php endif; ?>
                </div>

                <!-- 5. Interactive Leaflet Pin Map Picker -->
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label class="block text-[13px] font-medium text-[#18181b]">
                            Tandai Titik Lokasi Peta (GIS Pin Picker) <span class="text-[#ff5a00]">*</span>
                        </label>
                        <span class="text-[12px] text-[#71717a]">
                            Koordinat terpilih: <span id="display-coords" class="font-mono text-[#09090b] font-semibold"><?= $latitude; ?>, <?= $longitude; ?></span>
                        </span>
                    </div>
                    <div id="picker-map" class="w-full h-[320px] rounded-[20px] border border-[#ececee] overflow-hidden"></div>
                    <p class="text-[12px] text-[#71717a] mt-1.5 flex items-center gap-1">
                        <i data-lucide="map-pin" class="w-3.5 h-3.5 text-[#ff5a00]"></i>
                        <span>Klik pada peta atau geser penanda merah untuk menentukan koordinat lokasi aduan yang akurat.</span>
                    </p>
                </div>

                <!-- 6. Upload Foto Bukti Awal -->
                <div>
                    <label class="block text-[13px] font-medium text-[#18181b] mb-1.5">
                        Unggah Foto Bukti Lapangan (Kondisi Awal)
                    </label>
                    <div class="border-2 border-dashed border-[#ececee] hover:border-[#09090b] rounded-[20px] p-6 text-center transition cursor-pointer bg-[#fafafa]">
                        <input type="file" 
                               name="foto_bukti" 
                               id="foto_bukti" 
                               accept=".jpg,.jpeg,.png,.webp"
                               class="hidden" 
                               onchange="previewUpload(event)">
                        <label for="foto_bukti" class="cursor-pointer flex flex-col items-center justify-center gap-2">
                            <span class="w-10 h-10 rounded-full bg-white border border-[#ececee] flex items-center justify-center text-[#71717a]">
                                <i data-lucide="camera" class="w-5 h-5"></i>
                            </span>
                            <div class="text-[13px] font-medium text-[#09090b]">
                                Klik untuk mengunggah foto kondisi lapangan
                            </div>
                            <div class="text-[11px] text-[#71717a]">
                                Format JPG, PNG, atau WEBP (Maksimal 2 MB)
                            </div>
                            <div id="preview-filename" class="text-[12px] text-emerald-600 font-semibold mt-1 hidden"></div>
                        </label>
                    </div>
                    <?php if (isset($errors['foto_bukti'])): ?>
                        <p class="text-[#ff5a00] text-[12px] mt-1 font-medium"><?= $errors['foto_bukti']; ?></p>
                    <?php endif; ?>
                </div>

                <!-- 7. Deskripsi Lengkap Kronologi -->
                <div>
                    <label class="block text-[13px] font-medium text-[#18181b] mb-1.5">
                        Isi Laporan / Kronologi Kejadian <span class="text-[#ff5a00]">*</span>
                    </label>
                    <textarea name="isi_laporan" 
                              id="isi_laporan"
                              rows="5" 
                              placeholder="Deskripsikan permasalahan secara terperinci: sejak kapan terjadi, dampak terhadap warga, dan hal yang diharapkan..." 
                              required
                              class="w-full p-4 bg-white border <?= isset($errors['isi_laporan']) ? 'border-[#ff5a00]' : 'border-[#ececee]'; ?> focus:border-[#09090b] rounded-[14px] text-[14px] text-[#18181b] outline-none transition"><?= htmlspecialchars($isi_laporan); ?></textarea>
                    <?php if (isset($errors['isi_laporan'])): ?>
                        <p class="text-[#ff5a00] text-[12px] mt-1 font-medium"><?= $errors['isi_laporan']; ?></p>
                    <?php endif; ?>
                </div>

                <!-- 8. Checkbox Pelaporan Anonim (Whistleblower Protection) -->
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
                            Nama dan kontak Anda tidak akan ditampilkan kepada publik di tabel umum aduan. Hanya admin dinas verifikator yang dapat memvalidasi data Anda.
                        </p>
                    </div>
                </div>

                <!-- Tombol Aksi Formulir -->
                <div class="pt-4 border-t border-[#ececee] flex flex-col sm:flex-row items-center justify-between gap-4">
                    <button type="button" 
                            onclick="clearFormDraft()" 
                            class="text-[13px] text-[#71717a] hover:text-[#09090b] transition">
                        Bersihkan Draf Teks
                    </button>

                    <div class="flex items-center gap-3 w-full sm:w-auto">
                        <a href="pengaduan.php" class="flex-1 sm:flex-none text-center px-6 py-3 rounded-[14px] border border-[#ececee] text-[#52525b] hover:text-[#09090b] hover:bg-[#f4f4f5] text-[13px] font-medium transition">
                            Batal
                        </a>
                        <button type="submit" 
                                class="flex-1 sm:flex-none px-8 py-3 bg-[#09090b] hover:bg-[#18181b] text-white rounded-[14px] font-medium text-[13px] flex items-center justify-center gap-2 transition cursor-pointer">
                            <span>Kirim Laporan Resmi</span>
                            <i data-lucide="send" class="w-4 h-4"></i>
                        </button>
                    </div>
                </div>

            </form>
        </div>

    </main>
</div>

<!-- Script Leaflet Pin Map Picker & Draft Autosave -->
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // 1. Inisialisasi Peta Picker Leaflet
        const defaultLat = <?= $latitude; ?>;
        const defaultLng = <?= $longitude; ?>;
        
        const pickerMap = L.map('picker-map').setView([defaultLat, defaultLng], 12);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 18,
            attribution: '&copy; OpenStreetMap'
        }).addTo(pickerMap);

        // Marker yang dapat digeser (Draggable Marker)
        let pinMarker = L.marker([defaultLat, defaultLng], {
            draggable: true
        }).addTo(pickerMap);

        function updateCoordinates(lat, lng) {
            document.getElementById('input-latitude').value = lat.toFixed(7);
            document.getElementById('input-longitude').value = lng.toFixed(7);
            document.getElementById('display-coords').textContent = lat.toFixed(6) + ', ' + lng.toFixed(6);
        }

        // Event saat pin digeser
        pinMarker.on('dragend', function (e) {
            const position = pinMarker.getLatLng();
            updateCoordinates(position.lat, position.lng);
        });

        // Event saat klik pada peta
        pickerMap.on('click', function (e) {
            pinMarker.setLatLng(e.latlng);
            updateCoordinates(e.latlng.lat, e.latlng.lng);
        });

        // 2. Draft Autosave (localStorage)
        const judulField = document.getElementById('judul_laporan');
        const isiField   = document.getElementById('isi_laporan');
        const alamatField = document.getElementById('alamat_detail');
        const draftStatus = document.getElementById('draft-status');

        // Pulihkan draf jika tersedia di localStorage
        const savedJudul = localStorage.getItem('draft_sukoharjo_judul');
        const savedIsi   = localStorage.getItem('draft_sukoharjo_isi');
        const savedAlamat = localStorage.getItem('draft_sukoharjo_alamat');

        if (savedJudul && !judulField.value) judulField.value = savedJudul;
        if (savedIsi && !isiField.value) isiField.value = savedIsi;
        if (savedAlamat && !alamatField.value) alamatField.value = savedAlamat;

        function saveDraft() {
            localStorage.setItem('draft_sukoharjo_judul', judulField.value);
            localStorage.setItem('draft_sukoharjo_isi', isiField.value);
            localStorage.setItem('draft_sukoharjo_alamat', alamatField.value);
            draftStatus.textContent = 'Draf tersimpan di peramban (' + new Date().toLocaleTimeString() + ')';
        }

        judulField.addEventListener('input', saveDraft);
        isiField.addEventListener('input', saveDraft);
        alamatField.addEventListener('input', saveDraft);

        // Hapus draft saat form berhasil dikirim
        document.getElementById('form-pengaduan').addEventListener('submit', function () {
            localStorage.removeItem('draft_sukoharjo_judul');
            localStorage.removeItem('draft_sukoharjo_isi');
            localStorage.removeItem('draft_sukoharjo_alamat');
        });
    });

    function previewUpload(event) {
        const file = event.target.files[0];
        const previewEl = document.getElementById('preview-filename');
        if (file) {
            previewEl.textContent = 'Berkas dipilih: ' + file.name + ' (' + (file.size / 1024 / 1024).toFixed(2) + ' MB)';
            previewEl.classList.remove('hidden');
        }
    }

    function clearFormDraft() {
        if (confirm('Apakah Anda yakin ingin mengosongkan draf formulir?')) {
            localStorage.removeItem('draft_sukoharjo_judul');
            localStorage.removeItem('draft_sukoharjo_isi');
            localStorage.removeItem('draft_sukoharjo_alamat');
            document.getElementById('judul_laporan').value = '';
            document.getElementById('isi_laporan').value = '';
            document.getElementById('alamat_detail').value = '';
            document.getElementById('draft-status').textContent = 'Draf dikosongkan';
        }
    }
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

