/**
 * ==============================================================================
 * OTOMASI SCREENSHOT FITUR UTS PEMROGRAMAN WEB 2
 * Proyek: ePengaduan Kabupaten Sukoharjo (Tema 13)
 * Menggunakan: Puppeteer-Core (Deteksi Lokal Chrome / Edge di Windows)
 * ==============================================================================
 */

const fs = require('fs');
const path = require('path');

// Dukungan resolusi puppeteer-core dari lokal atau modul terinstal
let puppeteer;
try {
    puppeteer = require('puppeteer-core');
} catch (e) {
    try {
        puppeteer = require(path.join(__dirname, 'node_modules', 'puppeteer-core'));
    } catch (e2) {
        puppeteer = require('D:/KULIAH/SEMESTER 3/WEB 2/TUGAS 2/AplikasiMahasiswa/node_modules/puppeteer-core');
    }
}

// Daftar kemungkinan Base URL peladen lokal XAMPP
const POSSIBLE_BASE_URLS = [
    'http://localhost:8080/Pengaduan_Sukoharjo',
    'http://localhost/Pengaduan_Sukoharjo',
    'http://localhost/UTS/Pengaduan_Sukoharjo',
    'http://localhost:8000/Pengaduan_Sukoharjo',
    'http://localhost:7000/Pengaduan_Sukoharjo'
];

// Lokasi biner browser Chrome / Microsoft Edge di Windows
const CHROME_PATHS = [
    'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
    'C:\\Program Files (x86)\\Google\\Chrome\\Application\\chrome.exe',
    'C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe',
    'C:\\Program Files\\Microsoft\\Edge\\Application\\msedge.exe'
];

const OUTPUT_DIR = path.join(__dirname, 'screenshots');

function getBrowserPath() {
    for (const p of CHROME_PATHS) {
        if (fs.existsSync(p)) return p;
    }
    throw new Error('Tidak menemukan browser Google Chrome atau Microsoft Edge lokal.');
}

async function getAvailableBaseUrl() {
    for (const url of POSSIBLE_BASE_URLS) {
        try {
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 2000);
            const res = await fetch(`${url}/login.php`, { method: 'GET', signal: controller.signal });
            clearTimeout(timeoutId);
            if (res.status === 200 || res.status === 302) {
                return url;
            }
        } catch (e) {
            // Coba URL berikutnya
        }
    }
    return POSSIBLE_BASE_URLS[0]; // fallback
}

const sleep = (ms) => new Promise(resolve => setTimeout(resolve, ms));

