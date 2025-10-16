<?php 
// Temporary: enable verbose errors for debugging (remove in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include 'koneksi.php';
if (!isset($_SESSION['admin'])) header("Location: index.php");

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$closure_query = "SELECT * FROM closure WHERE id_closure = $id";
$closure_result = mysqli_query($conn, $closure_query);
$closure = mysqli_fetch_assoc($closure_result);

if (!$closure) {
    header("Location: dashboard.php");
    exit;
}

$core_query = "SELECT * FROM core_warna WHERE id_closure = $id";
$core_result = mysqli_query($conn, $core_query);
$core_data = [];
while ($row = mysqli_fetch_assoc($core_result)) {
    $core_data[] = $row;
}

// detect primary key field name in core_warna rows (avoid assuming 'id')
$core_pk = null;
if (!empty($core_data)) {
    $firstCore = $core_data[0];
    $candidates = ['id', 'id_core', 'id_core_warna', 'id_warna', 'id_closure_core'];
    foreach ($candidates as $cand) {
        if (array_key_exists($cand, $firstCore)) {
            $core_pk = $cand;
            break;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kode = mysqli_real_escape_string($conn, $_POST['kode_closure']);
    $nama = mysqli_real_escape_string($conn, $_POST['nama_closure']);
    $jenis = mysqli_real_escape_string($conn, $_POST['jenis_kabel']);
    $alamat = mysqli_real_escape_string($conn, $_POST['alamat_fisik']);
    $koordinat = mysqli_real_escape_string($conn, $_POST['koordinat']);
    $jarak = mysqli_real_escape_string($conn, $_POST['jarak_tujuan']);

    $update_closure = "UPDATE closure SET 
        kode_closure='$kode', 
        nama_closure='$nama', 
        jenis_kabel='$jenis',
        alamat_fisik='$alamat',
        koordinat='$koordinat',
        jarak_tujuan='$jarak'
        WHERE id_closure=$id";
    $res_up = mysqli_query($conn, $update_closure);
    if (!$res_up) {
        // show SQL error for debugging
        echo "<pre>SQL Error: " . mysqli_error($conn) . "\nQuery: " . htmlspecialchars($update_closure) . "</pre>";
        exit;
    }

    if (isset($_POST['core_id'])) {
        // Determine PK column name from existing core_data rows
        $pkName = null;
        if (!empty($core_data)) {
            $first = $core_data[0];
            foreach ($first as $k => $v) {
                // common pk candidates
                if (in_array($k, ['id', 'id_core', 'id_core_warna', 'id_warna', 'id_closure_core'])) {
                    $pkName = $k;
                    break;
                }
            }
        }

        foreach ($_POST['core_id'] as $i => $core_id) {
            $tujuan = mysqli_real_escape_string($conn, $_POST['tujuan_core'][$i]);
            if ($pkName) {
                $core_id = intval($core_id);
                $sql = "UPDATE core_warna SET tujuan_core='$tujuan' WHERE $pkName=$core_id";
                mysqli_query($conn, $sql);
            } else {
                // fallback: try to match by order (OFFSET)
                $offset = intval($i);
                // fetch the pk for the row at this offset
                $r = mysqli_query($conn, "SELECT * FROM core_warna WHERE id_closure = $id LIMIT 1 OFFSET $offset");
                $row = mysqli_fetch_assoc($r);
                if ($row) {
                    // try to find any numeric id-like column
                    $foundPk = null;
                    foreach ($row as $k => $v) {
                        if (preg_match('/^id(_|$)/', $k) || $k === 'id') { $foundPk = $k; break; }
                    }
                    if ($foundPk) {
                        $core_id_val = intval($row[$foundPk]);
                        mysqli_query($conn, "UPDATE core_warna SET tujuan_core='$tujuan' WHERE $foundPk=$core_id_val");
                    }
                }
            }
        }
    }

    header("Location: detail_closure.php?id=$id");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Closure - <?= htmlspecialchars($closure['nama_closure']) ?></title>
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
        }

        .navbar h1 {
            font-size: 24px;
            color: #333;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
            padding: 30px 40px;
        }

        .form-card {
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }

        .form-section {
            margin-bottom: 30px;
        }

        .form-section:last-child {
            margin-bottom: 0;
        }

        .form-section h3 {
            font-size: 20px;
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 600;
            font-size: 14px;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 15px;
            font-family: 'Inter', sans-serif;
            transition: all 0.3s;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .core-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .core-table th,
        .core-table td {
            padding: 12px;
            border: 1px solid #e0e0e0;
            text-align: left;
        }

        .core-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
            font-size: 14px;
        }

        .core-table td {
            vertical-align: middle;
        }

        .core-table .core-number {
            text-align: center;
            font-weight: 600;
            color: #333;
        }

        .core-color-cell {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .core-color-dot {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            border: 2px solid #333;
            flex-shrink: 0;
        }

        .core-table input {
            width: 100%;
            padding: 8px 12px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-size: 14px;
        }

        .core-table input:focus {
            outline: none;
            border-color: #667eea;
        }

        .core-table input[readonly] {
            background: #f8f9fa;
            cursor: not-allowed;
        }

        .btn {
            padding: 14px 28px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
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

        .form-actions {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #f0f0f0;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .alert-info {
            background: #dbeafe;
            color: #1e40af;
            border-left: 4px solid #3b82f6;
        }

        @media (max-width: 768px) {
            .navbar {
                padding: 15px 20px;
            }
            .container {
                padding: 20px;
            }
            .form-card {
                padding: 25px;
            }
            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="navbar">
        <a href="detail_closure.php?id=<?= $id ?>" class="back-btn">←</a>
        <h1>Edit Data Closure</h1>
    </div>

    <div class="container">
        <form method="POST">
            <div class="form-card">
                <div class="form-section">
                    <h3>Informasi Dasar Closure</h3>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Kode Closure *</label>
                            <input type="text" name="kode_closure" value="<?= htmlspecialchars($closure['kode_closure']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Nama Closure *</label>
                            <input type="text" name="nama_closure" value="<?= htmlspecialchars($closure['nama_closure']) ?>" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Jenis Kabel *</label>
                        <select name="jenis_kabel" required>
                            <option value="12 core" <?= $closure['jenis_kabel'] == '12 core' ? 'selected' : '' ?>>12 Core</option>
                            <option value="24 core" <?= $closure['jenis_kabel'] == '24 core' ? 'selected' : '' ?>>24 Core</option>
                        </select>
                    </div>
                </div>

                <div class="form-section">
                    <h3>Lokasi & Jarak</h3>
                    
                    <div class="form-group">
                        <label>Alamat Fisik *</label>
                        <textarea name="alamat_fisik" required><?= htmlspecialchars($closure['alamat_fisik']) ?></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Koordinat</label>
                            <input type="text" name="koordinat" value="<?= htmlspecialchars($closure['koordinat']) ?>">
                        </div>
                        <div class="form-group">
                            <label>Jarak ke Tujuan (km)</label>
                            <input type="number" name="jarak_tujuan" step="0.01" value="<?= htmlspecialchars($closure['jarak_tujuan']) ?>">
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-card">
                <div class="form-section">
                    <h3>Data Core & Tujuan</h3>
                    <div class="alert alert-info">
                        💡 Update tujuan untuk setiap core. Warna core tidak dapat diubah.
                    </div>
                    
                    <table class="core-table">
                        <thead>
                            <tr>
                                <th style="width: 100px;">No Core</th>
                                <th style="width: 200px;">Warna Core</th>
                                <th>Tujuan Core</th>
                            </tr>
                        </thead>
                        <tbody>
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
                            
                            foreach ($core_data as $i => $c): 
                                $color = isset($color_map[$c['warna_core']]) ? $color_map[$c['warna_core']] : '#cccccc';
                                $borderStyle = $c['warna_core'] == 'Putih' ? 'border: 2px solid #333;' : '';
                            ?>
                            <tr>
                                <td class="core-number">
                                    Core <?= $i + 1 ?>
                                    <input type="hidden" name="core_id[]" value="<?= isset($c[$core_pk]) ? htmlspecialchars($c[$core_pk]) : $i ?>">
                                </td>
                                <td>
                                    <div class="core-color-cell">
                                        <div class="core-color-dot" style="background-color: <?= $color ?>; <?= $borderStyle ?>"></div>
                                        <input type="text" value="<?= htmlspecialchars($c['warna_core']) ?>" readonly>
                                    </div>
                                </td>
                                <td>
                                    <input type="text" name="tujuan_core[]" value="<?= htmlspecialchars($c['tujuan_core']) ?>" placeholder="Masukkan tujuan core...">
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">💾 Update Data</button>
                    <a href="detail_closure.php?id=<?= $id ?>" class="btn btn-secondary">Batal</a>
                </div>
            </div>
        </form>
    </div>
</body>
</html>