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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #f5f7fa;
        }

        .navbar {
            background: white;
            padding: 20px 40px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .navbar-left {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .logo {
            width: 45px;
            height: 45px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }

        .navbar h1 {
            font-size: 22px;
            color: #333;
        }

        .navbar h1 .subtitle {
            font-size: 13px;
            color: #666;
            font-weight: 400;
            display: block;
            margin-top: 2px;
        }

        .navbar-right {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-badge {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 16px;
            background: #f8f9fa;
            border-radius: 10px;
        }

        .user-badge .avatar {
            width: 32px;
            height: 32px;
            background: linear-gradient(135deg, #0f2ba9ff 0%, #09139fff 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 14px;
        }

        .user-badge .name {
            font-size: 14px;
            color: #333;
            font-weight: 500;
        }

        .btn {
            padding: 10px 20px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #0f2ba9ff 0%, #09139fff 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }

        .btn-outline {
            background: white;
            color: #ef4444;
            border: 2px solid #ef4444;
        }

        .btn-outline:hover {
            background: #ef4444;
            color: white;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 30px 40px;
        }

        .page-header {
            margin-bottom: 30px;
        }

        .page-header h2 {
            font-size: 28px;
            color: #333;
            margin-bottom: 8px;
        }

        .page-header p {
            color: #666;
            font-size: 15px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: transform 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-3px);
        }

        .stat-card .icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-bottom: 15px;
        }

        .stat-card.purple .icon {
            background: rgba(102, 126, 234, 0.15);
        }

        .stat-card.green .icon {
            background: rgba(52, 211, 153, 0.15);
        }

        .stat-card.orange .icon {
            background: rgba(251, 146, 60, 0.15);
        }

        .stat-card.red .icon {
            background: rgba(239, 68, 68, 0.15);
        }

        .stat-card h3 {
            color: #666;
            font-size: 13px;
            font-weight: 500;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-card .value {
            font-size: 32px;
            font-weight: 700;
            color: #333;
        }

        .search-section {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }

        .search-form {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .search-form input {
            flex: 1;
            padding: 12px 18px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 15px;
            transition: all 0.3s;
        }

        .search-form input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .closure-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .closure-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            overflow: hidden;
            transition: all 0.3s;
        }

        .closure-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.12);
        }

        .closure-header {
            background: #1f3fb1ff;
            padding: 20px;
            color: white;
            position: relative;
        }

        .closure-header::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 0;
            right: 0;
            height: 10px;
            background: linear-gradient(to bottom, rgba(0,0,0,0.1), transparent);
        }

        .closure-header h3 {
            font-size: 18px;
            margin-bottom: 6px;
            font-weight: 600;
        }

        .closure-header .code {
            font-size: 13px;
            opacity: 0.9;
            font-weight: 500;
            letter-spacing: 0.5px;
        }

        .closure-body {
            padding: 20px;
        }

        .closure-info {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-bottom: 20px;
        }

        .info-row {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            font-size: 14px;
        }

        .info-row .icon {
            width: 18px;
            text-align: center;
            margin-top: 2px;
        }

        .info-row .label {
            color: #666;
            min-width: 90px;
            font-weight: 500;
        }

        .info-row .value {
            color: #333;
            flex: 1;
        }

        .core-visual {
            background: linear-gradient(to bottom, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            border: 2px solid #e0e0e0;
        }

        .core-progress {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 12px;
        }

        .progress-bar {
            flex: 1;
            height: 10px;
            background: #e0e0e0;
            border-radius: 5px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
            transition: width 0.3s;
            border-radius: 5px;
        }

        .progress-percentage {
            font-size: 14px;
            font-weight: 700;
            color: #667eea;
            min-width: 45px;
            text-align: right;
        }

        .core-info-text {
            font-size: 13px;
            color: #666;
            text-align: center;
            font-weight: 500;
        }

        .closure-actions {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 8px;
        }

        .btn-small {
            padding: 10px;
            font-size: 13px;
            text-align: center;
            border-radius: 8px;
        }

        .btn-info {
            background: #3b82f6;
            color: white;
        }

        .btn-info:hover {
            background: #2563eb;
        }

        .btn-warning {
            background: #f59e0b;
            color: white;
        }

        .btn-warning:hover {
            background: #d97706;
        }

        .btn-danger {
            background: #ef4444;
            color: white;
        }

        .btn-danger:hover {
            background: #dc2626;
        }

        .empty-state {
            text-align: center;
            padding: 80px 20px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .empty-state .icon {
            font-size: 80px;
            margin-bottom: 20px;
            opacity: 0.3;
        }

        .empty-state h3 {
            color: #333;
            margin-bottom: 10px;
            font-size: 20px;
        }

        .empty-state p {
            color: #666;
            margin-bottom: 25px;
        }

        @media (max-width: 768px) {
            .navbar {
                padding: 15px 20px;
                flex-direction: column;
                gap: 15px;
            }

            .container {
                padding: 20px;
            }

            .closure-grid {
                grid-template-columns: 1fr;
            }

            .search-form {
                flex-direction: column;
            }

            .closure-actions {
                grid-template-columns: 1fr;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="navbar">
        <div class="navbar-left">
            <div class="logo">🔌</div>
            <h1>
                Closure Management System
                <span class="subtitle">Sistem Manajemen Closure Fiber Optic</span>
            </h1>
        </div>
        <div class="navbar-right">
            <div class="user-badge">
                <div class="avatar">A</div>
                <span class="name">Admin</span>
            </div>
            <a href="tambah_closure.php" class="btn btn-primary">
                <span>+</span> Tambah Closure
            </a>
            <a href="logout.php" class="btn btn-outline">Logout</a>
        </div>
    </div>

    <div class="container">
        <div class="page-header">
            <h2>Dashboard Closure</h2>
            <p>Kelola dan monitor seluruh closure fiber optic Anda</p>
        </div>

        <div class="stats-grid">
            <div class="stat-card purple">
                <div class="icon">📦</div>
                <h3>Total Closure</h3>
                <div class="value"><?= $total_closure ?></div>
            </div>
            <div class="stat-card green">
                <div class="icon">🔗</div>
                <h3>Total Core</h3>
                <div class="value"><?= $total_core ?></div>
            </div>
            <div class="stat-card orange">
                <div class="icon">✓</div>
                <h3>Core Terisi</h3>
                <div class="value"><?= $core_terisi ?></div>
            </div>
            <div class="stat-card red">
                <div class="icon">○</div>
                <h3>Core Kosong</h3>
                <div class="value"><?= $core_kosong ?></div>
            </div>
        </div>

        <div class="search-section">
            <form method="get" class="search-form">
                <input type="text" name="cari" placeholder="Cari berdasarkan nama atau kode closure..." value="<?= htmlspecialchars($search) ?>">
                <button type="submit" class="btn btn-primary">Cari</button>
                <?php if($search): ?>
                    <a href="dashboard.php" class="btn btn-outline" style="border-color: #667eea; color: #667eea;">Reset</a>
                <?php endif; ?>
            </form>
        </div>

        <?php if(mysqli_num_rows($data) > 0): ?>
        <div class="closure-grid">
            <?php while($d = mysqli_fetch_assoc($data)): 
                $progress = $d['total_core'] > 0 ? ($d['core_terisi'] / $d['total_core']) * 100 : 0;
            ?>
            <div class="closure-card">
                <div class="closure-header">
                    <h3><?= htmlspecialchars($d['nama_closure']) ?></h3>
                    <div class="code">📋 <?= htmlspecialchars($d['kode_closure']) ?></div>
                </div>
                <div class="closure-body">
                    <div class="closure-info">
                        <div class="info-row">
                            <span class="icon">🔌</span>
                            <span class="label">Jenis Kabel:</span>
                            <span class="value"><?= htmlspecialchars($d['jenis_kabel']) ?></span>
                        </div>
                        <div class="info-row">
                            <span class="icon">📍</span>
                            <span class="label">Alamat:</span>
                            <span class="value"><?= htmlspecialchars($d['alamat_fisik']) ?></span>
                        </div>
                        <?php if($d['koordinat']): ?>
                        <div class="info-row">
                            <span class="icon">🗺️</span>
                            <span class="label">Koordinat:</span>
                            <span class="value"><?= htmlspecialchars($d['koordinat']) ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if($d['jarak_tujuan']): ?>
                        <div class="info-row">
                            <span class="icon">📏</span>
                            <span class="label">Jarak:</span>
                            <span class="value"><?= htmlspecialchars($d['jarak_tujuan']) ?> km</span>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="core-visual">
                        <div class="core-info-text">
                            <?= $d['core_terisi'] ?> dari <?= $d['total_core'] ?> core terisi
                        </div>
                    </div>

                    <div class="closure-actions">
                        <a href="detail_closure.php?id=<?= $d['id_closure'] ?>" class="btn btn-info btn-small">👁️ Detail</a>
                        <a href="edit_closure.php?id=<?= $d['id_closure'] ?>" class="btn btn-warning btn-small">✏️ Edit</a>
                        <a href="hapus_closure.php?id=<?= $d['id_closure'] ?>" 
                           onclick="return confirm('⚠️ Hapus closure <?= htmlspecialchars($d['nama_closure']) ?>?\n\nSemua data core juga akan terhapus!')" 
                           class="btn btn-danger btn-small">🗑️ Hapus</a>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
        <?php else: ?>
        <div class="empty-state">
            <div class="icon">🔍</div>
            <h3><?= $search ? "Hasil Pencarian Tidak Ditemukan" : "Belum Ada Data Closure" ?></h3>
            <p><?= $search ? "Coba kata kunci lain atau reset pencarian" : "Mulai tambahkan closure baru untuk memulai" ?></p>
            <?php if(!$search): ?>
                <a href="tambah_closure.php" class="btn btn-primary">+ Tambah Closure Pertama</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>