async function run() {
    console.log('================================================================');
    console.log('🚀 MEMULAI OTOMASI SCREENSHOT: ePENGADUAN KABUPATEN SUKOHARJO');
    console.log('================================================================');

    if (!fs.existsSync(OUTPUT_DIR)) {
        fs.mkdirSync(OUTPUT_DIR, { recursive: true });
    }

    const browserPath = getBrowserPath();
    const baseUrl = await getAvailableBaseUrl();
    console.log(`🌐 Base URL Terdeteksi : ${baseUrl}`);
    console.log(`🖥️  Browser Binary Path : ${browserPath}`);
    console.log(`📁 Folder Output       : ${OUTPUT_DIR}\n`);

    const browser = await puppeteer.launch({
        executablePath: browserPath,
        headless: 'new',
        args: [
            '--no-sandbox',
            '--disable-setuid-sandbox',
            '--disable-web-security',
            '--window-size=1366,768'
        ]
    });

    const page = await browser.newPage();
    await page.setViewport({ width: 1366, height: 768, deviceScaleFactor: 1.5 });

    try {
        // -------------------------------------------------------------
        // 1. Screenshot Halaman Login (Publik)
        // -------------------------------------------------------------
        console.log('📸 [1/10] Menangkap: 01_halaman_login.png ...');
        await page.goto(`${baseUrl}/login.php`, { waitUntil: 'domcontentloaded' });
        await sleep(1500);
        await page.screenshot({
            path: path.join(OUTPUT_DIR, '01_halaman_login.png'),
            fullPage: true
        });

        // -------------------------------------------------------------
        // 2. Screenshot Halaman Register (Publik)
        // -------------------------------------------------------------
        console.log('📸 [2/10] Menangkap: 02_halaman_register.png ...');
        await page.goto(`${baseUrl}/register.php`, { waitUntil: 'domcontentloaded' });
        await sleep(1500);
        await page.screenshot({
            path: path.join(OUTPUT_DIR, '02_halaman_register.png'),
            fullPage: true
        });

        // -------------------------------------------------------------
        // Autentikasi Login sebagai Administrator (Diskominfo Sukoharjo)
        // -------------------------------------------------------------
        console.log('🔑 Melakukan Login Otomatis sebagai Admin Pemkab (admin / 123456)...');
        await page.goto(`${baseUrl}/login.php`, { waitUntil: 'domcontentloaded' });
        await page.type('#login-identity', 'admin');
        await page.type('#login-password', '123456');
        
        await Promise.all([
            page.waitForNavigation({ waitUntil: 'domcontentloaded', timeout: 10000 }).catch(() => {}),
            page.click('#btn-login-submit')
        ]);
        await sleep(1500);
        console.log('   ✓ Berhasil Masuk Sesi Administrator.');

        // -------------------------------------------------------------
        // 3. Screenshot Dashboard & Peta GIS (index.php)
        // -------------------------------------------------------------
        console.log('📸 [3/10] Menangkap: 03_dashboard_statistik.png ...');
        await page.goto(`${baseUrl}/index.php`, { waitUntil: 'domcontentloaded' });
        await sleep(2500); // Beri waktu Leaflet tile map dan marker termuat sempurna
        await page.screenshot({
            path: path.join(OUTPUT_DIR, '03_dashboard_statistik.png'),
            fullPage: true
        });

        // -------------------------------------------------------------
        // 4. Screenshot Formulir Tambah Pengaduan (tambah_pengaduan.php)
        // -------------------------------------------------------------
        console.log('📸 [4/10] Menangkap: 04_form_tambah_pengaduan.png ...');
        await page.goto(`${baseUrl}/tambah_pengaduan.php`, { waitUntil: 'domcontentloaded' });
        await sleep(2500); // Leaflet pin picker map
        await page.screenshot({
            path: path.join(OUTPUT_DIR, '04_form_tambah_pengaduan.png'),
            fullPage: true
        });

        // -------------------------------------------------------------
        // 5. Screenshot Tabel Data Pengaduan (pengaduan.php)
        // -------------------------------------------------------------
        console.log('📸 [5/10] Menangkap: 05_tabel_data_pengaduan.png ...');
        await page.goto(`${baseUrl}/pengaduan.php`, { waitUntil: 'domcontentloaded' });
        await sleep(1500);
        await page.screenshot({
            path: path.join(OUTPUT_DIR, '05_tabel_data_pengaduan.png'),
            fullPage: true
        });

        // -------------------------------------------------------------
        // 6. Screenshot Detail & Linimasa Pengaduan (detail_pengaduan.php?id=1)
        // -------------------------------------------------------------
        console.log('📸 [6/10] Menangkap: 06_detail_dan_timeline_pengaduan.png ...');
        await page.goto(`${baseUrl}/detail_pengaduan.php?id=1`, { waitUntil: 'domcontentloaded' });
        await sleep(2500); // Before-after cards & mini map
        await page.screenshot({
            path: path.join(OUTPUT_DIR, '06_detail_dan_timeline_pengaduan.png'),
            fullPage: true
        });

        // -------------------------------------------------------------
        // 7. Screenshot Form Tindak Lanjut Petugas (tindak_lanjut.php?id=1)
        // -------------------------------------------------------------
        console.log('📸 [7/10] Menangkap: 07_form_tindak_lanjut.png ...');
        await page.goto(`${baseUrl}/tindak_lanjut.php?id=1`, { waitUntil: 'domcontentloaded' });
        await sleep(1500);
        await page.screenshot({
            path: path.join(OUTPUT_DIR, '07_form_tindak_lanjut.png'),
            fullPage: true
        });

        // -------------------------------------------------------------
        // 8. Screenshot Kelola Master Kategori (kategori.php)
        // -------------------------------------------------------------
        console.log('📸 [8/10] Menangkap: 08_kelola_kategori.png ...');
        await page.goto(`${baseUrl}/kategori.php`, { waitUntil: 'domcontentloaded' });
        await sleep(1500);
        await page.screenshot({
            path: path.join(OUTPUT_DIR, '08_kelola_kategori.png'),
            fullPage: true
        });

        // -------------------------------------------------------------
        // 9. Screenshot Kelola Master Masyarakat (masyarakat.php)
        // -------------------------------------------------------------
        console.log('📸 [9/10] Menangkap: 09_kelola_masyarakat.png ...');
        await page.goto(`${baseUrl}/masyarakat.php`, { waitUntil: 'domcontentloaded' });
        await sleep(1500);
        await page.screenshot({
            path: path.join(OUTPUT_DIR, '09_kelola_masyarakat.png'),
            fullPage: true
        });

        // -------------------------------------------------------------
        // 10. Screenshot Rekapitulasi & Cetak Laporan (laporan.php)
        // -------------------------------------------------------------
        console.log('📸 [10/10] Menangkap: 10_laporan_dan_cetak.png ...');
        await page.goto(`${baseUrl}/laporan.php`, { waitUntil: 'domcontentloaded' });
        await sleep(1500);
        await page.screenshot({
            path: path.join(OUTPUT_DIR, '10_laporan_dan_cetak.png'),
            fullPage: true
        });

        console.log('\n================================================================');
        console.log('🎉 SELURUH SCREENSHOT BERHASIL DIAMBIL LENGKAP!');
        console.log('================================================================');
        const files = fs.readdirSync(OUTPUT_DIR).filter(f => f.endsWith('.png'));
        files.sort().forEach((f, i) => {
            const stat = fs.statSync(path.join(OUTPUT_DIR, f));
            console.log(`   ${i + 1}. ${f} (${(stat.size / 1024).toFixed(1)} KB)`);
        });

    } catch (err) {
        console.error('\n❌ Terjadi kesalahan saat pengambilan screenshot:', err);
        process.exit(1);
    } finally {
        await browser.close();
    }
}

run();
