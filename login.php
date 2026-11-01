<?php
/**
 * ==============================================================================
 * HALAMAN MASUK (LOGIN) & PUBLIC LIVE TRACKING PORTAL
 * ePengaduan Kabupaten Sukoharjo - UTS Pemrograman Web 2
 * ==============================================================================
 */

require_once __DIR__ . '/config/koneksi.php';
require_once __DIR__ . '/config/auth.php';

// Jika pengguna sudah login, langsung alihkan ke Dashboard
if (is_logged_in()) {
    header('Location: index.php');
    exit;
}

$error_message = '';
$input_identity = '';

// Proses Autentikasi Pengguna
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $input_identity = trim($_POST['identity'] ?? '');
    $input_password = $_POST['password'] ?? '';

    if (empty($input_identity) || empty($input_password)) {
        $error_message = 'Silakan masukkan Username / NIK dan kata sandi Anda.';
    } else {
        // Cari pengguna berdasarkan username ATAU nik menggunakan Prepared Statement
        $stmt = mysqli_prepare($koneksi, "SELECT id_user, nik, nama_lengkap, username, email, telepon, kecamatan, password, role FROM users WHERE username = ? OR nik = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "ss", $input_identity, $input_identity);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($user = mysqli_fetch_assoc($result)) {
            // Verifikasi Password (Bcrypt dengan Fallback Demo '123456')
            if (verify_user_password($input_password, $user['password'])) {
                // Regenerasi session ID untuk keamanan
                session_regenerate_id(true);

                // Simpan data esensial ke sesi
                $_SESSION['user'] = [
                    'id_user'      => $user['id_user'],
                    'nik'          => $user['nik'],
                    'nama_lengkap' => $user['nama_lengkap'],
                    'username'     => $user['username'],
                    'email'        => $user['email'],
                    'telepon'      => $user['telepon'],
                    'kecamatan'    => $user['kecamatan'],
                    'role'         => $user['role']
                ];

                set_flash('success', 'Selamat datang kembali, ' . $user['nama_lengkap'] . '!');
                header('Location: index.php');
                exit;
            } else {
                $error_message = 'Kata sandi yang Anda masukkan salah. Coba gunakan: 123456';
            }
        } else {
            $error_message = 'Akun dengan Username atau NIK tersebut tidak ditemukan.';
        }
        mysqli_stmt_close($stmt);
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Pengaduan & Masuk — ePengaduan Kab. Sukoharjo</title>
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
<body class="bg-[#f4f4f5] text-[#18181b] min-h-screen flex items-center justify-center p-4 lg:p-8 antialiased">

    <div class="w-full max-w-[1360px] mx-auto flex flex-col lg:flex-row gap-8 lg:gap-10 items-stretch">
        
        <!-- ============================================================================== -->
        <!-- KOLOM KIRI: EDITORIAL HERO & PUBLIC LIVE TRACKING WIDGET (55% WIDTH) -->
        <!-- ============================================================================== -->
        <div class="w-full lg:w-[56%] flex flex-col justify-between p-8 sm:p-12 lg:p-14 bg-white rounded-[36px] border border-[#ececee]">
            
            <div class="flex flex-col">
                <!-- Civic Live Badge -->
                <div class="inline-flex items-center gap-2.5 self-start px-3.5 py-1.5 rounded-full bg-[#f4f4f5] border border-[#ececee] mb-8">
                    <span class="relative flex h-2 w-2">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                    </span>
                    <span class="text-[11px] uppercase text-[#52525b] font-semibold tracking-wider">
                        SISTEM LAYANAN ASPIRASI & PENGADUAN SUKOHARJO
                    </span>
                </div>

                <!-- Display Hero Headline -->
                <h1 class="text-[38px] sm:text-[46px] lg:text-[50px] font-semibold text-[#09090b] tracking-tight leading-[1.12] mb-5">
                    Suara Warga, Wujudkan Sukoharjo Lebih Baik.
                </h1>

                <!-- Institutional Narrative -->
                <p class="text-[15px] sm:text-[16px] text-[#52525b] max-w-xl leading-relaxed mb-8">
                    Kanal resmi Pemerintah Kabupaten Sukoharjo untuk menyampaikan laporan infrastruktur, ketertiban umum, dan pelayanan publik. Dilengkapi pelacakan geospasial real-time dan jaminan verifikasi cepat.
                </p>

                <!-- Public Live Tracking Widget -->
                <div class="w-full bg-[#f4f4f5] p-2.5 rounded-[22px] border border-[#ececee] mb-3">
                    <form action="detail_pengaduan.php" method="GET" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 bg-white p-2 rounded-[18px] border border-[#ececee]">
                        <div class="flex items-center flex-1 pl-3 gap-3">
                            <i data-lucide="search" class="w-5 h-5 text-[#71717a] shrink-0"></i>
                            <input type="text" 
                                   name="kode" 
                                   placeholder="Masukkan Kode Tracking (Contoh: SKH-2026-0001)" 
                                   required 
                                   class="w-full py-2.5 text-[#18181b] placeholder:text-[#71717a] text-[14px] bg-transparent outline-none border-0">
                        </div>
                        <button type="submit" 
                                class="inline-flex items-center justify-center gap-2 bg-[#09090b] hover:bg-[#27272a] text-white px-6 py-3.5 rounded-[14px] text-[14px] font-medium transition cursor-pointer select-none">
                            <span>Lacak Tanpa Login</span>
                            <i data-lucide="arrow-right" class="w-4 h-4"></i>
                        </button>
                    </form>
                </div>

                <div class="flex items-center gap-2 px-2 text-[#71717a] text-[12px] mb-8">
                    <span class="w-1.5 h-1.5 rounded-full bg-[#ff5a00]"></span>
                    <span>Pantau progres aduan jalan, PJU, sampah, dan fasilitas umum secara transparan tanpa perlu akun.</span>
                </div>
            </div>

            <!-- Metrics Grid Footer -->
            <div class="pt-8 border-t border-[#f4f4f5] grid grid-cols-1 sm:grid-cols-3 gap-6">
                <!-- Stat 1 -->
                <div class="flex flex-col">
                    <div class="flex items-baseline gap-2 mb-1">
                        <span class="text-[32px] sm:text-[36px] font-bold text-[#09090b]">98.4%</span>
                        <span class="inline-flex items-center text-[11px] font-semibold text-emerald-700 bg-emerald-50 px-1.5 py-0.5 rounded-[6px]">
                            +1.2%
                        </span>
                    </div>
                    <span class="text-[13px] text-[#52525b]">Tingkat Penyelesaian</span>
                </div>

                <!-- Stat 2 -->
                <div class="flex flex-col">
                    <div class="flex items-baseline gap-2 mb-1">
                        <span class="text-[32px] sm:text-[36px] font-bold text-[#09090b]">&lt; 24 Jam</span>
                        <span class="inline-flex items-center text-[10px] font-bold uppercase tracking-wider text-white bg-[#ff5a00] px-1.5 py-0.5 rounded-[6px]">
                            SLA Garansi
                        </span>
                    </div>
                    <span class="text-[13px] text-[#52525b]">Respons Verifikasi</span>
                </div>

                <!-- Stat 3 -->
                <div class="flex flex-col">
                    <div class="flex items-baseline gap-2 mb-1">
                        <span class="text-[32px] sm:text-[36px] font-bold text-[#09090b]">12</span>
                        <span class="text-[12px] text-[#71717a] font-medium">Kecamatan</span>
                    </div>
                    <span class="text-[13px] text-[#52525b] truncate" title="Kartasura, Grogol, Baki, Sukoharjo, dll">Wilayah Kab. Sukoharjo</span>
                </div>
            </div>

        </div>

        <!-- ============================================================================== -->
        <!-- KOLOM KANAN: FORMULIR OTENTIKASI & KREDENSIAL DEMO (44% WIDTH) -->
        <!-- ============================================================================== -->
        <div class="w-full lg:w-[44%] flex items-center justify-center">
            <div class="w-full max-w-[490px] bg-white rounded-[36px] border border-[#ececee] p-8 sm:p-10 flex flex-col justify-between">
                
                <div>
                    <!-- Header Kartu Login -->
                    <div class="flex items-center justify-between mb-6">
                        <span class="text-[11px] uppercase tracking-wider font-bold text-[#71717a]">Portal Masuk Terpadu</span>
                        <span class="bg-[#f4f4f5] text-[#18181b] border border-[#ececee] text-[11px] font-semibold px-2.5 py-1 rounded-[12px]">
                            Versi 2.4
                        </span>
                    </div>

                    <h2 class="text-[28px] font-semibold text-[#09090b] tracking-tight mb-2">
                        Masuk ke Akun
                    </h2>
                    <p class="text-[14px] text-[#52525b] mb-6">
                        Gunakan akun warga atau petugas untuk mengakses fitur pelaporan dan tindak lanjut.
                    </p>

                    <!-- Alert Error / Flash Message -->
                    <?php if (!empty($error_message)): ?>
                        <div class="mb-5 p-4 rounded-[18px] bg-[#fff5f0] border border-[#ffcca8] flex items-start gap-3 text-[#18181b]">
                            <span class="w-5 h-5 rounded-full bg-[#ff5a00] text-white flex items-center justify-center text-[12px] font-bold shrink-0 mt-0.5">!</span>
                            <p class="text-[13px] leading-snug"><?= htmlspecialchars($error_message); ?></p>
                        </div>
                    <?php endif; ?>

                    <?= render_flash_alerts(); ?>

                    <!-- Form Login -->
                    <form action="login.php" method="POST" class="space-y-4">
                        <input type="hidden" name="action" value="login">

                        <div>
                            <label class="block text-[13px] font-medium text-[#18181b] mb-1.5">
                                Username atau NIK (16 Digit)
                            </label>
                            <div class="relative flex items-center">
                                <i data-lucide="user" class="w-4 h-4 text-[#71717a] absolute left-4"></i>
                                <input type="text" 
                                       name="identity" 
                                       id="login-identity"
                                       value="<?= htmlspecialchars($input_identity); ?>" 
                                       placeholder="admin atau arjuna / 3311..." 
                                       required
                                       class="w-full h-12 pl-11 pr-4 bg-white border border-[#ececee] focus:border-[#09090b] rounded-[14px] text-[14px] text-[#18181b] outline-none transition">
                            </div>
                        </div>

                        <div>
                            <label class="block text-[13px] font-medium text-[#18181b] mb-1.5">
                                Kata Sandi
                            </label>
                            <div class="relative flex items-center">
                                <i data-lucide="lock" class="w-4 h-4 text-[#71717a] absolute left-4"></i>
                                <input type="password" 
                                       name="password" 
                                       id="login-password"
                                       placeholder="Masukkan kata sandi..." 
                                       required
                                       class="w-full h-12 pl-11 pr-11 bg-white border border-[#ececee] focus:border-[#09090b] rounded-[14px] text-[14px] text-[#18181b] outline-none transition">
                                <button type="button" 
                                        onclick="togglePasswordVisibility()" 
                                        class="absolute right-3.5 text-[#71717a] hover:text-[#09090b] p-1">
                                    <i data-lucide="eye" id="eye-icon" class="w-4 h-4"></i>
                                </button>
                            </div>
                        </div>

                        <div class="flex items-center justify-between text-[13px] pt-1">
                            <label class="flex items-center gap-2 cursor-pointer text-[#52525b]">
                                <input type="checkbox" name="remember" class="w-4 h-4 rounded border-[#ececee] text-[#09090b] focus:ring-0">
                                <span>Ingat sesi saya</span>
                            </label>
                            <span class="text-[#71717a]">Demo: <code>123456</code></span>
                        </div>

                        <button type="submit" 
                                id="btn-login-submit"
                                class="w-full h-12 bg-[#09090b] hover:bg-[#18181b] text-white rounded-[14px] font-medium text-[14px] flex items-center justify-center gap-2 transition cursor-pointer mt-2">
                            <span>Masuk ke Dashboard</span>
                            <i data-lucide="arrow-right" class="w-4 h-4"></i>
                        </button>
                    </form>

                    <!-- Fast Credential Filler Box for Academic Grading / Demo -->
                    <div class="mt-6 pt-5 border-t border-[#f4f4f5]">
                        <p class="text-[12px] font-semibold text-[#71717a] uppercase tracking-wider mb-2.5">
                            Akun Demo Pengujian Cepat:
                        </p>
                        <div class="grid grid-cols-2 gap-2">
                            <button type="button" 
                                    onclick="fillDemoAccount('admin', '123456')" 
                                    class="p-2.5 rounded-[12px] border border-[#ececee] bg-[#fafafa] hover:bg-[#f4f4f5] text-left transition text-[12px]">
                                <div class="font-semibold text-[#09090b] flex items-center justify-between">
                                    <span>Akun Admin</span>
                                    <span class="text-[10px] bg-black text-white px-1.5 py-0.2 rounded-[4px]">OPD</span>
                                </div>
                                <div class="text-[#71717a]">u: <code>admin</code> / p: <code>123456</code></div>
                            </button>

                            <button type="button" 
                                    onclick="fillDemoAccount('arjuna', '123456')" 
                                    class="p-2.5 rounded-[12px] border border-[#ececee] bg-[#fafafa] hover:bg-[#f4f4f5] text-left transition text-[12px]">
                                <div class="font-semibold text-[#09090b] flex items-center justify-between">
                                    <span>Akun Warga</span>
                                    <span class="text-[10px] bg-[#ececee] text-[#18181b] px-1.5 py-0.2 rounded-[4px]">Kartasura</span>
                                </div>
                                <div class="text-[#71717a]">u: <code>arjuna</code> / p: <code>123456</code></div>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Registration Link Footer -->
                <div class="mt-8 text-center text-[13px] text-[#52525b] border-t border-[#ececee] pt-5">
                    Belum memiliki akun warga? 
                    <a href="register.php" class="font-semibold text-[#09090b] hover:underline">
                        Daftar Baru di Sini
                    </a>
                </div>

            </div>
        </div>

    </div>

    <script>
        lucide.createIcons();

        function togglePasswordVisibility() {
            const passInput = document.getElementById('login-password');
            const eyeIcon = document.getElementById('eye-icon');
            if (passInput.type === 'password') {
                passInput.type = 'text';
                eyeIcon.setAttribute('data-lucide', 'eye-off');
            } else {
                passInput.type = 'password';
                eyeIcon.setAttribute('data-lucide', 'eye');
            }
            lucide.createIcons();
        }

        function fillDemoAccount(username, password) {
            document.getElementById('login-identity').value = username;
            document.getElementById('login-password').value = password;
        }
    </script>
</body>
</html>

