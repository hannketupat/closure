<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
// Pastikan file 'koneksi.php' tersedia dan berisi koneksi database ($conn)
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
$jenis_kabel = $closure['jenis_kabel'];
if (strpos($jenis_kabel, '24') !== false) {
    $total_cores = 24;
} elseif (strpos($jenis_kabel, '12') !== false) {
    $total_cores = 12;
} elseif (strpos($jenis_kabel, '8') !== false) {
    $total_cores = 8;
} elseif (strpos($jenis_kabel, '4') !== false) {
    $total_cores = 4;
} else {
    $total_cores = 12; // default
}

// Ambil data core yang sudah ada
$core_query = "SELECT * FROM core_warna WHERE id_closure = $id ORDER BY warna_core";
$core_result = mysqli_query($conn, $core_query);
$existing_cores = [];
while ($row = mysqli_fetch_assoc($core_result)) {
    $existing_cores[] = $row;
}
$old_core_count = count($existing_cores);


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

// Gabungkan data existing dengan warna standar untuk memastikan semua core muncul (Hanya untuk pre-fill tampilan)
$core_data = [];
for ($i = 0; $i < $total_cores; $i++) {
    if (isset($existing_cores[$i])) {
        // Gunakan data yang sudah ada dari database
        $core_data[] = $existing_cores[$i];
    } else {
        // Buat data placeholder untuk core yang belum ada. 
        // Warna diambil dari daftar standar untuk inisialisasi, 
        // tapi nanti bisa diubah via dropdown.
        $core_data[] = [
            $core_pk => 0, // ID 0 menandakan data baru
            'warna_core' => $standard_colors[$i] ?? 'Putih', // Gunakan warna standar sebagai default
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

    // Tentukan jumlah core yang baru berdasarkan jenis kabel dari POST
    $new_total_cores = 0;
    if (strpos($jenis, '24') !== false) {
        $new_total_cores = 24;
    } elseif (strpos($jenis, '12') !== false) {
        $new_total_cores = 12;
    } elseif (strpos($jenis, '8') !== false) {
        $new_total_cores = 8;
    } elseif (strpos($jenis, '4') !== false) {
        $new_total_cores = 4;
    } else {
        $new_total_cores = 12; 
    }
    
    // =======================================================
    // LOGIKA PENGHAPUSAN CORE BERLEBIHAN
    // =======================================================
    if ($new_total_cores < $old_core_count) {
        $cores_to_delete = $old_core_count - $new_total_cores;
        
        // Asumsi: Core yang akan dihapus adalah core dengan ID tertinggi (yang paling terakhir di-insert/yang terakhir dalam urutan logis)
        if ($core_pk) {
            // Ambil ID Core yang akan dihapus: Urutkan berdasarkan $core_pk (ID Primary Key) secara DESC
            $delete_ids_query = "
                SELECT $core_pk
                FROM core_warna 
                WHERE id_closure = $id
                ORDER BY $core_pk DESC
                LIMIT $cores_to_delete
            ";
            
            $delete_result = mysqli_query($conn, $delete_ids_query);
            $ids_to_delete = [];
            while ($row = mysqli_fetch_assoc($delete_result)) {
                $ids_to_delete[] = $row[$core_pk];
            }

            if (!empty($ids_to_delete)) {
                $ids_string = implode(',', $ids_to_delete);
                
                // Hapus core yang berlebihan
                $delete_query = "DELETE FROM core_warna WHERE $core_pk IN ($ids_string)";
                mysqli_query($conn, $delete_query);
            }
        }
    }
    // =======================================================
    // AKHIR LOGIKA PENGHAPUSAN
    // =======================================================

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
    // Kita menggunakan 'warna_core_select' sebagai nama input baru untuk menampung warna yang dipilih
    if (isset($_POST['core_id']) && isset($_POST['warna_core_select'])) { 
        $cores_processed = 0;
        foreach ($_POST['core_id'] as $i => $core_id) {
            
            // Hentikan loop jika sudah memproses sebanyak $new_total_cores 
            if ($cores_processed >= $new_total_cores) {
                break; 
            }
            
            $tujuan = mysqli_real_escape_string($conn, $_POST['tujuan_core'][$i]);
            // AMBIL NILAI DARI DROPDOWN BARU
            $warna = mysqli_real_escape_string($conn, $_POST['warna_core_select'][$i]);
            $core_id = intval($core_id);
            
            if ($core_pk && $core_id > 0) {
                // Update existing core
                mysqli_query($conn, "UPDATE core_warna SET tujuan_core='$tujuan', warna_core='$warna' WHERE $core_pk=$core_id");
            } else if ($core_pk) {
                // Insert new core (Hanya terjadi jika jenis kabel BARU lebih besar dari yang LAMA)
                mysqli_query($conn, "INSERT INTO core_warna (id_closure, warna_core, tujuan_core) 
                                     VALUES ($id, '$warna', '$tujuan')");
            }
            $cores_processed++;
        }
    }

    header("Location: detail_closure.php?id=$id");
    exit;
}

$has_koordinat = !empty($closure['koordinat']);
$koordinat_parts = $has_koordinat ? explode(',', $closure['koordinat']) : [null, null];
$lat = $has_koordinat && isset($koordinat_parts[0]) ? trim($koordinat_parts[0]) : '-6.2088';
$lng = $has_koordinat && isset($koordinat_parts[1]) ? trim($koordinat_parts[1]) : '106.8456';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Closure - <?= htmlspecialchars($closure['nama_closure']) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    
    <style>
        /* CSS yang Anda sediakan (tidak ada perubahan signifikan, hanya penyesuaian untuk dropdown) */
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

        /* Map Styles */
        #map {
            height: 400px;
            width: 100%;
            border-radius: 12px;
            border: 1px solid #e0e0e0;
            margin-top: 10px;
        }

        .map-info {
            background: #f0f7ff;
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 13px;
            color: #1f3fb1;
            margin-bottom: 15px;
            border-left: 3px solid #1f3fb1;
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

        .core-table td input[type="text"],
        .core-table td select {
            /* Menyesuaikan style input/select di dalam tabel */
            width: 100%;
            padding: 8px 10px;
            border: 1px solid #ccc;
            border-radius: 8px;
            background: #fafafa;
            font-size: 14px;
            transition: all 0.2s;
        }

        .core-table td input[type="text"]:focus,
        .core-table td select:focus {
            outline: none;
            border-color: #000;
            background: #fff;
        }

        .core-color-display { /* Wrapper baru untuk menampilkan dot warna */
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 8px;
        }

        .color-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            flex-shrink: 0;
            border: 1px solid #ccc;
        }
        /* Penambahan style untuk select agar tidak bertabrakan dengan class .form-group select */
        .core-table select {
            width: 100%;
            padding: 8px 10px;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-size: 14px;
            background: #fafafa;
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

        @media (max-width: 768px) {
            #map {
                height: 300px;
            }
        }
    </style>
    
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
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
                            <option value="4 core" <?= $closure['jenis_kabel'] == '4 core' ? 'selected' : '' ?>>4 Core</option>
                            <option value="8 core" <?= $closure['jenis_kabel'] == '8 core' ? 'selected' : '' ?>>8 Core</option>
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

                    <div class="form-group">
                        <label>Koordinat GPS</label>
                        <input type="text" id="koordinat" name="koordinat" value="<?= htmlspecialchars($closure['koordinat']) ?>" readonly>
                        <div id="map"></div>
                    </div>

                    <div class="form-group">
                        <label>Jarak ke Tujuan (km)</label>
                        <input type="number" step="0.01" name="jarak_tujuan" value="<?= htmlspecialchars($closure['jarak_tujuan']) ?>">
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
                                // MAP WARNA HEX
                                $warna_map = [
                                    'Biru' => '#4a90e2', 'Oranye' => '#f5a623', 'Hijau' => '#7ed321',
                                    'Coklat' => '#a67c52', 'Abu-abu' => '#9b9b9b', 'Putih' => '#ffffff',
                                    'Merah' => '#d0021b', 'Hitam' => '#000000', 'Kuning' => '#f8e71c',
                                    'Ungu' => '#9013fe', 'Merah Muda' => '#ffb6c1', 'Aqua' => '#50e3c2',
                                    'Biru Muda' => '#87ceeb', 'Oranye Muda' => '#ffd700', 'Hijau Muda' => '#90ee90',
                                    'Coklat Muda' => '#deb887', 'Abu-abu Muda' => '#d3d3d3', 'Pink' => '#ffc0cb',
                                    'Merah Tua' => '#8b0000', 'Hitam Muda' => '#696969', 
                                    'Kuning Muda' => '#ffffe0', 'Ungu Muda' => '#dda0dd', 
                                    'Tosca' => '#40e0d0', 'Silver' => '#c0c0c0'
                                ];
                                
                                foreach ($core_data as $i => $core):
                                    $core_warna = $core['warna_core'];
                                    $hex = $warna_map[$core_warna] ?? '#ccc';
                                    $core_id_value = isset($core[$core_pk]) ? $core[$core_pk] : 0;
                                ?>
                                    <tr>
                                        <td style="text-align:center;"><?= $i + 1 ?></td>
                                        <td>
                                            <input type="hidden" name="core_id[]" value="<?= $core_id_value ?>">
                                            
                                            <div class="core-color-display" id="color-display-<?= $i ?>">
                                                <span class="color-dot" style="background-color:<?= $hex ?>; border-color: <?= $core_warna == 'Putih' ? '#000' : '#ccc' ?>;"></span>
                                                </div>
                                            
                                            <select 
                                                name="warna_core_select[]" 
                                                onchange="updateCoreColor(this, '<?= $i ?>')" 
                                                style="margin-top: 5px;"
                                            >
                                                <?php foreach ($standard_colors as $color): 
                                                    $opt_hex = $warna_map[$color] ?? '#ccc';
                                                ?>
                                                    <option 
                                                        value="<?= htmlspecialchars($color) ?>" 
                                                        <?= ($core_warna == $color) ? 'selected' : '' ?>
                                                        style="background-color: <?= $opt_hex ?>; color: <?= $opt_hex == '#000000' || $opt_hex == '#8b0000' || $opt_hex == '#1f3fb1' ? 'white' : 'black' ?>;"
                                                    >
                                                        <?= htmlspecialchars($color) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
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

    <script>
        // Data warna untuk keperluan JavaScript (agar dot warna bisa berubah real-time)
        const WARNA_MAP_JS = <?= json_encode($warna_map) ?>;

        function updateCoreColor(selectElement, coreIndex) {
            const selectedColorName = selectElement.value;
            const hexColor = WARNA_MAP_JS[selectedColorName] || '#ccc';
            const displayDiv = document.getElementById('color-display-' + coreIndex);
            const colorDot = displayDiv.querySelector('.color-dot');

            // Update warna dot
            colorDot.style.backgroundColor = hexColor;

            // Tambahkan border hitam untuk warna Putih
            if (selectedColorName === 'Putih') {
                 colorDot.style.borderColor = '#000';
            } else {
                 colorDot.style.borderColor = '#ccc';
            }
        }

        // Jalankan fungsi update saat halaman selesai dimuat untuk memastikan semua dot sesuai dengan nilai default
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('select[name="warna_core_select[]"]').forEach((select, index) => {
                updateCoreColor(select, index);
            });
        });
        
        // =======================================================
        // KODE JAVASCRIPT LEAFLET UNTUK MAP (TIDAK BERUBAH)
        // =======================================================
        let map, marker;

        function initMap() {
            const lat = <?= json_encode((float)$lat) ?>;
            const lng = <?= json_encode((float)$lng) ?>;

            map = L.map('map').setView([lat, lng], 15);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors',
                maxZoom: 19
            }).addTo(map);

            marker = L.marker([lat, lng], {
                draggable: true
            }).addTo(map);

            // Update koordinat saat marker dipindah
            marker.on('dragend', function(e) {
                const pos = marker.getLatLng();
                updateKoordinat(pos.lat, pos.lng);
            });

            // Klik map untuk set marker
            map.on('click', function(e) {
                marker.setLatLng(e.latlng);
                updateKoordinat(e.latlng.lat, e.latlng.lng);
            });
        }

        function updateKoordinat(lat, lng) {
            document.getElementById('koordinat').value = lat.toFixed(6) + ',' + lng.toFixed(6);
        }

        // Initialize map setelah halaman load
        window.addEventListener('load', initMap);
    </script>
</body>

</html>