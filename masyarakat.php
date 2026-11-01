<?php
/**
 * ==============================================================================
 * CRUD MASTER DATA MASYARAKAT / PELAPOR (ADMIN ONLY)
 * ePengaduan Kabupaten Sukoharjo - UTS Pemrograman Web 2
 * ==============================================================================
 */

require_once __DIR__ . '/config/koneksi.php';
require_once __DIR__ . '/config/auth.php';

require_admin();

$currentUser = current_user();
$currentUserId = current_user_id();
$errors = [];

// 1. TAMBAH AKUN MASYARAKAT BARU (CREATE)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_user') {
    $nik          = trim($_POST['nik'] ?? '');
    $nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
    $username     = strtolower(trim($_POST['username'] ?? ''));
    $email        = trim($_POST['email'] ?? '');
    $telepon      = trim($_POST['telepon'] ?? '');
    $kecamatan    = trim($_POST['kecamatan'] ?? 'Sukoharjo');
    $password     = $_POST['password'] ?? '123456';
    $role         = trim($_POST['role'] ?? 'warga');

    if (empty($nik) || !preg_match('/^[0-9]{16}$/', $nik)) {
        $errors['nik'] = 'NIK harus 16 digit angka.';
    }
    if (empty($nama_lengkap)) {
        $errors['nama_lengkap'] = 'Nama lengkap wajib diisi.';
    }
    if (empty($username)) {
        $errors['username'] = 'Username wajib diisi.';
    }

    // Cek duplikasi
    $stmt_check = mysqli_prepare($koneksi, "SELECT id_user FROM users WHERE nik = ? OR username = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt_check, "ss", $nik, $username);
    mysqli_stmt_execute($stmt_check);
    if (mysqli_num_rows(mysqli_stmt_get_result($stmt_check)) > 0) {
        $errors['general'] = 'NIK atau Username tersebut sudah terdaftar.';
    }
    mysqli_stmt_close($stmt_check);

    if (empty($errors)) {
        $hashed = hash_user_password($password);
        $stmt_ins = mysqli_prepare($koneksi, "INSERT INTO users (nik, nama_lengkap, username, email, telepon, kecamatan, password, role) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt_ins, "ssssssss", $nik, $nama_lengkap, $username, $email, $telepon, $kecamatan, $hashed, $role);
        if (mysqli_stmt_execute($stmt_ins)) {
            mysqli_stmt_close($stmt_ins);
            set_flash('success', "Akun warga '{$nama_lengkap}' berhasil didaftarkan.");
            header('Location: masyarakat.php');
            exit;
        } else {
            $errors['general'] = 'Gagal menyimpan data akun: ' . mysqli_error($koneksi);
            mysqli_stmt_close($stmt_ins);
        }
    }
}

// 2. UBAH DATA MASYARAKAT (UPDATE)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_user') {
    $id_user      = (int)($_POST['id_user'] ?? 0);
    $nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
    $email        = trim($_POST['email'] ?? '');
    $telepon      = trim($_POST['telepon'] ?? '');
    $kecamatan    = trim($_POST['kecamatan'] ?? 'Sukoharjo');
    $role         = trim($_POST['role'] ?? 'warga');
    $new_password = $_POST['new_password'] ?? '';

    if ($id_user > 0 && !empty($nama_lengkap)) {
        if (!empty($new_password)) {
            $hashed = hash_user_password($new_password);
            $stmt_up = mysqli_prepare($koneksi, "UPDATE users SET nama_lengkap = ?, email = ?, telepon = ?, kecamatan = ?, role = ?, password = ? WHERE id_user = ?");
            mysqli_stmt_bind_param($stmt_up, "ssssssi", $nama_lengkap, $email, $telepon, $kecamatan, $role, $hashed, $id_user);
        } else {
            $stmt_up = mysqli_prepare($koneksi, "UPDATE users SET nama_lengkap = ?, email = ?, telepon = ?, kecamatan = ?, role = ? WHERE id_user = ?");
            mysqli_stmt_bind_param($stmt_up, "sssssi", $nama_lengkap, $email, $telepon, $kecamatan, $role, $id_user);
        }

        if (mysqli_stmt_execute($stmt_up)) {
            mysqli_stmt_close($stmt_up);
            set_flash('success', "Data warga '{$nama_lengkap}' berhasil diperbarui.");
            header('Location: masyarakat.php');
            exit;
        } else {
            $errors['general'] = 'Gagal memperbarui data pengguna.';
            mysqli_stmt_close($stmt_up);
        }
    }
}

