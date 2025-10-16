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
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Closure - <?= htmlspecialchars($closure['nama_closure']) ?></title>
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
            align-items: center;
            gap: 20px;
        }

        .back-btn {
            text-decoration: none;
            color: #667eea;
            font-size: 24px;
            transition: transform 0.2s;
        }

        .back-btn:hover {
            transform: translateX(-5px);
        }

        .navbar h1 {
            font-size: 24px;
            color: #333;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 30px 40px;
        }

        .closure-header-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px;
            border-radius: 20px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }

        .closure-header-card h2 {
            font-size: 32px;
            margin-bottom: 10px;
        }

        .closure-header-card .subtitle {
            font-size: 18px;
            opacity: 0.9;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .info-card {
            background: white;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .info-card .label {
            color: #666;
            font-size: 13px;
            font-weight: 500;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .info-card .value {
            color: #333;
            font-size: 18px;
            font-weight: 600;
        }

        .closure-visual {
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }

        .closure-visual h3 {
            font-size: 22px;
            margin-bottom: 30px;
            color: #333;
            text-align: center;
        }

        .closure-box {
            border: 4px solid #333;
            border-radius: 20px;
            padding: 40px 30px;
            background: linear-gradient(to bottom, #f8f9fa 0%, #e9ecef 100%);
            position: relative;
            box-shadow: inset 0 2px 10px rgba(0,0,0,0.1);
        }

        .closure-box::before {
            content: 'FIBER OPTIC CLOSURE';
            position: absolute;
            top: 15px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 11px;
            font-weight: 700;
            color: #666;
            letter-spacing: 3px;
            background: #f8f9fa;
            padding: 5px 15px;
            border-radius: 5px;
        }

        .cores-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
            gap: 15px;
            margin-top: 40px;
        }

        .core-item {
            background: white;
            border: 2px solid #ddd;
            border-radius: 12px;
            padding: 15px;
            text-align: center;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }

        .core-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: var(--core-color);
        }

        .core-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
            border-color: var(--core-color);
        }

        .core-number {
            font-size: 14px;
            font-weight: 700;
            color: #333;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .core-color {
            display: inline-block;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-bottom: 10px;
            border: 3px solid #333;
            box-shadow: 0 3px 8px rgba(0,0,0,0.2);
        }

        .core-label {
            font-size: 12px;
            font-weight: 600;
            color: #666;
            margin-bottom: 8px;
        }

        .core-destination {
            font-size: 11px;
            color: #333;
            background: #f8f9fa;
            padding: 6px 10px;
            border-radius: 6px;
            margin-top: 8px;
            min-height: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 500;
        }

        .core-destination.empty {
            color: #999;
            font-style: italic;
            background: #fff;
            border: 1px dashed #ddd;
        }

        .btn {
            display: inline-block;
            padding: 12px 24px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            font-size: 15px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }

        .btn-secondary {
            background: white;
            color: #667eea;
            border: 2px solid #667eea;
        }

        .btn-secondary:hover {
            background: #667eea;
            color: white;
        }

        .actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .stats-mini {
            display: flex;
            justify-content: center;
            gap: 30px;
            margin: 20px 0;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
        }

        .stat-mini {
            text-align: center;
        }

        .stat-mini .number {
            font-size: 28px;
            font-weight: 700;
            color: #667eea;
        }

        .stat-mini .label {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 5px;
        }

        @media (max-width: 768px) {
            .navbar {
                padding: 15px 20px;
            }
            .container {
                padding: 20px;
            }
            .closure-header-card {
                padding: 25px;
            }
            .closure-header-card h2 {
                font-size: 24px;
            }
            .closure-visual {
                padding: 20px;
            }
            .closure-box {
                padding: 30px 15px;
            }
            .cores-container {
                grid-template-columns: repeat(auto-fill, minmax(110px, 1fr));
                gap: 10px;
            }
            .actions {
                flex-direction: column;
            }
            .btn {
                width: 100%;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="navbar">
        <a href="dashboard.php" class="back-btn">←</a>
        <h1>Detail Closure</h1>
    </div>

    <div class="container">
        <div class="closure-header-card">
            <h2><?= htmlspecialchars($closure['nama_closure']) ?></h2>
            <div class="subtitle"><?= htmlspecialchars($closure['kode_closure']) ?></div>
        </div>

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

        <div class="closure-visual">
            <h3>🔌 Diagram Visual Closure Fiber Optic</h3>
            
            <?php
            $total_cores = mysqli_num_rows($core_data);
            mysqli_data_seek($core_data, 0);
            $filled_cores = 0;
            while($temp = mysqli_fetch_assoc($core_data)) {
                if(!empty(trim($temp['tujuan_core']))) $filled_cores++;
            }
            mysqli_data_seek($core_data, 0);
            ?>

            <div class="stats-mini">
                <div class="stat-mini">
                    <div class="number"><?= $total_cores ?></div>
                    <div class="label">Total Core</div>
                </div>
                <div class="stat-mini">
                    <div class="number"><?= $filled_cores ?></div>
                    <div class="label">Core Terisi</div>
                </div>
                <div class="stat-mini">
                    <div class="number"><?= $total_cores - $filled_cores ?></div>
                    <div class="label">Core Kosong</div>
                </div>
            </div>

            <div class="closure-box">
                <div class="cores-container">
                    <?php 
                    $color_map = [
                        'Biru' => '#0066cc',
                        'Oranye' => '#ff6600',
                        'Hijau' => '#00cc44',
                        'Coklat' => '#8b4513',
                        'Abu-abu' => '#808080',
                        'Putih' => '#ffffff',
                        'Merah' => '#cc0000',
                        'Hitam' => '#000000',
                        'Kuning' => '#ffcc00',
                        'Ungu' => '#9933cc',
                        'Merah Muda' => '#ff69b4',
                        'Aqua' => '#00cccc'
                    ];
                    
                    $core_num = 1;
                    while($core = mysqli_fetch_assoc($core_data)): 
                        $color = isset($color_map[$core['warna_core']]) ? $color_map[$core['warna_core']] : '#cccccc';
                    ?>
                    <div class="core-item" style="--core-color: <?= $color ?>">
                        <div class="core-number">Core <?= $core_num ?></div>
                        <div class="core-color" style="background-color: <?= $color ?>; <?= $core['warna_core'] == 'Putih' ? 'border-color: #333;' : '' ?>"></div>
                        <div class="core-label"><?= htmlspecialchars($core['warna_core']) ?></div>
                        <div class="core-destination <?= empty(trim($core['tujuan_core'])) ? 'empty' : '' ?>">
                            <?= !empty(trim($core['tujuan_core'])) ? htmlspecialchars($core['tujuan_core']) : 'Belum Terisi' ?>
                        </div>
                    </div>
                    <?php 
                    $core_num++;
                    endwhile; 
                    ?>
                </div>
            </div>
        </div>

        <div class="actions">
            <a href="edit_closure.php?id=<?= $closure['id_closure'] ?>" class="btn btn-primary">✏️ Edit Data Closure</a>
            <a href="dashboard.php" class="btn btn-secondary">← Kembali ke Dashboard</a>
        </div>
    </div>
</body>
</html>