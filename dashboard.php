<?php
session_start();
include 'koneksi.php';
if (!isset($_SESSION['admin'])) header("Location: index.php");

$search = isset($_GET['cari']) ? $_GET['cari'] : "";
$search_param = mysqli_real_escape_string($conn, $search);

$q = "SELECT c.*, 
      (SELECT COUNT(*) FROM core_warna WHERE id_closure = c.id_closure) as total_core,
      (SELECT COUNT(*) FROM core_warna WHERE id_closure = c.id_closure AND tujuan_core != '' AND tujuan_core IS NOT NULL) as core_terisi
      FROM closure c 
      WHERE nama_closure LIKE '%$search_param%' OR kode_closure LIKE '%$search_param%'
      ORDER BY c.id_closure DESC";
$data = mysqli_query($conn, $q);

$total_closure = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM closure"))['total'];
$total_core = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM core_warna"))['total'];
$core_terisi = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM core_warna WHERE tujuan_core != '' AND tujuan_core IS NOT NULL"))['total'];
$core_kosong = $total_core - $core_terisi;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Closure Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        .logo-img {
            width: 100px;
            height: 50px;
            object-fit: fill;
        }
        .gradient-blue {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
        }
        .gradient-secondary {
            background: linear-gradient(60deg, #6c757d, #5a6268);
        }
        .gradient-success {
            background: linear-gradient(60deg, #28a745, #218838);
        }
        .gradient-warning {
            background: linear-gradient(60deg, #ffc107, #e0a800);
        }
        .gradient-danger {
            background: linear-gradient(60deg, #dc3545, #c82333);
        }
        /* Penyesuaian Responsif untuk Card Actions */
        .closure-actions {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease;
        }
        .closure-actions.show {
            max-height: 200px; /* Nilai yang cukup besar untuk menampung aksi di mobile */
        }
        
        /* Mini Map Styles */
        .mini-map {
            height: 180px;
            width: 100%;
            border-radius: 8px;
            margin-bottom: 16px;
            overflow: hidden;
            border: 2px solid #e5e7eb;
        }
        
        .map-placeholder {
            height: 180px;
            width: 100%;
            background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
            border-radius: 8px;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #9ca3af;
            font-size: 14px;
            border: 2px dashed #d1d5db;
        }
        
        @media (max-width: 768px) {
            .closure-actions.show {
                max-height: 200px; /* Pertahankan di mobile */
            }
            .mini-map, .map-placeholder {
                height: 150px;
            }
            /* Penyesuaian Navbar di Mobile */
            #navbar-main-menu {
                display: none;
            }
            #navbar-toggle:checked ~ #navbar-main-menu {
                display: block;
            }
            #navbar-actions {
                /* Untuk memastikan tombol tambah dan logout terlihat */
                display: flex;
                flex-direction: column;
                gap: 8px;
            }
            /* Sembunyikan Logo text di mobile */
            .logo-text-hidden {
                display: none;
            }
        }
    </style>
    
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
</head>
<body class="bg-gray-100">
    <nav class="bg-white shadow-sm sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center gap-4">
                    <div id="logo">
                        <img src="assets/rafateklogo.jpeg" alt="rafatek_logo" class="logo-img">
                    </div>
                    <div>
                        <h1 class="text-xl font-semibold text-gray-800">Closure Management</h1>
                        <p class="text-xs text-gray-600 mt-1 logo-text-hidden md:inline">Sistem Manajemen Closure Fiber Optic</p>
                    </div>
                </div>
                
                <input type="checkbox" id="navbar-toggle" class="hidden">
                <label for="navbar-toggle" class="md:hidden text-gray-500 hover:text-gray-700 cursor-pointer p-2">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"></path></svg>
                </label>
                
                <div class="hidden md:flex md:items-center md:gap-3 absolute md:static top-full left-0 right-0 bg-white p-4 md:p-0 shadow-md md:shadow-none" id="navbar-main-menu">
                    <div class="flex items-center gap-2 px-4 py-2 bg-gray-100 rounded-lg w-full md:w-auto">
                        <div class="w-8 h-8 gradient-blue rounded-full flex items-center justify-center text-white text-sm font-semibold">
                            A
                        </div>
                        <span class="text-sm font-medium text-gray-800">Admin</span>
                    </div>
                    <div id="navbar-actions" class="flex flex-col md:flex-row gap-2 mt-2 md:mt-0">
                        <a href="tambah_closure.php" class="px-5 py-2.5 gradient-blue text-white rounded-lg font-semibold text-sm hover:shadow-lg transition-all hover:-translate-y-0.5 text-center">
                            <span>+</span> Tambah Closure
                        </a>
                        <a href="logout.php" class="px-5 py-2.5 bg-white text-red-500 border-2 border-red-500 rounded-lg font-semibold text-sm hover:bg-red-500 hover:text-white transition-all text-center">
                            Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <div class="gradient-blue py-16 -mb-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-3xl font-semibold text-white mb-2">Dashboard Closure</h2>
            <p class="text-white text-opacity-90">Kelola dan monitor closure fiber optic</p>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-4 gap-4 md:gap-5 mb-8">
            <div class="bg-white rounded-xl md:rounded-2xl shadow-sm hover:shadow-md transition-all hover:-translate-y-1 overflow-hidden">
                <div class="p-4 md:p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-10 h-10 md:w-12 md:h-12 gradient-secondary rounded-full flex items-center justify-center text-xl md:text-2xl text-white shadow-lg">
                                📦
                            </div>
                        </div>
                        <div class="ml-3 md:ml-5 flex-1 text-right">
                            <p class="text-xs text-gray-500 uppercase tracking-wide font-medium mb-1">Total Closure</p>
                            <h4 class="text-xl md:text-3xl font-bold text-gray-800"><?= $total_closure ?></h4>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl md:rounded-2xl shadow-sm hover:shadow-md transition-all hover:-translate-y-1 overflow-hidden">
                <div class="p-4 md:p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-10 h-10 md:w-12 md:h-12 gradient-success rounded-full flex items-center justify-center text-xl md:text-2xl text-white shadow-lg">
                                🔗
                            </div>
                        </div>
                        <div class="ml-3 md:ml-5 flex-1 text-right">
                            <p class="text-xs text-gray-500 uppercase tracking-wide font-medium mb-1">Total Core</p>
                            <h4 class="text-xl md:text-3xl font-bold text-gray-800"><?= $total_core ?></h4>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl md:rounded-2xl shadow-sm hover:shadow-md transition-all hover:-translate-y-1 overflow-hidden">
                <div class="p-4 md:p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-10 h-10 md:w-12 md:h-12 gradient-warning rounded-full flex items-center justify-center text-xl md:text-2xl text-white shadow-lg">
                                ✓
                            </div>
                        </div>
                        <div class="ml-3 md:ml-5 flex-1 text-right">
                            <p class="text-xs text-gray-500 uppercase tracking-wide font-medium mb-1">Core Aktif</p>
                            <h4 class="text-xl md:text-3xl font-bold text-gray-800"><?= $core_terisi ?></h4>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl md:rounded-2xl shadow-sm hover:shadow-md transition-all hover:-translate-y-1 overflow-hidden">
                <div class="p-4 md:p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-10 h-10 md:w-12 md:h-12 gradient-danger rounded-full flex items-center justify-center text-xl md:text-2xl text-white shadow-lg">
                                ○
                            </div>
                        </div>
                        <div class="ml-3 md:ml-5 flex-1 text-right">
                            <p class="text-xs text-gray-500 uppercase tracking-wide font-medium mb-1">Core Kosong</p>
                            <h4 class="text-xl md:text-3xl font-bold text-gray-800"><?= $core_kosong ?></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm p-6 mb-8">
            <form method="get" class="flex flex-col md:flex-row gap-4">
                <input type="text" 
                       name="cari" 
                       placeholder="Cari berdasarkan nama atau kode closure..." 
                       value="<?= htmlspecialchars($search) ?>"
                       class="flex-1 px-5 py-3 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-blue-900 focus:ring-4 focus:ring-blue-100 transition-all text-sm">
                <button type="submit" class="px-6 py-3 gradient-blue text-white rounded-lg font-semibold hover:shadow-lg transition-all text-sm">
                    Cari
                </button>
                <?php if($search): ?>
                    <a href="dashboard.php" class="px-6 py-3 bg-white text-blue-900 border-2 border-blue-900 rounded-lg font-semibold hover:bg-blue-900 hover:text-white transition-all text-center text-sm">
                        Reset
                    </a>
                <?php endif; ?>
            </form>
        </div>

        <?php if(mysqli_num_rows($data) > 0): ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5 mb-8">
            <?php while($d = mysqli_fetch_assoc($data)): 
                $progress = $d['total_core'] > 0 ? ($d['core_terisi'] / $d['total_core']) * 100 : 0;
                // Pastikan progress tidak lebih dari 100%
                $progress = min(100, $progress);
                $progress_color = $progress < 30 ? 'bg-red-500' : ($progress < 70 ? 'bg-yellow-500' : 'bg-green-500');
                
                $has_koordinat = !empty($d['koordinat']);
                $koordinat_parts = $has_koordinat ? explode(',', $d['koordinat']) : [null, null];
                $lat = $has_koordinat && isset($koordinat_parts[0]) ? trim($koordinat_parts[0]) : null;
                $lng = $has_koordinat && isset($koordinat_parts[1]) ? trim($koordinat_parts[1]) : null;
            ?>
            <div class="bg-white rounded-2xl shadow-sm hover:shadow-xl transition-all hover:-translate-y-2 overflow-hidden cursor-pointer flex flex-col" onclick="toggleCard(event, <?= $d['id_closure'] ?>)">
                <div class="gradient-blue p-5 text-white relative">
                    <div class="absolute inset-x-0 bottom-0 h-3 bg-gradient-to-b from-black/10 to-transparent"></div>
                    <h3 class="text-lg font-semibold mb-1.5"><?= htmlspecialchars($d['nama_closure']) ?></h3>
                    <div class="text-xs opacity-90 font-medium tracking-wide"><?= htmlspecialchars($d['kode_closure']) ?></div>
                </div>

                <div class="p-5 flex-1 flex flex-col">
                    <?php if($has_koordinat && $lat && $lng): ?>
                        <div class="mini-map" id="map-<?= $d['id_closure'] ?>" 
                             data-lat="<?= htmlspecialchars($lat) ?>" 
                             data-lng="<?= htmlspecialchars($lng) ?>"></div>
                    <?php else: ?>
                        <div class="map-placeholder">
                            Koordinat belum diset
                        </div>
                    <?php endif; ?>

                    <div class="space-y-3 mb-5 flex-1">
                        <div class="flex items-start gap-2.5 text-sm">
                            <span class="text-gray-600 font-medium min-w-[90px]">Jenis Kabel:</span>
                            <span class="text-gray-800 flex-1 break-words"><?= htmlspecialchars($d['jenis_kabel']) ?></span>
                        </div>
                        <div class="flex items-start gap-2.5 text-sm">
                            <span class="text-gray-600 font-medium min-w-[90px]">Alamat:</span>
                            <span class="text-gray-800 flex-1 break-words line-clamp-2"><?= htmlspecialchars($d['alamat_fisik']) ?></span>
                        </div>
                        <?php if($d['koordinat']): ?>
                        <div class="flex items-start gap-2.5 text-sm">
                            <span class="text-gray-600 font-medium min-w-[90px]">Koordinat:</span>
                            <span class="text-gray-800 flex-1 font-mono text-xs break-words"><?= htmlspecialchars($d['koordinat']) ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if($d['jarak_tujuan']): ?>
                        <div class="flex items-start gap-2.5 text-sm">
                            <span class="text-gray-600 font-medium min-w-[90px]">Jarak:</span>
                            <span class="text-gray-800 flex-1"><?= htmlspecialchars($d['jarak_tujuan']) ?> km</span>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="bg-gray-100 rounded-lg p-3 mb-4 border border-gray-200">
                        <div class="text-sm text-gray-700 font-medium mb-2 text-center">
                            <?= $d['core_terisi'] ?> / <?= $d['total_core'] ?> Core Terisi 
                        </div>
                    </div>

                    <div class="closure-actions border-t border-gray-200 pt-4 mt-auto" id="actions-<?= $d['id_closure'] ?>">
                        <div class="flex flex-col sm:flex-row gap-2.5 justify-center">
                            <a href="detail_closure.php?id=<?= $d['id_closure'] ?>" 
                               class="flex items-center justify-center gap-1 px-3 py-2 text-gray-600 border border-gray-300 rounded-lg hover:bg-blue-50 hover:border-blue-700 hover:text-blue-700 transition-all flex-1 text-xs font-medium"
                               onclick="event.stopPropagation()" 
                               title="Lihat Detail">
                                <span>Detail</span>
                            </a>
                            <a href="edit_closure.php?id=<?= $d['id_closure'] ?>" 
                               class="flex items-center justify-center gap-1 px-3 py-2 text-gray-600 border border-gray-300 rounded-lg hover:bg-yellow-50 hover:border-yellow-700 hover:text-yellow-700 transition-all flex-1 text-xs font-medium"
                               onclick="event.stopPropagation()" 
                               title="Edit Data">
                                <span>Edit</span>
                            </a>
                            <a href="hapus_closure.php?id=<?= $d['id_closure'] ?>" 
                               onclick="event.stopPropagation(); return confirm('⚠️ Hapus closure <?= htmlspecialchars($d['nama_closure']) ?>?\n\nSemua data core juga akan terhapus!')" 
                               class="flex items-center justify-center gap-1 px-3 py-2 text-white bg-red-500 border border-red-500 rounded-lg hover:bg-red-600 hover:border-red-600 transition-all flex-1 text-xs font-medium"
                               title="Hapus">
                                <span>Hapus</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
        <?php else: ?>
        <div class="bg-white rounded-2xl shadow-sm p-10 md:p-20 text-center">
            <div class="text-7xl md:text-8xl opacity-30 mb-5">🔍</div>
            <h3 class="text-xl text-gray-800 font-semibold mb-2">
                <?= $search ? "Hasil Pencarian Tidak Ditemukan" : "Belum Ada Data Closure" ?>
            </h3>
            <p class="text-gray-600 mb-6">
                <?= $search ? "Coba kata kunci lain atau reset pencarian" : "Mulai tambahkan closure baru untuk memulai" ?>
            </p>
            <?php if(!$search): ?>
                <a href="tambah_closure.php" class="inline-block px-6 py-3 gradient-blue text-white rounded-lg font-semibold hover:shadow-lg transition-all">
                    + Tambah Closure Pertama
                </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <script>
        // Initialize all mini maps
        document.addEventListener('DOMContentLoaded', function() {
            const mapElements = document.querySelectorAll('.mini-map');
            
            mapElements.forEach(function(mapEl) {
                const lat = parseFloat(mapEl.dataset.lat);
                const lng = parseFloat(mapEl.dataset.lng);
                
                if (!isNaN(lat) && !isNaN(lng)) {
                    // Pastikan peta hanya diinisialisasi sekali
                    if (mapEl._leaflet_id) {
                        mapEl._leaflet_id = null; // Reset jika ada id lama
                    }
                    
                    const miniMap = L.map(mapEl.id, {
                        center: [lat, lng],
                        zoom: 15,
                        zoomControl: false,
                        dragging: false,
                        scrollWheelZoom: false,
                        doubleClickZoom: false,
                        touchZoom: false,
                        boxZoom: false,
                        keyboard: false
                    });
                    
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '© OpenStreetMap'
                    }).addTo(miniMap);
                    
                    L.marker([lat, lng]).addTo(miniMap);
                    
                    // Enable interaction on click
                    mapEl.addEventListener('click', function(e) {
                        e.stopPropagation();
                        // Memberikan sedikit indikasi bahwa peta dapat digeser/diperbesar saat di-klik
                        miniMap.dragging.enable();
                        miniMap.scrollWheelZoom.enable();
                        miniMap.doubleClickZoom.enable();
                        miniMap.touchZoom.enable();
                        // Opsional: set view to center again if needed on interaction
                        // miniMap.setView([lat, lng], 15);
                    });
                }
            });
        });
    
        function toggleCard(event, id) {
            const card = event.currentTarget;
            const actions = document.getElementById('actions-' + id);
            const allCards = document.querySelectorAll('[onclick^="toggleCard"]');
            const allActions = document.querySelectorAll('.closure-actions');
            
            // Close all other cards and remove shadow
            allCards.forEach(c => {
                if (c !== card) {
                    c.classList.remove('shadow-2xl');
                }
            });
            
            allActions.forEach(a => {
                if (a !== actions) {
                    a.classList.remove('show');
                }
            });
            
            // Toggle current card's shadow and actions
            card.classList.toggle('shadow-2xl');
            actions.classList.toggle('show');
        }

        // Close all cards when clicking outside
        document.addEventListener('click', function(event) {
            if (!event.target.closest('[onclick^="toggleCard"]') && !event.target.closest('#navbar-main-menu')) {
                const allCards = document.querySelectorAll('[onclick^="toggleCard"]');
                const allActions = document.querySelectorAll('.closure-actions');
                
                allCards.forEach(card => {
                    card.classList.remove('shadow-2xl');
                });
                
                allActions.forEach(actions => {
                    actions.classList.remove('show');
                });
                
                // Close mobile menu if open
                const toggle = document.getElementById('navbar-toggle');
                if (toggle) {
                    toggle.checked = false;
                }
            }
        });
        
        // Mobile Navbar Toggle Script
        const navbarToggle = document.getElementById('navbar-toggle');
        const navbarMenu = document.getElementById('navbar-main-menu');

        if(navbarToggle && navbarMenu) {
            navbarToggle.addEventListener('change', function() {
                if(this.checked) {
                    navbarMenu.classList.remove('hidden');
                } else {
                    navbarMenu.classList.add('hidden');
                }
            });
        }
    </script>
</body>
</html>