// 3. HAPUS DATA MASYARAKAT (DELETE)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_user') {
    $id_user = (int)($_POST['id_user'] ?? 0);

    if ($id_user === $currentUserId) {
        set_flash('error', 'Anda tidak dapat menghapus akun admin yang sedang Anda gunakan saat ini.');
    } elseif ($id_user > 0) {
        $stmt_del = mysqli_prepare($koneksi, "DELETE FROM users WHERE id_user = ?");
        mysqli_stmt_bind_param($stmt_del, "i", $id_user);
        mysqli_stmt_execute($stmt_del);
        mysqli_stmt_close($stmt_del);
        set_flash('success', 'Akun warga berhasil dihapus dari sistem.');
    }
    header('Location: masyarakat.php');
    exit;
}

// 4. READ & PENCARIAN
$search = trim($_GET['search'] ?? '');
$sql = "SELECT u.*, COUNT(p.id_pengaduan) as total_laporan 
        FROM users u 
        LEFT JOIN pengaduan p ON u.id_user = p.id_user ";
if (!empty($search)) {
    $sql .= "WHERE u.nik LIKE ? OR u.nama_lengkap LIKE ? OR u.username LIKE ? OR u.kecamatan LIKE ? ";
}
$sql .= "GROUP BY u.id_user ORDER BY u.created_at DESC";

