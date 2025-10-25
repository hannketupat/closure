<?php
session_start();
include 'koneksi.php';
if (!isset($_SESSION['admin'])) header("Location: index.php");

$search = isset($_GET['cari']) ? $_GET['cari'] : "";
$search_param = mysqli_real_escape_string($conn, $search);

// Pagination settings
$items_per_page = 15; // 9 items per page (3x3 grid)
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$current_page = max(1, $current_page); // Ensure page is at least 1
$offset = ($current_page - 1) * $items_per_page;

// Get total count for pagination
$count_query = "SELECT COUNT(*) as total FROM closure c 
                WHERE nama_closure LIKE '%$search_param%' OR kode_closure LIKE '%$search_param%'";
$count_result = mysqli_query($conn, $count_query);
$total_items = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_items / $items_per_page);

// Main query with LIMIT for pagination
$q = "SELECT c.*, 
      (SELECT COUNT(*) FROM core_warna WHERE id_closure = c.id_closure) as total_core,
      (SELECT COUNT(*) FROM core_warna WHERE id_closure = c.id_closure AND tujuan_core != '' AND tujuan_core IS NOT NULL) as core_terisi
      FROM closure c 
      WHERE nama_closure LIKE '%$search_param%' OR kode_closure LIKE '%$search_param%'
      ORDER BY c.id_closure DESC
      LIMIT $offset, $items_per_page";
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

        .closure-actions {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease;
        }

        .closure-actions.show {
            max-height: 200px;
        }

        /* Mini Map Styles dengan hover effect */
        .mini-map {
            height: 180px;
            width: 100%;
            border-radius: 8px;
            margin-bottom: 16px;
            overflow: hidden;
            border: 2px solid #e5e7eb;
            position: relative;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .mini-map:hover {
            border-color: #3b82f6;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }

        .mini-map::after {
            content: 'Buka di Google Maps';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(0, 0, 0, 0.75);
            color: white;
            padding: 8px;
            text-align: center;
            font-size: 12px;
            font-weight: 600;
            opacity: 0;
            transition: opacity 0.3s ease;
            pointer-events: none;
            z-index: 1000;
        }

        .mini-map:hover::after {
            opacity: 1;
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

        /* Custom class for mobile menu to control visibility via JS */
        .mobile-menu-hide {
            display: none;
        }

        @media (min-width: 768px) {
            .mobile-menu-hide {
                display: flex !important;
                /* Always show on desktop */
            }
        }

        /* Pagination Styles */
        .pagination-btn {
            min-width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .pagination-btn:hover:not(.active):not(:disabled) {
            background-color: #f3f4f6;
        }

        .pagination-btn.active {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
        }

        .pagination-btn:disabled {
            opacity: 0.4;
            cursor: not-allowed;
        }
    </style>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
</head>

<body class="bg-gray-100">
    <nav class="bg-white shadow-sm sticky top-0 z-[9999]">
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

                <button id="navbar-toggle-btn" class="md:hidden text-gray-500 hover:text-gray-700 cursor-pointer p-2">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"></path>
                    </svg>
                </button>

                <div class="mobile-menu-hide md:flex md:items-center md:gap-3 absolute md:static top-full left-0 right-0 bg-white p-4 md:p-0 shadow-md md:shadow-none transition-all duration-300 ease-in-out" id="navbar-main-menu">

                    <div class="relative w-full md:w-auto" id="profile-dropdown-container">
                        <button id="profile-dropdown-btn" class="flex items-center gap-2 px-4 py-2 bg-gray-100 rounded-lg w-full cursor-pointer hover:bg-gray-200 transition-colors">
                            <div class="w-8 h-8 gradient-blue rounded-full flex items-center justify-center text-white text-sm font-semibold">
                                A
                            </div>
                            <span class="text-sm font-medium text-gray-800">Admin</span>
                            <svg class="w-4 h-4 ml-auto transition-transform duration-200" id="profile-arrow" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>

                        <div class="profile-dropdown-menu absolute md:w-48 right-0 mt-2 py-2 w-full bg-white border border-gray-200 rounded-lg shadow-xl hidden z-50" id="profile-dropdown-menu">
                            <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 w-full text-left">
                                Profile (Placeholder)
                            </a>
                            <a href="logout.php" class="block px-4 py-2 text-sm text-red-500 hover:bg-red-50 hover:text-red-700 border-t mt-1 pt-2">
                                Logout
                            </a>
                        </div>
                    </div>

                    <div id="navbar-actions" class="hidden md:flex flex-col md:flex-row gap-2 mt-2 md:mt-0">
                        <a href="tambah_closure.php" class="px-5 py-2.5 gradient-blue text-white rounded-lg font-semibold text-sm hover:shadow-lg transition-all hover:-translate-y-0.5 text-center">
                            <span>+</span> Tambah Closure
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
        <div class="md:hidden mb-6 mt-4"> <a href="tambah_closure.php" class="block w-full px-5 py-3 gradient-blue text-white rounded-lg font-semibold text-sm hover:shadow-lg transition-all text-center">
                <span>+</span> Tambah Closure Baru
            </a>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
            <!-- Total Closure -->
            <div class="bg-white border border-gray-100 rounded-2xl shadow-sm hover:shadow-md transition-all hover:-translate-y-1 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Total Closure</p>
                        <h3 class="text-3xl font-semibold text-gray-900 mt-1"><?= $total_closure ?></h3>
                    </div>
                    <div class="w-12 h-12 flex items-center justify-center rounded-xl bg-indigo-50 text-indigo-600">
                        <!-- Icon Folder -->
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 7a2 2 0 012-2h5l2 2h7a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V7z" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Total Core -->
            <div class="bg-white border border-gray-100 rounded-2xl shadow-sm hover:shadow-md transition-all hover:-translate-y-1 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Total Core</p>
                        <h3 class="text-3xl font-semibold text-gray-900 mt-1"><?= $total_core ?></h3>
                    </div>
                    <div class="w-12 h-12 flex items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                        <!-- Icon Network -->
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2m-4-8a9 9 0 11-9 9" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Core Aktif -->
            <div class="bg-white border border-gray-100 rounded-2xl shadow-sm hover:shadow-md transition-all hover:-translate-y-1 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Core Aktif</p>
                        <h3 class="text-3xl font-semibold text-gray-900 mt-1"><?= $core_terisi ?></h3>
                    </div>
                    <div class="w-12 h-12 flex items-center justify-center rounded-xl bg-yellow-50 text-yellow-600">
                        <!-- Icon Lightning -->
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Core Kosong -->
            <div class="bg-white border border-gray-100 rounded-2xl shadow-sm hover:shadow-md transition-all hover:-translate-y-1 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Core Kosong</p>
                        <h3 class="text-3xl font-semibold text-gray-900 mt-1"><?= $core_kosong ?></h3>
                    </div>
                    <div class="w-12 h-12 flex items-center justify-center rounded-xl bg-rose-50 text-rose-600">
                        <!-- Icon Circle -->
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <circle cx="12" cy="12" r="9" />
                        </svg>
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
                <?php if ($search): ?>
                    <a href="dashboard.php" class="px-6 py-3 bg-white text-blue-900 border-2 border-blue-900 rounded-lg font-semibold hover:bg-blue-900 hover:text-white transition-all text-center text-sm">
                        Reset
                    </a>
                <?php endif; ?>
            </form>
        </div>

        <?php if (mysqli_num_rows($data) > 0): ?>
            <!-- Pagination Info -->
            <div class="mb-4 text-sm text-gray-600">
                Menampilkan <?= $offset + 1 ?> - <?= min($offset + $items_per_page, $total_items) ?> dari <?= $total_items ?> closure
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5 mb-8">
                <?php while ($d = mysqli_fetch_assoc($data)):
                    $progress = $d['total_core'] > 0 ? ($d['core_terisi'] / $d['total_core']) * 100 : 0;
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
                            <?php if ($has_koordinat && $lat && $lng): ?>
                                <div class="mini-map" id="map-<?= $d['id_closure'] ?>"
                                    data-lat="<?= htmlspecialchars($lat) ?>"
                                    data-lng="<?= htmlspecialchars($lng) ?>"
                                    onclick="openGoogleMaps(event, '<?= htmlspecialchars($lat) ?>', '<?= htmlspecialchars($lng) ?>')"></div>
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
                                <?php if ($d['koordinat']): ?>
                                    <div class="flex items-start gap-2.5 text-sm">
                                        <span class="text-gray-600 font-medium min-w-[90px]">Koordinat:</span>
                                        <span class="text-gray-800 flex-1 font-mono text-xs break-words"><?= htmlspecialchars($d['koordinat']) ?></span>
                                    </div>
                                <?php endif; ?>
                                <?php if ($d['jarak_tujuan']): ?>
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

                            <?php if (!empty($d['updated_at'])): ?>
                                <div class="text-xs text-gray-500 mt-2 text-right italic">
                                    Last Update: <?= date('d M Y, H:i', strtotime($d['updated_at'])) ?>
                                </div>
                            <?php endif; ?>

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

            <!-- Pagination Controls -->
            <?php if ($total_pages > 1): ?>
                <div class="bg-white rounded-2xl shadow-sm p-6 mb-8">
                    <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                        <!-- Page Info (Mobile) -->
                        <div class="text-sm text-gray-600 sm:hidden">
                            Halaman <?= $current_page ?> dari <?= $total_pages ?>
                        </div>

                        <!-- Pagination Buttons -->
                        <div class="flex items-center gap-2 flex-wrap justify-center">
                            <!-- First Page -->
                            <a href="?page=1<?= $search ? '&cari=' . urlencode($search) : '' ?>" 
                               class="pagination-btn border border-gray-300 text-gray-700 <?= $current_page == 1 ? 'pointer-events-none' : '' ?>"
                               <?= $current_page == 1 ? 'disabled' : '' ?>>
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"></path>
                                </svg>
                            </a>

                            <!-- Previous Page -->
                            <a href="?page=<?= max(1, $current_page - 1) ?><?= $search ? '&cari=' . urlencode($search) : '' ?>" 
                               class="pagination-btn border border-gray-300 text-gray-700 <?= $current_page == 1 ? 'pointer-events-none' : '' ?>"
                               <?= $current_page == 1 ? 'disabled' : '' ?>>
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                                </svg>
                            </a>

                            <!-- Page Numbers -->
                            <?php
                            $start_page = max(1, $current_page - 2);
                            $end_page = min($total_pages, $current_page + 2);

                            if ($start_page > 1): ?>
                                <a href="?page=1<?= $search ? '&cari=' . urlencode($search) : '' ?>" 
                                   class="pagination-btn border border-gray-300 text-gray-700">1</a>
                                <?php if ($start_page > 2): ?>
                                    <span class="text-gray-400">...</span>
                                <?php endif; ?>
                            <?php endif; ?>

                            <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                                <a href="?page=<?= $i ?><?= $search ? '&cari=' . urlencode($search) : '' ?>" 
                                   class="pagination-btn border border-gray-300 text-gray-700 <?= $i == $current_page ? 'active' : '' ?>">
                                    <?= $i ?>
                                </a>
                            <?php endfor; ?>

                            <?php if ($end_page < $total_pages): ?>
                                <?php if ($end_page < $total_pages - 1): ?>
                                    <span class="text-gray-400">...</span>
                                <?php endif; ?>
                                <a href="?page=<?= $total_pages ?><?= $search ? '&cari=' . urlencode($search) : '' ?>" 
                                   class="pagination-btn border border-gray-300 text-gray-700"><?= $total_pages ?></a>
                            <?php endif; ?>

                            <!-- Next Page -->
                            <a href="?page=<?= min($total_pages, $current_page + 1) ?><?= $search ? '&cari=' . urlencode($search) : '' ?>" 
                               class="pagination-btn border border-gray-300 text-gray-700 <?= $current_page == $total_pages ? 'pointer-events-none' : '' ?>"
                               <?= $current_page == $total_pages ? 'disabled' : '' ?>>
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </a>

                            <!-- Last Page -->
                            <a href="?page=<?= $total_pages ?><?= $search ? '&cari=' . urlencode($search) : '' ?>" 
                               class="pagination-btn border border-gray-300 text-gray-700 <?= $current_page == $total_pages ? 'pointer-events-none' : '' ?>"
                               <?= $current_page == $total_pages ? 'disabled' : '' ?>>
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"></path>
                                </svg>
                            </a>
                        </div>

                        <!-- Page Info (Desktop) -->
                        <div class="hidden sm:block text-sm text-gray-600">
                            Halaman <?= $current_page ?> dari <?= $total_pages ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="bg-white rounded-2xl shadow-sm p-10 md:p-20 text-center">
                <div class="text-7xl md:text-8xl opacity-30 mb-5">🔍</div>
                <h3 class="text-xl text-gray-800 font-semibold mb-2">
                    <?= $search ? "Hasil Pencarian Tidak Ditemukan" : "Belum Ada Data Closure" ?>
                </h3>
                <p class="text-gray-600 mb-6">
                    <?= $search ? "Coba kata kunci lain atau reset pencarian" : "Mulai tambahkan closure baru untuk memulai" ?>
                </p>
                <?php if (!$search): ?>
                    <a href="tambah_closure.php" class="inline-block px-6 py-3 gradient-blue text-white rounded-lg font-semibold hover:shadow-lg transition-all">
                        + Tambah Closure Pertama
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Function to open Google Maps with the correct URL format
        function openGoogleMaps(event, lat, lng) {
            event.stopPropagation();
            // Corrected Google Maps URL format for coordinates
            const googleMapsUrl = `http://maps.google.com/?q=${lat},${lng}`;
            window.open(googleMapsUrl, '_blank');
        }

        // Function to toggle card actions
        function toggleCard(event, id) {
            const card = event.currentTarget;
            const actions = document.getElementById('actions-' + id);
            const allCards = document.querySelectorAll('[onclick^="toggleCard"]');
            const allActions = document.querySelectorAll('.closure-actions');

            // Close all other card actions and remove shadow
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

            // Toggle current card action and shadow
            card.classList.toggle('shadow-2xl');
            actions.classList.toggle('show');
        }

        // --- Navbar and Dropdown Logic ---

        const navbarToggleBtn = document.getElementById('navbar-toggle-btn');
        const navbarMenu = document.getElementById('navbar-main-menu');
        const profileDropdownBtn = document.getElementById('profile-dropdown-btn');
        const profileDropdownMenu = document.getElementById('profile-dropdown-menu');
        const profileDropdownContainer = document.getElementById('profile-dropdown-container');
        const profileArrow = document.getElementById('profile-arrow');


        // Function to show/hide the main navbar (mobile menu)
        function toggleMobileMenu() {
            if (navbarMenu.classList.contains('mobile-menu-hide')) {
                navbarMenu.classList.remove('mobile-menu-hide');
            } else {
                navbarMenu.classList.add('mobile-menu-hide');
                // Ensure profile dropdown is closed when closing the main menu
                closeProfileDropdown();
            }
        }

        // Function to show/hide the profile dropdown
        function toggleProfileDropdown() {
            // For mobile, the dropdown links are immediately visible inside the menu, so no explicit toggle needed.
            // This toggle is primarily for desktop (md breakpoint) where the menu is initially hidden.
            if (window.innerWidth >= 768) {
                if (profileDropdownMenu.classList.contains('hidden')) {
                    profileDropdownMenu.classList.remove('hidden');
                    profileArrow.classList.add('rotate-180');
                } else {
                    profileDropdownMenu.classList.add('hidden');
                    profileArrow.classList.remove('rotate-180');
                }
            } else {
                // On mobile, the dropdown button doesn't hide the links, it just acts as a spacer.
                // The links are already visible inside the main menu.
            }
        }

        // Function to close the profile dropdown (used by global click listener)
        function closeProfileDropdown() {
            if (profileDropdownMenu && !profileDropdownMenu.classList.contains('hidden')) {
                profileDropdownMenu.classList.add('hidden');
                profileArrow.classList.remove('rotate-180');
            }
        }

        // Attach event listeners
        if (navbarToggleBtn) {
            navbarToggleBtn.addEventListener('click', toggleMobileMenu);
        }

        if (profileDropdownBtn) {
            profileDropdownBtn.addEventListener('click', function(event) {
                event.stopPropagation(); // Prevent global click listener from immediately closing it
                toggleProfileDropdown();
            });
        }

        // Global click listener to close actions, mobile menu, and desktop profile dropdown
        document.addEventListener('click', function(event) {
            const isClickInsideCard = event.target.closest('[onclick^="toggleCard"]');

            // --- Card Actions Logic ---
            if (!isClickInsideCard) {
                document.querySelectorAll('[onclick^="toggleCard"]').forEach(card => {
                    card.classList.remove('shadow-2xl');
                });
                document.querySelectorAll('.closure-actions').forEach(actions => {
                    actions.classList.remove('show');
                });
            }

            // --- Navbar/Dropdown Logic (Close if clicked outside) ---
            const isClickInsideNavbar = event.target.closest('#navbar-main-menu');
            const isClickOnToggle = event.target.closest('#navbar-toggle-btn');
            const isClickInsideProfile = event.target.closest('#profile-dropdown-container');

            // 1. Close Mobile Menu if clicked outside
            if (window.innerWidth < 768 && !isClickInsideNavbar && !isClickOnToggle && !navbarMenu.classList.contains('mobile-menu-hide')) {
                navbarMenu.classList.add('mobile-menu-hide');
            }

            // 2. Close Desktop Profile Dropdown if clicked outside (only for md+)
            if (window.innerWidth >= 768 && profileDropdownMenu && !isClickInsideProfile) {
                closeProfileDropdown();
            }
        });

        // --- End of Navbar and Dropdown Logic ---
        // Initialize all mini maps (Leaflet)
        document.addEventListener('DOMContentLoaded', function() {
            const mapElements = document.querySelectorAll('.mini-map');

            mapElements.forEach(function(mapEl) {
                const lat = parseFloat(mapEl.dataset.lat);
                const lng = parseFloat(mapEl.dataset.lng);

                if (!isNaN(lat) && !isNaN(lng)) {
                    // Check if map is already initialized and destroy it if necessary (prevents Leaflet errors)
                    if (mapEl._leaflet_id) {
                        try {
                            L.map(mapEl.id).remove();
                        } catch (e) {
                            // ignore, was just for safety
                        }
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

                    // 💡 ADDED LINE: Hides the Leaflet prefix, which includes the Ukraine flag (🇺🇦)
                    miniMap.attributionControl.setPrefix(false);

                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '© OpenStreetMap'
                    }).addTo(miniMap);

                    L.marker([lat, lng]).addTo(miniMap);
                }
            });
        });
    </script>
</body>

</html>