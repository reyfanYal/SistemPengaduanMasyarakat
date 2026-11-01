<?php
/**
 * ==============================================================================
 * PENDAFTARAN AKUN WARGA SUKOHARJO (REGISTER)
 * ePengaduan Kabupaten Sukoharjo - UTS Pemrograman Web 2
 * ==============================================================================
 */

require_once __DIR__ . '/config/koneksi.php';
require_once __DIR__ . '/config/auth.php';

// Jika pengguna sudah login, alihkan ke dashboard
if (is_logged_in()) {
    header('Location: index.php');
    exit;
}

$errors = [];
$nik = '';
$nama_lengkap = '';
$username = '';
$email = '';
$telepon = '';
$kecamatan = 'Sukoharjo';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'register') {
    $nik          = trim($_POST['nik'] ?? '');
    $nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
    $username     = strtolower(trim($_POST['username'] ?? ''));
    $email        = trim($_POST['email'] ?? '');
    $telepon      = trim($_POST['telepon'] ?? '');
    $kecamatan    = trim($_POST['kecamatan'] ?? 'Sukoharjo');
    $password     = $_POST['password'] ?? '';
    $konfirmasi   = $_POST['konfirmasi_password'] ?? '';

    // 1. Validasi NIK (16 Digit Angka)
    if (empty($nik)) {
        $errors['nik'] = 'Nomor Induk Kependudukan (NIK) wajib diisi.';
    } elseif (!preg_match('/^[0-9]{16}$/', $nik)) {
        $errors['nik'] = 'NIK harus berupa 16 digit angka sesuai KTP.';
    } else {
        // Cek duplikasi NIK
        $stmt = mysqli_prepare($koneksi, "SELECT id_user FROM users WHERE nik = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "s", $nik);
        mysqli_stmt_execute($stmt);
        if (mysqli_num_rows(mysqli_stmt_get_result($stmt)) > 0) {
            $errors['nik'] = 'NIK tersebut sudah terdaftar pada sistem.';
        }
        mysqli_stmt_close($stmt);
    }

    // 2. Validasi Nama Lengkap
    if (empty($nama_lengkap)) {
        $errors['nama_lengkap'] = 'Nama lengkap wajib diisi.';
    } elseif (strlen($nama_lengkap) < 3) {
        $errors['nama_lengkap'] = 'Nama lengkap minimal 3 karakter.';
    }

    // 3. Validasi Username
    if (empty($username)) {
        $errors['username'] = 'Username wajib diisi.';
    } elseif (!preg_match('/^[a-z0-9_]{4,30}$/', $username)) {
        $errors['username'] = 'Username harus 4-30 karakter, hanya huruf kecil, angka, atau garis bawah (_).';
    } else {
        // Cek duplikasi Username
        $stmt = mysqli_prepare($koneksi, "SELECT id_user FROM users WHERE username = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        if (mysqli_num_rows(mysqli_stmt_get_result($stmt)) > 0) {
            $errors['username'] = 'Username sudah dipakai oleh pengguna lain.';
        }
        mysqli_stmt_close($stmt);
    }

    // 4. Validasi Email
    if (empty($email)) {
        $errors['email'] = 'Alamat email wajib diisi.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Format alamat email tidak valid.';
    }

    // 5. Validasi Telepon
    if (empty($telepon)) {
        $errors['telepon'] = 'Nomor telepon/WhatsApp wajib diisi.';
    }

    // 6. Validasi Password
    if (empty($password)) {
        $errors['password'] = 'Kata sandi wajib diisi.';
    } elseif (strlen($password) < 6) {
        $errors['password'] = 'Kata sandi minimal 6 karakter.';
    } elseif ($password !== $konfirmasi) {
        $errors['konfirmasi_password'] = 'Konfirmasi kata sandi tidak cocok.';
    }

    // Jika seluruh validasi lolos, simpan akun baru
    if (empty($errors)) {
        $hashed_password = hash_user_password($password);
        $role = 'warga';

        $stmt = mysqli_prepare($koneksi, "INSERT INTO users (nik, nama_lengkap, username, email, telepon, kecamatan, password, role) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "ssssssss", $nik, $nama_lengkap, $username, $email, $telepon, $kecamatan, $hashed_password, $role);
        
        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            set_flash('success', 'Registrasi berhasil! Silakan masuk menggunakan NIK/Username Anda.');
            header('Location: login.php');
            exit;
        } else {
            $errors['general'] = 'Terjadi kesalahan sistem saat menyimpan data akun. Silakan coba beberapa saat lagi.';
            mysqli_stmt_close($stmt);
        }
    }
}

$kecamatan_list = get_kecamatan_sukoharjo();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pendaftaran Warga Sukoharjo — ePengaduan Kab. Sukoharjo</title>
    <!-- Google Fonts: DM Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,100..1000;1,9..40,100..1000&display=swap" rel="stylesheet">
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Lucide Icons CDN -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body { font-family: 'DM Sans', sans-serif; }
    </style>
</head>
<body class="bg-[#f4f4f5] text-[#18181b] min-h-screen flex items-center justify-center p-4 lg:p-10 antialiased">

    <div class="w-full max-w-[800px] bg-white rounded-[36px] border border-[#ececee] p-8 sm:p-12">
        
        <!-- Header Registrasi -->
        <div class="flex items-center justify-between mb-8 pb-6 border-b border-[#ececee]">
            <div>
                <div class="inline-flex items-center gap-2 bg-[#ff5a00] text-white text-[11px] font-bold uppercase tracking-wider px-3 py-1 rounded-[12px] mb-2">
                    KAB. SUKOHARJO
                </div>
                <h1 class="text-[30px] font-semibold text-[#09090b] tracking-tight">
                    Pendaftaran Akun Warga
                </h1>
                <p class="text-[14px] text-[#52525b] mt-1">
                    Lengkapi identitas Anda untuk menyampaikan aspirasi dan pengaduan pembangunan Sukoharjo.
                </p>
            </div>
            <a href="login.php" class="text-[13px] text-[#71717a] hover:text-[#09090b] flex items-center gap-1 font-medium transition">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
                <span>Kembali Masuk</span>
            </a>
        </div>

        <?php if (!empty($errors['general'])): ?>
            <div class="mb-6 p-4 rounded-[18px] bg-[#fff5f0] border border-[#ffcca8] text-[#18181b] flex items-center gap-3">
                <span class="w-5 h-5 rounded-full bg-[#ff5a00] text-white flex items-center justify-center text-[12px] font-bold shrink-0">!</span>
                <span class="text-[13px]"><?= htmlspecialchars($errors['general']); ?></span>
            </div>
        <?php endif; ?>

        <!-- Form Pendaftaran -->
        <form action="register.php" method="POST" class="space-y-6">
            <input type="hidden" name="action" value="register">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- NIK -->
                <div>
                    <label class="block text-[13px] font-medium text-[#18181b] mb-1.5">
                        Nomor Induk Kependudukan (NIK 16 Digit) <span class="text-[#ff5a00]">*</span>
                    </label>
                    <input type="text" 
                           name="nik" 
                           maxlength="16"
                           value="<?= htmlspecialchars($nik); ?>" 
                           placeholder="331109xxxxxxxxxx" 
                           required
                           class="w-full h-12 px-4 bg-white border <?= isset($errors['nik']) ? 'border-[#ff5a00]' : 'border-[#ececee]'; ?> focus:border-[#09090b] rounded-[14px] text-[14px] text-[#18181b] outline-none transition">
                    <?php if (isset($errors['nik'])): ?>
                        <p class="text-[#ff5a00] text-[12px] mt-1 font-medium"><?= $errors['nik']; ?></p>
                    <?php endif; ?>
                </div>

                <!-- Nama Lengkap -->
                <div>
                    <label class="block text-[13px] font-medium text-[#18181b] mb-1.5">
                        Nama Lengkap (Sesuai KTP) <span class="text-[#ff5a00]">*</span>
                    </label>
                    <input type="text" 
                           name="nama_lengkap" 
                           value="<?= htmlspecialchars($nama_lengkap); ?>" 
                           placeholder="Contoh: Arjuna Pratama" 
                           required
                           class="w-full h-12 px-4 bg-white border <?= isset($errors['nama_lengkap']) ? 'border-[#ff5a00]' : 'border-[#ececee]'; ?> focus:border-[#09090b] rounded-[14px] text-[14px] text-[#18181b] outline-none transition">
                    <?php if (isset($errors['nama_lengkap'])): ?>
                        <p class="text-[#ff5a00] text-[12px] mt-1 font-medium"><?= $errors['nama_lengkap']; ?></p>
                    <?php endif; ?>
                </div>

                <!-- Username -->
                <div>
                    <label class="block text-[13px] font-medium text-[#18181b] mb-1.5">
                        Username Login <span class="text-[#ff5a00]">*</span>
                    </label>
                    <input type="text" 
                           name="username" 
                           value="<?= htmlspecialchars($username); ?>" 
                           placeholder="contoh: arjuna99" 
                           required
                           class="w-full h-12 px-4 bg-white border <?= isset($errors['username']) ? 'border-[#ff5a00]' : 'border-[#ececee]'; ?> focus:border-[#09090b] rounded-[14px] text-[14px] text-[#18181b] outline-none transition">
                    <?php if (isset($errors['username'])): ?>
                        <p class="text-[#ff5a00] text-[12px] mt-1 font-medium"><?= $errors['username']; ?></p>
                    <?php endif; ?>
                </div>

                <!-- Email -->
                <div>
                    <label class="block text-[13px] font-medium text-[#18181b] mb-1.5">
                        Alamat Email Aktif <span class="text-[#ff5a00]">*</span>
                    </label>
                    <input type="email" 
                           name="email" 
                           value="<?= htmlspecialchars($email); ?>" 
                           placeholder="nama@email.com" 
                           required
                           class="w-full h-12 px-4 bg-white border <?= isset($errors['email']) ? 'border-[#ff5a00]' : 'border-[#ececee]'; ?> focus:border-[#09090b] rounded-[14px] text-[14px] text-[#18181b] outline-none transition">
                    <?php if (isset($errors['email'])): ?>
                        <p class="text-[#ff5a00] text-[12px] mt-1 font-medium"><?= $errors['email']; ?></p>
                    <?php endif; ?>
                </div>

                <!-- Telepon / WA -->
                <div>
                    <label class="block text-[13px] font-medium text-[#18181b] mb-1.5">
                        Nomor WhatsApp / HP <span class="text-[#ff5a00]">*</span>
                    </label>
                    <input type="text" 
                           name="telepon" 
                           value="<?= htmlspecialchars($telepon); ?>" 
                           placeholder="081234567890" 
                           required
                           class="w-full h-12 px-4 bg-white border <?= isset($errors['telepon']) ? 'border-[#ff5a00]' : 'border-[#ececee]'; ?> focus:border-[#09090b] rounded-[14px] text-[14px] text-[#18181b] outline-none transition">
                    <?php if (isset($errors['telepon'])): ?>
                        <p class="text-[#ff5a00] text-[12px] mt-1 font-medium"><?= $errors['telepon']; ?></p>
                    <?php endif; ?>
                </div>

                <!-- Kecamatan Domisili Sukoharjo -->
                <div>
                    <label class="block text-[13px] font-medium text-[#18181b] mb-1.5">
                        Kecamatan Domisili (Kab. Sukoharjo) <span class="text-[#ff5a00]">*</span>
                    </label>
                    <select name="kecamatan" 
                            class="w-full h-12 px-4 bg-white border border-[#ececee] focus:border-[#09090b] rounded-[14px] text-[14px] text-[#18181b] outline-none transition cursor-pointer">
                        <?php foreach ($kecamatan_list as $kec): ?>
                            <option value="<?= htmlspecialchars($kec); ?>" <?= ($kecamatan === $kec) ? 'selected' : ''; ?>>
                                Kecamatan <?= htmlspecialchars($kec); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Kata Sandi -->
                <div>
                    <label class="block text-[13px] font-medium text-[#18181b] mb-1.5">
                        Kata Sandi (Minimal 6 Karakter) <span class="text-[#ff5a00]">*</span>
                    </label>
                    <input type="password" 
                           name="password" 
                           placeholder="••••••••" 
                           required
                           class="w-full h-12 px-4 bg-white border <?= isset($errors['password']) ? 'border-[#ff5a00]' : 'border-[#ececee]'; ?> focus:border-[#09090b] rounded-[14px] text-[14px] text-[#18181b] outline-none transition">
                    <?php if (isset($errors['password'])): ?>
                        <p class="text-[#ff5a00] text-[12px] mt-1 font-medium"><?= $errors['password']; ?></p>
                    <?php endif; ?>
                </div>

                <!-- Konfirmasi Kata Sandi -->
                <div>
                    <label class="block text-[13px] font-medium text-[#18181b] mb-1.5">
                        Ulangi Kata Sandi <span class="text-[#ff5a00]">*</span>
                    </label>
                    <input type="password" 
                           name="konfirmasi_password" 
                           placeholder="••••••••" 
                           required
                           class="w-full h-12 px-4 bg-white border <?= isset($errors['konfirmasi_password']) ? 'border-[#ff5a00]' : 'border-[#ececee]'; ?> focus:border-[#09090b] rounded-[14px] text-[14px] text-[#18181b] outline-none transition">
                    <?php if (isset($errors['konfirmasi_password'])): ?>
                        <p class="text-[#ff5a00] text-[12px] mt-1 font-medium"><?= $errors['konfirmasi_password']; ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Ketentuan Layanan -->
            <div class="pt-4 border-t border-[#ececee] flex items-start gap-3">
                <input type="checkbox" id="terms" required class="mt-1 w-4 h-4 rounded border-[#ececee] text-[#09090b] focus:ring-0">
                <label for="terms" class="text-[13px] text-[#52525b] leading-relaxed">
                    Saya menyatakan bahwa data kependudukan yang saya cantumkan adalah sah dan bersedia menggunakan layanan pengaduan secara bertanggung jawab sesuai peraturan Pemerintah Kabupaten Sukoharjo.
                </label>
            </div>

            <div class="flex flex-col sm:flex-row items-center justify-between gap-4 pt-2">
                <a href="login.php" class="text-[14px] text-[#71717a] hover:text-[#09090b] transition">
                    Sudah punya akun? Masuk
                </a>
                <button type="submit" 
                        class="w-full sm:w-auto px-8 h-12 bg-[#09090b] hover:bg-[#18181b] text-white rounded-[14px] font-medium text-[14px] flex items-center justify-center gap-2 transition cursor-pointer">
                    <span>Selesaikan Pendaftaran</span>
                    <i data-lucide="check" class="w-4 h-4"></i>
                </button>
            </div>
        </form>

    </div>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>