$stmt = mysqli_prepare($koneksi, $sql);
if (!empty($search)) {
    $sp = "%{$search}%";
    mysqli_stmt_bind_param($stmt, "ssss", $sp, $sp, $sp, $sp);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$kecamatan_list = get_kecamatan_sukoharjo();

$page_title = 'Data Masyarakat & Pelapor';
$page_heading = 'Master Data Masyarakat Sukoharjo';

require_once __DIR__ . '/includes/head.php';
?>

<div class="flex min-h-screen">
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>

    <main class="ml-[260px] flex-1 p-8 max-w-[1200px] w-full">
        <?php require_once __DIR__ . '/includes/topbar.php'; ?>
        <?= render_flash_alerts(); ?>

        <!-- Toolbar Header & Tambah Pengguna -->
        <div class="bg-white rounded-[36px] border border-[#ececee] p-[28px] mb-8">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h2 class="text-[22px] font-semibold text-[#09090b] tracking-tight">
                        Database Warga & Pelapor
                    </h2>
                    <p class="text-[13px] text-[#52525b] mt-0.5">
                        Kelola akun penduduk Sukoharjo, verifikasi NIK, serta status hak akses akun sistem.
                    </p>
                </div>

                <button type="button" 
                        onclick="openCreateModal()" 
                        class="bg-[#09090b] hover:bg-[#18181b] text-white px-5 py-2.5 rounded-[14px] text-[13px] font-medium flex items-center gap-2 transition cursor-pointer">
                    <i data-lucide="user-plus" class="w-4 h-4"></i>
                    <span>Tambah Akun Baru</span>
                </button>
            </div>

            <!-- Form Pencarian Warga -->
            <form action="masyarakat.php" method="GET" class="mt-5 flex items-center gap-3">
                <div class="relative flex-1 max-w-md">
                    <i data-lucide="search" class="w-4 h-4 text-[#71717a] absolute left-3.5 top-3.5 pointer-events-none"></i>
                    <input type="text" 
                           name="search" 
                           value="<?= htmlspecialchars($search); ?>" 
                           placeholder="Cari NIK 16 digit, nama warga, atau kecamatan..." 
                           class="w-full h-11 pl-10 pr-4 bg-[#fafafa] border border-[#ececee] focus:border-[#09090b] rounded-[14px] text-[13px] text-[#18181b] outline-none transition">
                </div>
                <button type="submit" class="h-11 px-5 bg-[#09090b] text-white rounded-[14px] text-[13px] font-medium transition">
                    Cari
                </button>
                <?php if (!empty($search)): ?>
                    <a href="masyarakat.php" class="h-11 px-3 bg-[#f4f4f5] text-[#71717a] hover:text-[#09090b] rounded-[14px] flex items-center justify-center transition">
                        <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                    </a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Tabel Data Masyarakat (Awesomic Rounded 36px Card) -->
        <div class="bg-white rounded-[36px] border border-[#ececee] p-[28px] overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-[#ececee] text-[12px] text-[#71717a] uppercase font-semibold">
                            <th class="py-3.5 px-3">NIK & Username</th>
                            <th class="py-3.5 px-4">Nama Lengkap</th>
                            <th class="py-3.5 px-4">Kontak (Email / Telepon)</th>
                            <th class="py-3.5 px-3">Kecamatan Domisili</th>
                            <th class="py-3.5 px-3">Peran (Role)</th>
                            <th class="py-3.5 px-3 text-center">Total Aduan</th>
                            <th class="py-3.5 px-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#f4f4f5] text-[13px]">
                        <?php if (mysqli_num_rows($result) > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                <tr class="hover:bg-[#fafafa] transition">
                                    <td class="py-4 px-3 whitespace-nowrap">
                                        <div class="font-mono font-bold text-[#09090b]">
                                            <?= htmlspecialchars($row['nik']); ?>
                                        </div>
                                        <div class="text-[11px] text-[#71717a]">
                                            @<?= htmlspecialchars($row['username']); ?>
                                        </div>
                                    </td>
                                    <td class="py-4 px-4 font-semibold text-[#18181b]">
                                        <?= htmlspecialchars($row['nama_lengkap']); ?>
                                    </td>
                                    <td class="py-4 px-4">
                                        <div class="text-[#18181b]"><?= htmlspecialchars($row['email']); ?></div>
                                        <div class="text-[12px] text-[#71717a]"><?= htmlspecialchars($row['telepon']); ?></div>
                                    </td>
                                    <td class="py-4 px-3 text-[#52525b] whitespace-nowrap">
                                        Kec. <?= htmlspecialchars($row['kecamatan']); ?>
                                    </td>
                                    <td class="py-4 px-3 whitespace-nowrap">
                                        <?php if ($row['role'] === 'admin'): ?>
                                            <span class="inline-flex items-center gap-1 bg-[#09090b] text-white text-[11px] font-bold px-2.5 py-0.5 rounded-[10px]">
                                                Admin OPD
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center gap-1 bg-[#f4f4f5] text-[#18181b] border border-[#ececee] text-[11px] font-medium px-2.5 py-0.5 rounded-[10px]">
                                                Warga
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-4 px-3 text-center whitespace-nowrap">
                                        <span class="font-bold text-[#09090b]"><?= (int)$row['total_laporan']; ?></span>
                                    </td>
                                    <td class="py-4 px-3 text-right whitespace-nowrap">
                                        <div class="inline-flex items-center gap-1.5">
                                            <!-- Edit Button -->
                                            <button type="button" 
                                                    onclick="openEditModal('<?= $row['id_user']; ?>', '<?= htmlspecialchars(addslashes($row['nama_lengkap'])); ?>', '<?= htmlspecialchars(addslashes($row['email'])); ?>', '<?= htmlspecialchars(addslashes($row['telepon'])); ?>', '<?= htmlspecialchars(addslashes($row['kecamatan'])); ?>', '<?= $row['role']; ?>')"
                                                    class="h-8 px-2.5 bg-[#f4f4f5] hover:bg-[#09090b] hover:text-white rounded-[10px] text-[12px] font-medium flex items-center gap-1 transition"
                                                    title="Ubah Profil Warga">
                                                <i data-lucide="edit-3" class="w-3.5 h-3.5"></i>
                                                <span>Ubah</span>
                                            </button>

                                            <!-- Delete Button (kecuali akun sendiri) -->
                                            <?php if ($row['id_user'] != $currentUserId): ?>
                                                <button type="button" 
                                                        onclick="openDeleteModal('<?= $row['id_user']; ?>', '<?= htmlspecialchars(addslashes($row['nama_lengkap'])); ?>')"
                                                        class="h-8 px-2.5 bg-[#fee2e2] text-[#991b1b] hover:bg-rose-600 hover:text-white rounded-[10px] text-[12px] font-medium flex items-center gap-1 transition"
                                                        title="Hapus Akun">
                                                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="py-8 text-center text-[#71717a]">
                                    Tidak ada data masyarakat yang ditemukan.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>
</div>

<!-- Modal Tambah Pengguna Baru -->
<div id="create-modal" class="fixed inset-0 bg-black/40 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-[32px] border border-[#ececee] max-w-lg w-full p-7">
        <div class="flex items-center justify-between pb-4 mb-4 border-b border-[#ececee]">
            <h3 class="text-[18px] font-semibold text-[#09090b]">Tambah Akun Warga Baru</h3>
            <button type="button" onclick="closeCreateModal()" class="text-[#71717a] hover:text-[#09090b]">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
        <form action="masyarakat.php" method="POST" class="space-y-4">
            <input type="hidden" name="action" value="create_user">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-[13px] font-medium text-[#18181b] mb-1">NIK (16 Digit) <span class="text-[#ff5a00]">*</span></label>
                    <input type="text" name="nik" maxlength="16" required placeholder="3311xxxxxxxxxxxx" class="w-full h-11 px-3.5 bg-white border border-[#ececee] rounded-[12px] text-[13px] outline-none">
                </div>
                <div>
                    <label class="block text-[13px] font-medium text-[#18181b] mb-1">Username <span class="text-[#ff5a00]">*</span></label>
                    <input type="text" name="username" required placeholder="arjuna99" class="w-full h-11 px-3.5 bg-white border border-[#ececee] rounded-[12px] text-[13px] outline-none">
                </div>
            </div>
            <div>
                <label class="block text-[13px] font-medium text-[#18181b] mb-1">Nama Lengkap <span class="text-[#ff5a00]">*</span></label>
                <input type="text" name="nama_lengkap" required placeholder="Nama sesuai KTP" class="w-full h-11 px-3.5 bg-white border border-[#ececee] rounded-[12px] text-[13px] outline-none">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-[13px] font-medium text-[#18181b] mb-1">Email</label>
                    <input type="email" name="email" required placeholder="email@gmail.com" class="w-full h-11 px-3.5 bg-white border border-[#ececee] rounded-[12px] text-[13px] outline-none">
                </div>
                <div>
                    <label class="block text-[13px] font-medium text-[#18181b] mb-1">Telepon / WA</label>
                    <input type="text" name="telepon" required placeholder="08xxxxxxxxxx" class="w-full h-11 px-3.5 bg-white border border-[#ececee] rounded-[12px] text-[13px] outline-none">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-[13px] font-medium text-[#18181b] mb-1">Kecamatan</label>
                    <select name="kecamatan" class="w-full h-11 px-3 bg-white border border-[#ececee] rounded-[12px] text-[13px] outline-none">
                        <?php foreach ($kecamatan_list as $kec): ?>
                            <option value="<?= htmlspecialchars($kec); ?>">Kec. <?= htmlspecialchars($kec); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-[13px] font-medium text-[#18181b] mb-1">Peran Akun</label>
                    <select name="role" class="w-full h-11 px-3 bg-white border border-[#ececee] rounded-[12px] text-[13px] outline-none">
                        <option value="warga">Warga</option>
                        <option value="admin">Admin OPD</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-[13px] font-medium text-[#18181b] mb-1">Kata Sandi Awal (Default: <code>123456</code>)</label>
                <input type="text" name="password" value="123456" class="w-full h-11 px-3.5 bg-white border border-[#ececee] rounded-[12px] text-[13px] outline-none">
            </div>
            <div class="pt-3 border-t border-[#ececee] flex items-center justify-end gap-3">
                <button type="button" onclick="closeCreateModal()" class="px-5 py-2.5 rounded-[12px] border border-[#ececee] text-[13px] text-[#52525b]">Batal</button>
                <button type="submit" class="px-6 py-2.5 bg-[#09090b] text-white rounded-[12px] text-[13px] font-medium">Daftarkan Warga</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit Pengguna -->
<div id="edit-modal" class="fixed inset-0 bg-black/40 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-[32px] border border-[#ececee] max-w-lg w-full p-7">
        <div class="flex items-center justify-between pb-4 mb-4 border-b border-[#ececee]">
            <h3 class="text-[18px] font-semibold text-[#09090b]">Ubah Data Warga</h3>
            <button type="button" onclick="closeEditModal()" class="text-[#71717a] hover:text-[#09090b]">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
        <form action="masyarakat.php" method="POST" class="space-y-4">
            <input type="hidden" name="action" value="update_user">
            <input type="hidden" name="id_user" id="edit-user-id">
            <div>
                <label class="block text-[13px] font-medium text-[#18181b] mb-1">Nama Lengkap</label>
                <input type="text" name="nama_lengkap" id="edit-nama" required class="w-full h-11 px-3.5 bg-white border border-[#ececee] rounded-[12px] text-[13px] outline-none">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-[13px] font-medium text-[#18181b] mb-1">Email</label>
                    <input type="email" name="email" id="edit-email" required class="w-full h-11 px-3.5 bg-white border border-[#ececee] rounded-[12px] text-[13px] outline-none">
                </div>
                <div>
                    <label class="block text-[13px] font-medium text-[#18181b] mb-1">Telepon / WA</label>
                    <input type="text" name="telepon" id="edit-telepon" required class="w-full h-11 px-3.5 bg-white border border-[#ececee] rounded-[12px] text-[13px] outline-none">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-[13px] font-medium text-[#18181b] mb-1">Kecamatan</label>
                    <select name="kecamatan" id="edit-kecamatan" class="w-full h-11 px-3 bg-white border border-[#ececee] rounded-[12px] text-[13px] outline-none">
                        <?php foreach ($kecamatan_list as $kec): ?>
                            <option value="<?= htmlspecialchars($kec); ?>">Kec. <?= htmlspecialchars($kec); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-[13px] font-medium text-[#18181b] mb-1">Peran Akun</label>
                    <select name="role" id="edit-role" class="w-full h-11 px-3 bg-white border border-[#ececee] rounded-[12px] text-[13px] outline-none">
                        <option value="warga">Warga</option>
                        <option value="admin">Admin OPD</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-[13px] font-medium text-[#18181b] mb-1">Ganti Kata Sandi (Kosongkan jika tidak diubah)</label>
                <input type="password" name="new_password" placeholder="••••••••" class="w-full h-11 px-3.5 bg-white border border-[#ececee] rounded-[12px] text-[13px] outline-none">
            </div>
            <div class="pt-3 border-t border-[#ececee] flex items-center justify-end gap-3">
                <button type="button" onclick="closeEditModal()" class="px-5 py-2.5 rounded-[12px] border border-[#ececee] text-[13px] text-[#52525b]">Batal</button>
                <button type="submit" class="px-6 py-2.5 bg-[#09090b] text-white rounded-[12px] text-[13px] font-medium">Perbarui Data</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Konfirmasi Hapus Pengguna -->
<div id="delete-modal" class="fixed inset-0 bg-black/40 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-[32px] border border-[#ececee] max-w-md w-full p-7">
        <h3 class="text-[18px] font-semibold text-[#09090b] mb-2">Konfirmasi Hapus Akun Warga</h3>
        <p class="text-[14px] text-[#52525b] mb-6 leading-relaxed">
            Apakah Anda yakin ingin menghapus akun <strong id="delete-user-name" class="text-[#09090b]"></strong>? Seluruh data laporan yang terkait akun ini juga akan terhapus secara permanen.
        </p>
        <form action="masyarakat.php" method="POST" class="flex items-center justify-end gap-3">
            <input type="hidden" name="action" value="delete_user">
            <input type="hidden" name="id_user" id="delete-user-id">
            <button type="button" onclick="closeDeleteModal()" class="px-5 py-2.5 rounded-[12px] border border-[#ececee] text-[13px] text-[#52525b]">Batal</button>
            <button type="submit" class="px-6 py-2.5 bg-rose-600 hover:bg-rose-700 text-white rounded-[12px] text-[13px] font-medium">Hapus Akun</button>
        </form>
    </div>
</div>

<script>
    function openCreateModal() { document.getElementById('create-modal').classList.remove('hidden'); }
    function closeCreateModal() { document.getElementById('create-modal').classList.add('hidden'); }

    function openEditModal(id, nama, email, telepon, kecamatan, role) {
        document.getElementById('edit-user-id').value = id;
        document.getElementById('edit-nama').value = nama;
        document.getElementById('edit-email').value = email;
        document.getElementById('edit-telepon').value = telepon;
        document.getElementById('edit-kecamatan').value = kecamatan;
        document.getElementById('edit-role').value = role;
        document.getElementById('edit-modal').classList.remove('hidden');
    }
    function closeEditModal() { document.getElementById('edit-modal').classList.add('hidden'); }

    function openDeleteModal(id, nama) {
        document.getElementById('delete-user-id').value = id;
        document.getElementById('delete-user-name').textContent = nama;
        document.getElementById('delete-modal').classList.remove('hidden');
    }
    function closeDeleteModal() { document.getElementById('delete-modal').classList.add('hidden'); }
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

