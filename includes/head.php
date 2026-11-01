<?php
/**
 * ==============================================================================
 * HEADER & ASSET LOADER (AWESOMIC DESIGN SYSTEM)
 * ePengaduan Kabupaten Sukoharjo - UTS Pemrograman Web 2
 * ==============================================================================
 */
if (!isset($page_title)) {
    $page_title = 'Sistem Pengaduan Masyarakat';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Sistem Informasi Pengaduan dan Aspirasi Pelayanan Publik Pemerintah Kabupaten Sukoharjo">
    <meta name="theme-color" content="#09090b">
    <title><?= htmlspecialchars($page_title); ?> — ePengaduan Kab. Sukoharjo</title>
    
    <!-- Google Fonts: DM Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,100..1000;1,9..40,100..1000&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"DM Sans"', 'sans-serif'],
                    },
                    colors: {
                        ember: '#ff5a00',
                        obsidian: '#09090b',
                        graphite: '#18181b',
                        slatecard: '#27272a',
                        steel: '#52525b',
                        fog: '#71717a',
                        cloud: '#ececee',
                        paper: '#f4f4f5'
                    }
                }
            }
        }
    </script>
    
    <!-- Lucide Icons CDN -->
    <script src="https://unpkg.com/lucide@latest"></script>

    <!-- Leaflet.js (GIS Map) CDN -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

    <!-- Custom Baseline CSS (Strict Awesomic Elevation & Print Rules) -->
    <style>
        /* Smooth anti-aliased font rendering */
        body {
            font-family: 'DM Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        /* Hilangkan bayangan bawaan untuk mematuhi aturan Awesomic border-only */
        .shadow-none, .card-awesomic {
            box-shadow: none !important;
        }

        /* Custom scrollbar elegan */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        ::-webkit-scrollbar-track {
            background: #f4f4f5;
        }
        ::-webkit-scrollbar-thumb {
            background: #d4d4d8;
            border-radius: 9999px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #a1a1aa;
        }

        /* Leaflet Map Custom Styling */
        .leaflet-popup-content-wrapper {
            border-radius: 18px !important;
            padding: 4px !important;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1) !important;
            border: 1px solid #ececee !important;
            font-family: 'DM Sans', sans-serif !important;
        }
        .leaflet-popup-tip {
            background: #ffffff !important;
        }

        /* Aturan Cetak Laporan Formal */
        @media print {
            aside, header, nav, .no-print, #quick-tracking-bar, .action-buttons {
                display: none !important;
            }
            body {
                background: #ffffff !important;
                color: #000000 !important;
            }
            .main-content {
                margin-left: 0 !important;
                padding: 0 !important;
                max-width: 100% !important;
            }
            .print-only {
                display: block !important;
            }
            .card-awesomic {
                border: 1px solid #000000 !important;
                border-radius: 8px !important;
            }
        }
    </style>
</head>
<body class="bg-[#f4f4f5] text-[#18181b] min-h-screen antialiased flex flex-col">

