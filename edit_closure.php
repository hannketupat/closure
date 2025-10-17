<?php
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

// Tentukan jumlah core berdasarkan jenis kabel
$total_cores = ($closure['jenis_kabel'] == '24 core') ? 24 : 12;

// Ambil data core yang sudah ada
$core_query = "SELECT * FROM core_warna WHERE id_closure = $id ORDER BY warna_core";
$core_result = mysqli_query($conn, $core_query);
$existing_cores = [];
while ($row = mysqli_fetch_assoc($core_result)) {
    $existing_cores[] = $row;
}

// Deteksi primary key untuk core_warna
$core_pk = null;
if (!empty($existing_cores)) {
    $firstCore = $existing_cores[0];
    foreach (['id', 'id_core', 'id_core_warna', 'id_warna', 'id_closure_core'] as $cand) {
        if (array_key_exists($cand, $firstCore)) {
            $core_pk = $cand;
            break;
        }
    }
}

// Daftar warna core standar (24 warna)
$standard_colors = [
    'Biru', 'Oranye', 'Hijau', 'Coklat', 'Abu-abu', 'Putih',
    'Merah', 'Hitam', 'Kuning', 'Ungu', 'Merah Muda', 'Aqua',
    'Biru Muda', 'Oranye Muda', 'Hijau Muda', 'Coklat Muda', 
    'Abu-abu Muda', 'Pink', 'Merah Tua', 'Hitam Muda', 
    'Kuning Muda', 'Ungu Muda', 'Tosca', 'Silver'
];

