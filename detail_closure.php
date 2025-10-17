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

$core_query = "SELECT * FROM core_warna WHERE id_closure = $id ORDER BY id ASC";
$core_data = mysqli_query($conn, $core_query);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Detail Closure - <?= htmlspecialchars($closure['nama_closure']) ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
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
          <div class="value"><?= htmlspecialchars($closure['koordinat']) ?></div>
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
          'Aqua' => '#50e3c2'
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
      <a href="edit_closure.php?id=<?= $closure['id_closure'] ?>" class="btn">✏️ Edit</a>
      <a href="dashboard.php" class="btn btn-secondary">← Kembali</a>
    </div>
  </div>
</body>
</html>
