<?php
session_start();
include 'koneksi.php';
if (!isset($_SESSION['admin'])) header("Location: index.php");

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$query = "SELECT * FROM closure WHERE id_closure = $id";
$result = mysqli_query($conn, $query);
$closure = mysqli_fetch_assoc($result);

if (!$closure) {
    header("Location: dashboard.php");
    exit;
}

$core_query = "SELECT * FROM core_warna WHERE id_closure = $id";
$core_data = mysqli_query($conn, $core_query);

$has_koordinat = !empty($closure['koordinat']);
$koordinat_parts = $has_koordinat ? explode(',', $closure['koordinat']) : [null, null];
$lat = $has_koordinat && isset($koordinat_parts[0]) ? trim($koordinat_parts[0]) : null;
$lng = $has_koordinat && isset($koordinat_parts[1]) ? trim($koordinat_parts[1]) : null;
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Detail Closure - <?= htmlspecialchars($closure['nama_closure']) ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  
  <!-- Leaflet CSS -->
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" 
        integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" 
        crossorigin="" />
  
  <!-- Tailwind CSS -->
  <script src="https://cdn.tailwindcss.com"></script>
  
  <style>
    body {
      font-family: 'Inter', sans-serif;
    }
  </style>
</head>
<body class="bg-gray-100">
  <!-- Navbar -->
  <div class="sticky top-0 z-10 bg-white border-b border-gray-200 px-8 py-4 flex items-center gap-4">
    <a href="dashboard.php" class="text-2xl text-black">←</a>
    <h1 class="text-lg font-semibold">Detail Closure</h1>
  </div>

  <!-- Container -->
  <div class="max-w-4xl mx-auto my-10 px-5">
    <!-- Header Card -->
    <div class="bg-white border border-gray-200 rounded-xl p-6 mb-6">
      <h2 class="text-2xl font-semibold mb-1"><?= htmlspecialchars($closure['nama_closure']) ?></h2>
      <div class="text-gray-500 text-sm"><?= htmlspecialchars($closure['kode_closure']) ?></div>

      <!-- Info Grid -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mt-6">
        <div class="bg-white border border-gray-200 rounded-xl p-4">
          <div class="text-xs text-gray-500 uppercase tracking-widest mb-1">Jenis Kabel</div>
          <div class="text-sm font-medium"><?= htmlspecialchars($closure['jenis_kabel']) ?></div>
        </div>
        <div class="bg-white border border-gray-200 rounded-xl p-4">
          <div class="text-xs text-gray-500 uppercase tracking-widest mb-1">Alamat Fisik</div>
          <div class="text-sm font-medium"><?= htmlspecialchars($closure['alamat_fisik']) ?></div>
        </div>
        <?php if($closure['koordinat']): ?>
        <div class="bg-white border border-gray-200 rounded-xl p-4">
          <div class="text-xs text-gray-500 uppercase tracking-widest mb-1">Koordinat GPS</div>
          <div class="text-sm font-medium font-mono"><?= htmlspecialchars($closure['koordinat']) ?></div>
        </div>
        <?php endif; ?>
        <?php if($closure['jarak_tujuan']): ?>
        <div class="bg-white border border-gray-200 rounded-xl p-4">
          <div class="text-xs text-gray-500 uppercase tracking-widest mb-1">Jarak Tujuan</div>
          <div class="text-sm font-medium"><?= htmlspecialchars($closure['jarak_tujuan']) ?> km</div>
        </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Map Section -->
    <div class="bg-white border border-gray-200 rounded-xl p-6 mb-6">
      <h3 class="text-lg font-semibold mb-4 text-gray-900">Lokasi Closure</h3>
      <div id="map-error" class="hidden bg-red-100 border border-red-400 text-red-700 px-3 py-2 rounded mb-4">
        Peta gagal dimuat. Periksa koneksi internet atau coba refresh halaman.
      </div>
      <div id="detail-map" class="h-80 w-full rounded-lg border-2 border-gray-200"></div>
    </div>

    <!-- Closure Visual -->
    <div class="bg-white border border-gray-200 rounded-xl p-6">
      <h3 class="text-lg font-semibold text-center mb-5 text-gray-900">Data Core Fiber</h3>
      <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
        <?php 
        $color_map = [
          'Biru' => '#4a90e2',
          'Oranye' => '#f5a623',
          'Hijau' => '#7ed321',
          'Coklat' => '#a67c52',
          'Abu-abu' => '#9b9b9b',
          'Putih' => '#ffffff',
          'Merah' => '#d0021b',
          'Hitam' => '#000000',
          'Kuning' => '#f8e71c',
          'Ungu' => '#9013fe',
          'Merah Muda' => '#ffb6c1',
          'Aqua' => '#50e3c2',
          'Biru Muda' => '#87ceeb',
          'Oranye Muda' => '#ffd700',
          'Hijau Muda' => '#90ee90',
          'Coklat Muda' => '#deb887',
          'Abu-abu Muda' => '#d3d3d3',
          'Pink' => '#ffc0cb',
          'Merah Tua' => '#8b0000',
          'Hitam Muda' => '#696969',
          'Kuning Muda' => '#ffffe0',
          'Ungu Muda' => '#dda0dd',
          'Tosca' => '#40e0d0',
          'Silver' => '#c0c0c0'
        ];

        $i = 1;
        while($core = mysqli_fetch_assoc($core_data)): 
          $color = $color_map[$core['warna_core']] ?? '#ccc';
        ?>
        <div class="border border-gray-200 rounded-lg p-3 text-center bg-gray-50 hover:bg-white hover:shadow transition-all duration-200">
          <div class="text-xs text-gray-600 mb-2">Core <?= $i ?></div>
          <div class="flex justify-center mb-2">
            <div class="w-4 h-4 rounded-full border border-gray-400" style="background-color: <?= $color ?>"></div>
          </div>
          <div class="text-xs font-medium text-gray-800 mb-1"><?= htmlspecialchars($core['warna_core']) ?></div>
          <div class="text-xs bg-gray-100 rounded px-1.5 py-1 min-h-5 flex items-center justify-center <?= empty(trim($core['tujuan_core'])) ? 'text-gray-400 italic' : '' ?>">
            <?= !empty(trim($core['tujuan_core'])) ? htmlspecialchars($core['tujuan_core']) : 'Belum Terisi' ?>
          </div>
        </div>
        <?php $i++; endwhile; ?>
      </div>
    </div>

    <!-- Actions -->
    <div class="mt-10 flex justify-center gap-3 flex-wrap">
      <a href="edit_closure.php?id=<?= $closure['id_closure'] ?>" class="px-5 py-2.5 bg-black text-white rounded-lg font-semibold text-sm hover:bg-white hover:text-black border border-black transition-colors duration-300">
        Edit
      </a>
      <a href="dashboard.php" class="px-5 py-2.5 bg-white text-black rounded-lg font-semibold text-sm hover:bg-black hover:text-white border border-black transition-colors duration-300">
        ← Kembali
      </a>
    </div>
  </div>

  <!-- Leaflet JS -->
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
          integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" 
          crossorigin=""></script>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Check if Leaflet loaded
      if (typeof L === 'undefined') {
        document.getElementById('map-error').style.display = 'block';
        document.getElementById('map-error').innerHTML = 'Leaflet tidak ter-load. Periksa koneksi internet.';
        console.error('Leaflet library tidak ter-load!');
        return;
      }
      
      // default center if no koordinat set
      const hasCoord = <?= $has_koordinat ? 'true' : 'false' ?>;
      const lat = hasCoord ? <?= json_encode((float)$lat) ?> : -6.2088;
      const lng = hasCoord ? <?= json_encode((float)$lng) ?> : 106.8456;
      
      console.log('Initializing map with coordinates:', lat, lng, 'hasCoord:', hasCoord);
      
      try {
        const map = L.map('detail-map').setView([lat, lng], hasCoord ? 16 : 12);
        
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
          attribution: '© OpenStreetMap contributors',
          maxZoom: 19
        }).addTo(map);
        
        let marker = null;
        if (hasCoord) {
          marker = L.marker([lat, lng]).addTo(map);
          const popupContent = <?= json_encode('<b>' . htmlspecialchars($closure['nama_closure']) . '</b><br>' . htmlspecialchars($closure['alamat_fisik'])) ?>;
          marker.bindPopup(popupContent).openPopup();
        }
        
        // Coordinates are view-only on detail page. To change coordinates, open Edit page.
        
        // Force map to refresh size after a brief delay
        setTimeout(function() {
          map.invalidateSize();
          console.log('Map size invalidated');
        }, 100);
        
      } catch(error) {
        console.error('Error initializing map:', error);
        document.getElementById('map-error').style.display = 'block';
        document.getElementById('map-error').innerHTML = 'Error memuat peta: ' + error.message;
      }
    });
  </script>
</body>
</html>