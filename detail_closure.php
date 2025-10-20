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
  
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      font-family: 'Inter', sans-serif;
      background: #f8f8f8;
      color: #222;
    }

    .navbar {
      background: #fff;
      border-bottom: 1px solid #e5e5e5;
      padding: 16px 32px;
      display: flex;
      align-items: center;
      gap: 16px;
      position: sticky;
      top: 0;
      z-index: 10;
    }

    .navbar a {
      text-decoration: none;
      font-size: 22px;
      color: #000;
    }

    .navbar h1 {
      font-size: 18px;
      font-weight: 600;
    }

    .container {
      max-width: 900px;
      margin: 40px auto;
      padding: 0 20px;
    }

    .header-card {
      background: #fff;
      border: 1px solid #e0e0e0;
      border-radius: 12px;
      padding: 24px;
      margin-bottom: 24px;
    }

    .header-card h2 {
      font-size: 24px;
      font-weight: 600;
      margin-bottom: 4px;
    }

    .header-card .subtitle {
      color: #777;
      font-size: 14px;
    }

    .info-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
      gap: 16px;
      margin-top: 24px;
    }

    .info-card {
      background: #fff;
      border: 1px solid #e0e0e0;
      border-radius: 12px;
      padding: 16px;
    }

    .info-card .label {
      font-size: 12px;
      color: #777;
      margin-bottom: 6px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .info-card .value {
      font-size: 15px;
      font-weight: 500;
    }

    /* Map Section */
    .map-section {
      background: #fff;
      border: 1px solid #e0e0e0;
      border-radius: 12px;
      padding: 24px;
      margin-bottom: 24px;
    }

    .map-section h3 {
      font-size: 18px;
      font-weight: 600;
      margin-bottom: 16px;
      color: #111;
    }

    #detail-map {
      height: 350px;
      width: 100%;
      border-radius: 8px;
      border: 2px solid #e0e0e0;
    }

    .map-placeholder {
      height: 350px;
      width: 100%;
      background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
      border-radius: 8px;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      color: #9ca3af;
      font-size: 16px;
      border: 2px dashed #d1d5db;
      gap: 8px;
    }

    .closure-visual {
      background: #fff;
      border: 1px solid #e0e0e0;
      border-radius: 12px;
      padding: 24px;
      margin-top: 30px;
    }

    .closure-visual h3 {
      font-size: 18px;
      font-weight: 600;
      text-align: center;
      margin-bottom: 20px;
      color: #111;
    }

    .cores-container {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
      gap: 16px;
    }

    .core-item {
      border: 1px solid #e0e0e0;
      border-radius: 10px;
      padding: 12px;
      text-align: center;
      background: #fafafa;
      transition: 0.2s;
    }

    .core-item:hover {
      background: #fff;
      box-shadow: 0 2px 6px rgba(0,0,0,0.05);
    }

    .core-number {
      font-size: 12px;
      color: #555;
      margin-bottom: 8px;
    }

    .color-dot {
      width: 16px;
      height: 16px;
      border-radius: 50%;
      display: inline-block;
      margin-bottom: 8px;
      border: 1px solid #999;
    }

    .core-label {
      font-size: 12px;
      font-weight: 500;
      color: #333;
      margin-bottom: 6px;
    }

    .core-destination {
      font-size: 11px;
      background: #f0f0f0;
      border-radius: 6px;
      padding: 4px 6px;
      min-height: 22px;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .core-destination.empty {
      color: #aaa;
      font-style: italic;
    }

    .actions {
      margin-top: 40px;
      text-align: center;
      display: flex;
      justify-content: center;
      gap: 12px;
      flex-wrap: wrap;
    }

    .btn {
      border: 1px solid #000;
      border-radius: 8px;
      padding: 10px 20px;
      background: #000;
      color: #fff;
      text-decoration: none;
      font-size: 14px;
      font-weight: 600;
      transition: 0.3s;
      cursor: pointer;
    }

    .btn:hover {
      background: #fff;
      color: #000;
    }

    .btn-secondary {
      background: #fff;
      color: #000;
    }

    .btn-secondary:hover {
      background: #000;
      color: #fff;
    }

    #map-error { 
      display:none; 
      background:#ffe6e6; 
      border:1px solid #ffb3b3; 
      color:#800; 
      padding:10px; 
      border-radius:6px; 
      margin-bottom:10px; 
    }

    /* coord-controls removed: editing coordinates handled in Edit page */

    @media (max-width: 768px) {
      #detail-map, .map-placeholder {
        height: 250px;
      }

      .coord-controls {
        flex-direction: column;
      }

      #coord-input, #save-coord {
        width: 100%;
      }
    }
  </style>
</head>
<body>
  <div class="navbar">
    <a href="dashboard.php">←</a>
    <h1>Detail Closure</h1>
  </div>

  <div class="container">
    <div class="header-card">
      <h2><?= htmlspecialchars($closure['nama_closure']) ?></h2>
      <div class="subtitle"><?= htmlspecialchars($closure['kode_closure']) ?></div>

      <div class="info-grid">
        <div class="info-card">
          <div class="label">Jenis Kabel</div>
          <div class="value"><?= htmlspecialchars($closure['jenis_kabel']) ?></div>
        </div>
        <div class="info-card">
          <div class="label">Alamat Fisik</div>
          <div class="value"><?= htmlspecialchars($closure['alamat_fisik']) ?></div>
        </div>
        <?php if($closure['koordinat']): ?>
        <div class="info-card">
          <div class="label">Koordinat GPS</div>
          <div class="value" style="font-family: monospace; font-size: 13px;"><?= htmlspecialchars($closure['koordinat']) ?></div>
        </div>
        <?php endif; ?>
        <?php if($closure['jarak_tujuan']): ?>
        <div class="info-card">
          <div class="label">Jarak Tujuan</div>
          <div class="value"><?= htmlspecialchars($closure['jarak_tujuan']) ?> km</div>
        </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Map Section -->
    <div class="map-section">
      <h3>Lokasi Closure</h3>
      <div id="map-error">Peta gagal dimuat. Periksa koneksi internet atau coba refresh halaman.</div>
      <div id="detail-map"></div>
    </div>

    <div class="closure-visual">
      <h3>Data Core Fiber</h3>
      <div class="cores-container">
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
        <div class="core-item">
          <div class="core-number">Core <?= $i ?></div>
          <div class="color-dot" style="background-color: <?= $color ?>"></div>
          <div class="core-label"><?= htmlspecialchars($core['warna_core']) ?></div>
          <div class="core-destination <?= empty(trim($core['tujuan_core'])) ? 'empty' : '' ?>">
            <?= !empty(trim($core['tujuan_core'])) ? htmlspecialchars($core['tujuan_core']) : 'Belum Terisi' ?>
          </div>
        </div>
        <?php $i++; endwhile; ?>
      </div>
    </div>

    <div class="actions">
      <a href="edit_closure.php?id=<?= $closure['id_closure'] ?>" class="btn">Edit</a>
      <a href="dashboard.php" class="btn btn-secondary">← Kembali</a>
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