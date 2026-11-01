<?php
/**
 * ==============================================================================
 * PROSES KELUAR SESI (LOGOUT)
 * ePengaduan Kabupaten Sukoharjo - UTS Pemrograman Web 2
 * ==============================================================================
 */

require_once __DIR__ . '/config/auth.php';

// Hapus seluruh variabel sesi
$_SESSION = [];

// Hapus cookie sesi jika ada
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Hancurkan sesi
session_destroy();

// Mulai sesi baru untuk flash message
session_start();
set_flash('success', 'Anda telah berhasil keluar dari sistem ePengaduan Sukoharjo.');
header('Location: login.php');
exit;

