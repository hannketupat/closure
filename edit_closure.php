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
    'Biru',
    'Oranye',
    'Hijau',
    'Coklat',
    'Abu-abu',
    'Putih',
    'Merah',
    'Hitam',
    'Kuning',
    'Ungu',
    'Merah Muda',
    'Aqua',
    'Biru Muda',
    'Oranye Muda',
    'Hijau Muda',
    'Coklat Muda',
    'Abu-abu Muda',
    'Pink',
    'Merah Tua',
    'Hitam Muda',
    'Kuning Muda',
    'Ungu Muda',
    'Tosca',
    'Silver'
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
jarak_tujuan='$jarak',
updated_at=NOW()
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

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
</head>

<body class="bg-white text-gray-900">
    <!-- Navbar -->
    <div class="sticky top-0 z-50 bg-white border-b border-gray-200 px-10 py-4 flex items-center justify-start gap-4">
        <a href="detail_closure.php?id=<?= $id ?>" class="text-2xl text-black hover:translate-x-[-4px] transition-transform">←</a>
        <h1 class="text-xl font-semibold">Edit Closure</h1>
    </div>
    <div class="px-10 py-3 bg-gray-50 border-b border-gray-200 text-sm text-gray-600">
        <nav class="flex items-center space-x-2">
            <a href="dashboard.php" class="hover:text-blue-700 transition-colors">Dashboard</a>
            <span>/</span>
            <a href="detail.php" class="hover:text-blue-700 transition-colors">Detail Closure</a>
            <span>/</span>
            <span class="text-gray-900 font-medium">Edit Closure</span>
        </nav>
    </div>
    <!-- Container -->
    <div class="max-w-3xl mx-auto my-10 px-5">
        <form method="POST">
            <!-- Form Card 1: Informasi Dasar & Lokasi -->
            <div class="bg-white border border-gray-200 rounded-xl p-8 mb-8 shadow-sm">
                <!-- Section: Informasi Dasar -->
                <div class="mb-8">
                    <h3 class="text-lg font-semibold mb-5 border-b border-gray-200 pb-2 text-gray-900">Informasi Dasar Closure</h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label class="block mb-2 text-sm font-medium">Kode Closure</label>
                            <input type="text" name="kode_closure" value="<?= htmlspecialchars($closure['kode_closure']) ?>" required class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm bg-gray-50 focus:outline-none focus:border-black focus:bg-white transition-colors">
                        </div>
                        <div>
                            <label class="block mb-2 text-sm font-medium">Nama Closure</label>
                            <input type="text" name="nama_closure" value="<?= htmlspecialchars($closure['nama_closure']) ?>" required class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm bg-gray-50 focus:outline-none focus:border-black focus:bg-white transition-colors">
                        </div>
                    </div>

                    <div class="mt-5">
                        <label class="block mb-2 text-sm font-medium">Jenis Kabel</label>
                        <select name="jenis_kabel" required onchange="alert('Perhatian: Mengubah jenis kabel akan mempengaruhi jumlah core. Silakan simpan dan refresh halaman untuk melihat perubahan.')" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm bg-gray-50 focus:outline-none focus:border-black focus:bg-white transition-colors">
                            <option value="4 core" <?= $closure['jenis_kabel'] == '4 core' ? 'selected' : '' ?>>4 Core</option>
                            <option value="8 core" <?= $closure['jenis_kabel'] == '8 core' ? 'selected' : '' ?>>8 Core</option>
                            <option value="12 core" <?= $closure['jenis_kabel'] == '12 core' ? 'selected' : '' ?>>12 Core</option>
                            <option value="24 core" <?= $closure['jenis_kabel'] == '24 core' ? 'selected' : '' ?>>24 Core</option>
                        </select>
                    </div>
                </div>

                <!-- Section: Lokasi & Jarak -->
                <div>
                    <h3 class="text-lg font-semibold mb-5 border-b border-gray-200 pb-2 text-gray-900">Lokasi & Jarak</h3>

                    <div class="mb-5">
                        <label class="block mb-2 text-sm font-medium">Alamat Fisik</label>
                        <textarea name="alamat_fisik" required class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm bg-gray-50 focus:outline-none focus:border-black focus:bg-white transition-colors resize-none"><?= htmlspecialchars($closure['alamat_fisik']) ?></textarea>
                    </div>

                    <div class="mb-5">
                        <label class="block mb-2 text-sm font-medium">Koordinat GPS</label>
                        <input type="text" id="koordinat" name="koordinat" value="<?= htmlspecialchars($closure['koordinat']) ?>" placeholder="-6.2088,106.8456" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm bg-gray-50 focus:outline-none focus:border-black focus:bg-white transition-colors">
                        <div id="map" class="h-72 w-full border border-gray-200 rounded-lg mt-3"></div>
                        <small class="text-gray-500 text-xs mt-2 block">Anda bisa drag marker untuk mengubah koordinat atau paste langsung "lat,lng" lalu simpan.</small>
                    </div>

                    <div>
                        <label class="block mb-2 text-sm font-medium">Jarak ke Tujuan (km)</label>
                        <input type="number" step="0.01" name="jarak_tujuan" value="<?= htmlspecialchars($closure['jarak_tujuan']) ?>" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm bg-gray-50 focus:outline-none focus:border-black focus:bg-white transition-colors">
                    </div>
                </div>
            </div>

            <!-- Form Card 2: Data Core Fiber -->
            <div class="bg-white border border-gray-200 rounded-xl p-8 mb-8 shadow-sm">
                <div>
                    <h3 class="text-lg font-semibold mb-5 border-b border-gray-200 pb-2 text-gray-900">
                        Data Core Fiber
                        <span class="inline-block ml-2 px-3 py-1 bg-gray-100 rounded-full text-sm font-medium text-gray-700"><?= $total_cores ?> Core</span>
                    </h3>

                    <div class="overflow-x-auto">
                        <table class="w-full border-collapse">
                            <thead>
                                <tr class="bg-gray-50">
                                    <th class="px-3 py-3 border border-gray-200 text-left text-sm font-semibold">No Core</th>
                                    <th class="px-3 py-3 border border-gray-200 text-left text-sm font-semibold">Warna Core</th>
                                    <th class="px-3 py-3 border border-gray-200 text-left text-sm font-semibold">Tujuan Core</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // MAP WARNA HEX
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
                                    $core_warna = $core['warna_core'];
                                    $hex = $warna_map[$core_warna] ?? '#ccc';
                                    $core_id_value = isset($core[$core_pk]) ? $core[$core_pk] : 0;
                                ?>
                                    <tr class="hover:bg-gray-50 transition-colors">
                                        <td class="px-3 py-3 border border-gray-200 text-center text-sm"><?= $i + 1 ?></td>
                                        <td class="px-3 py-3 border border-gray-200 text-sm">
                                            <input type="hidden" name="core_id[]" value="<?= $core_id_value ?>">

                                            <div class="flex items-center gap-2 mb-2" id="color-display-<?= $i ?>">
                                                <span class="w-3 h-3 rounded-full flex-shrink-0 border" style="background-color:<?= $hex ?>; border-color: <?= $core_warna == 'Putih' ? '#000' : '#999' ?>;"></span>
                                            </div>

                                            <select
                                                name="warna_core_select[]"
                                                onchange="updateCoreColor(this, '<?= $i ?>')"
                                                class="w-full px-2 py-2 border border-gray-300 rounded text-sm bg-gray-50 focus:outline-none focus:border-black focus:bg-white transition-colors">
                                                <?php foreach ($standard_colors as $color):
                                                    $opt_hex = $warna_map[$color] ?? '#ccc';
                                                ?>
                                                    <option
                                                        value="<?= htmlspecialchars($color) ?>"
                                                        <?= ($core_warna == $color) ? 'selected' : '' ?>
                                                        style="background-color: <?= $opt_hex ?>; color: <?= $opt_hex == '#000000' || $opt_hex == '#8b0000' || $opt_hex == '#1f3fb1' ? 'white' : 'black' ?>;">
                                                        <?= htmlspecialchars($color) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                        <td class="px-3 py-3 border border-gray-200 text-sm">
                                            <input type="text" name="tujuan_core[]" value="<?= htmlspecialchars($core['tujuan_core']) ?>" placeholder="Misal: ODP-001" class="w-full px-2 py-2 border border-gray-300 rounded text-sm bg-gray-50 focus:outline-none focus:border-black focus:bg-white transition-colors">
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="flex gap-3 justify-end mt-6 pt-5 border-t border-gray-200">
                    <button type="submit" class="px-6 py-3 bg-blue-900 text-white font-semibold text-sm rounded-lg hover:bg-gray-800 transition-colors">
                        Simpan Perubahan
                    </button>
                    <a href="detail_closure.php?id=<?= $id ?>" class="px-6 py-3 bg-white text-black border border-black font-semibold text-sm rounded-lg hover:bg-black hover:text-white transition-colors text-center">
                        Batal
                    </a>
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
            const colorDot = displayDiv.querySelector('.rounded-full');

            // Update warna dot
            colorDot.style.backgroundColor = hexColor;

            // Tambahkan border hitam untuk warna Putih
            if (selectedColorName === 'Putih') {
                colorDot.style.borderColor = '#000';
            } else {
                colorDot.style.borderColor = '#999';
            }
        }

        // Jalankan fungsi update saat halaman selesai dimuat untuk memastikan semua dot sesuai dengan nilai default
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('select[name="warna_core_select[]"]').forEach((select, index) => {
                updateCoreColor(select, index);
            });
        });

        // =======================================================
        // KODE JAVASCRIPT LEAFLET UNTUK MAP
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

            // handle paste/change on input to update marker
            const coordInput = document.getElementById('koordinat');

            function parseCoords(str) {
                str = (str || '').trim();
                const direct = str.match(/(-?\d+\.\d+)\s*,\s*(-?\d+\.\d+)/);
                if (direct) return [parseFloat(direct[1]), parseFloat(direct[2])];
                const at = str.match(/@(-?\d+\.\d+),(-?\d+\.\d+)/);
                if (at) return [parseFloat(at[1]), parseFloat(at[2])];
                return null;
            }

            coordInput.addEventListener('change', function() {
                const parsed = parseCoords(coordInput.value);
                if (parsed) {
                    marker.setLatLng(parsed);
                    map.setView(parsed, 16);
                }
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