// Gabungkan data existing dengan warna standar untuk memastikan semua core muncul
$core_data = [];
for ($i = 0; $i < $total_cores; $i++) {
    if (isset($existing_cores[$i])) {
        // Gunakan data yang sudah ada
        $core_data[] = $existing_cores[$i];
    } else {
        // Buat data placeholder untuk core yang belum ada
        $core_data[] = [
            $core_pk => 0, // ID 0 menandakan data baru
            'warna_core' => $standard_colors[$i],
            'tujuan_core' => '',
            'id_closure' => $id
        ];
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
    mysqli_query($conn, $update_closure);

    // Update atau insert core data
    if (isset($_POST['core_id']) && isset($_POST['warna_core'])) {
        foreach ($_POST['core_id'] as $i => $core_id) {
            $tujuan = mysqli_real_escape_string($conn, $_POST['tujuan_core'][$i]);
            $warna = mysqli_real_escape_string($conn, $_POST['warna_core'][$i]);
            $core_id = intval($core_id);
            
            if ($core_pk && $core_id > 0) {
                // Update existing core
                mysqli_query($conn, "UPDATE core_warna SET tujuan_core='$tujuan' WHERE $core_pk=$core_id");
            } else if ($core_pk) {
                // Insert new core
                mysqli_query($conn, "INSERT INTO core_warna (id_closure, warna_core, tujuan_core) 
                                     VALUES ($id, '$warna', '$tujuan')");
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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #fff;
            color: #000000ff;
            margin: 0;
            padding: 0;
        }

        .navbar {
            background: #ffffffff;
            border-bottom: 1px solid #e0e0e0;
            padding: 16px 40px;
            display: flex;
            align-items: center;
            justify-content: flex-start;
            gap: 16px;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .back-btn {
            text-decoration: none;
            color: #111;
            font-size: 22px;
            transition: 0.2s;
        }

        .back-btn:hover {
            transform: translateX(-4px);
        }

        .navbar h1 {
            font-size: 20px;
            font-weight: 600;
            margin: 0;
        }

        .container {
            max-width: 900px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .form-card {
            background: #fff;
            border: 1px solid #e0e0e0;
            border-radius: 12px;
            padding: 32px;
            margin-bottom: 30px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .form-section h3 {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 20px;
            border-bottom: 1px solid #e0e0e0;
            padding-bottom: 8px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            font-weight: 500;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ccc;
            border-radius: 12px;
            font-size: 14px;
            background: #fafafa;
            color: #111;
            transition: border-color 0.2s;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #000;
            background: #fff;
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .core-table-wrapper {
            overflow-x: auto;
        }

        .core-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        .core-table th,
        .core-table td {
            padding: 12px;
            border: 1px solid #e0e0e0;
            text-align: left;
            font-size: 14px;
        }

        .core-table th {
            background: #f7f7f7;
            font-weight: 600;
        }

        .core-table td input[type="text"] {
            width: 100%;
            padding: 8px 10px;
            border: 1px solid #ccc;
            border-radius: 8px;
            background: #fafafa;
            font-size: 14px;
            transition: all 0.2s;
        }

        .core-table td input[type="text"]:focus {
            outline: none;
            border-color: #000;
            background: #fff;
        }

        .core-color {
            display: flex;
            align-items: center;
            gap: 8px;
            justify-content: flex-start;
            font-weight: 500;
            background: none;
            border: none;
            padding: 0;
        }

        .color-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            flex-shrink: 0;
            border: 1px solid #ccc;
        }

        .form-actions {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
            margin-top: 25px;
            border-top: 1px solid #e0e0e0;
            padding-top: 20px;
        }

        .btn {
            border: none;
            padding: 12px 24px;
            font-size: 14px;
            font-weight: 600;
            border-radius: 8px;
            cursor: pointer;
            transition: 0.3s;
        }

        .btn-primary {
            background: #1f3fb1ff;
            color: #fff;
        }

        .btn-primary:hover {
            background: #333;
        }

        .btn-secondary {
            background: transparent;
            text-align: center;
            color: #000;
            border: 1px solid #000;
            text-decoration: none;
            display: inline-block;
        }

        .btn-secondary:hover {
            background: #000;
            color: #fff;
        }

        .info-badge {
            display: inline-block;
            padding: 4px 12px;
            background: #f0f0f0;
            border-radius: 12px;
            font-size: 13px;
            font-weight: 500;
            margin-left: 8px;
        }
    </style>
</head>

<body>
    <div class="navbar">
        <a href="detail_closure.php?id=<?= $id ?>" class="back-btn">←</a>
        <h1>Edit Closure</h1>
    </div>

    <div class="container">
        <form method="POST">
            <div class="form-card">
                <div class="form-section">
                    <h3>Informasi Dasar Closure</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Kode Closure</label>
                            <input type="text" name="kode_closure" value="<?= htmlspecialchars($closure['kode_closure']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Nama Closure</label>
                            <input type="text" name="nama_closure" value="<?= htmlspecialchars($closure['nama_closure']) ?>" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Jenis Kabel</label>
                        <select name="jenis_kabel" required onchange="alert('Perhatian: Mengubah jenis kabel akan mempengaruhi jumlah core. Silakan simpan dan refresh halaman untuk melihat perubahan.')">
                            <option value="12 core" <?= $closure['jenis_kabel'] == '12 core' ? 'selected' : '' ?>>12 Core</option>
                            <option value="24 core" <?= $closure['jenis_kabel'] == '24 core' ? 'selected' : '' ?>>24 Core</option>
                        </select>
                    </div>
                </div>

                <div class="form-section">
                    <h3>Lokasi & Jarak</h3>
                    <div class="form-group">
                        <label>Alamat Fisik</label>
                        <textarea name="alamat_fisik" required><?= htmlspecialchars($closure['alamat_fisik']) ?></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Koordinat GPS</label>
                            <input type="text" name="koordinat" value="<?= htmlspecialchars($closure['koordinat']) ?>">
                        </div>
                        <div class="form-group">
                            <label>Jarak ke Tujuan (km)</label>
                            <input type="number" step="0.01" name="jarak_tujuan" value="<?= htmlspecialchars($closure['jarak_tujuan']) ?>">
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-card">
                <div class="form-section">
                    <h3>
                        Data Core Fiber
                        <span class="info-badge"><?= $total_cores ?> Core</span>
                    </h3>
                    <div class="core-table-wrapper">
                        <table class="core-table">
                            <thead>
                                <tr>
                                    <th>No Core</th>
                                    <th>Warna Core</th>
                                    <th>Tujuan Core</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $warna_map = [
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
                                foreach ($core_data as $i => $core):
                                    $hex = $warna_map[$core['warna_core']] ?? '#ccc';
                                    $core_id_value = isset($core[$core_pk]) ? $core[$core_pk] : 0;
                                ?>
                                    <tr>
                                        <td style="text-align:center;"><?= $i + 1 ?></td>
                                        <td>
                                            <div class="core-color">
                                                <span class="color-dot" style="background-color:<?= $hex ?>"></span>
                                                <?= htmlspecialchars($core['warna_core']) ?>
                                                <input type="hidden" name="core_id[]" value="<?= $core_id_value ?>">
                                                <input type="hidden" name="warna_core[]" value="<?= htmlspecialchars($core['warna_core']) ?>">
                                            </div>
                                        </td>
                                        <td>
                                            <input type="text" name="tujuan_core[]" value="<?= htmlspecialchars($core['tujuan_core']) ?>" placeholder="Misal: ODP-001">
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                    <a href="detail_closure.php?id=<?= $id ?>" class="btn btn-secondary">Batal</a>
                </div>
            </div>
        </form>
    </div>
</body>

</html>