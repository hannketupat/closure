<?php
session_start();
include 'koneksi.php';
if (!isset($_SESSION['admin'])) header("Location: index.php");
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Closure Baru</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    
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

        /* ===== Navbar ===== */
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

        /* ===== Container ===== */
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

        /* ===== Map Container ===== */
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

        /* ===== Core Table ===== */
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

        /* ===== Input Tujuan Core ===== */
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

        /* ===== Warna Core Preview Only ===== */
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

        /* ===== Buttons ===== */
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
        }

        .btn-secondary:hover {
            background: #000;
            color: #fff;
        }

        @media (max-width: 768px) {
            .form-card {
                padding: 24px;
            }

            .form-actions {
                flex-direction: column;
            }

            .btn {
                width: 100%;
            }

            #map {
                height: 300px;
            }
        }
    </style>

    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <script>
        const warna12 = [{
                nama: "Biru",
                hex: "#4a90e2"
            },
            {
                nama: "Oranye",
                hex: "#f5a623"
            },
            {
                nama: "Hijau",
                hex: "#7ed321"
            },
            {
                nama: "Coklat",
                hex: "#a67c52"
            },
            {
                nama: "Abu-abu",
                hex: "#9b9b9b"
            },
            {
                nama: "Putih",
                hex: "#ffffff"
            },
            {
                nama: "Merah",
                hex: "#d0021b"
            },
            {
                nama: "Hitam",
                hex: "#000000"
            },
            {
                nama: "Kuning",
                hex: "#f8e71c"
            },
            {
                nama: "Ungu",
                hex: "#9013fe"
            },
            {
                nama: "Merah Muda",
                hex: "#ffb6c1"
            },
            {
                nama: "Aqua",
                hex: "#50e3c2"
            }
        ];

        function tampilkanTabelCore() {
            const jenis = document.getElementById("jenis_kabel").value;
            const jumlah = jenis === "4 core" ? 4 : jenis === "8 core" ? 8 : jenis === "12 core" ? 12 : jenis === "24 core" ? 24 : 0;
            const tabelDiv = document.getElementById("tabel_core");

            if (jumlah === 0) {
                tabelDiv.innerHTML = "";
                return;
            }

            let html = `
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
      `;

            for (let i = 0; i < jumlah; i++) {
                const w = warna12[i % 12];
                html += `
          <tr>
            <td style="text-align:center;">${i + 1}</td>
            <td>
              <div class="core-color">
                <span class="color-dot" style="background-color:${w.hex};"></span>
                ${w.nama}
                <input type="hidden" name="warna_core[]" value="${w.nama}">
              </div>
            </td>
            <td><input type="text" name="tujuan_core[]" placeholder="Misal: ODP-001"></td>
          </tr>
        `;
            }

            html += `</tbody></table></div>`;
            tabelDiv.innerHTML = html;
        }

        // Leaflet Map
        let map, marker;

        function initMap() {
            // Default koordinat Jakarta
            const defaultLat = -6.586363275689849; 
            const defaultLng = 106.75895461056768;

            map = L.map('map').setView([defaultLat, defaultLng], 13);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors',
                maxZoom: 19
            }).addTo(map);

            marker = L.marker([defaultLat, defaultLng], {
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

            // Set koordinat awal
            updateKoordinat(defaultLat, defaultLng);
        }

        function updateKoordinat(lat, lng) {
            document.getElementById('koordinat').value = lat.toFixed(6) + ',' + lng.toFixed(6);
        }

        // Initialize map setelah halaman load
        window.addEventListener('load', initMap);
    </script>
</head>

<body>
    <div class="navbar">
        <a href="dashboard.php" class="back-btn">←</a>
        <h1>Tambah Closure Baru</h1>
    </div>

    <div class="container">
        <form action="proses_simpan.php" method="POST">
            <div class="form-card">
                <div class="form-section">
                    <h3>Informasi Dasar Closure</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Kode Closure</label>
                            <input type="text" name="kode_closure" placeholder="Contoh: CLS-001" required>
                        </div>
                        <div class="form-group">
                            <label>Nama Closure</label>
                            <input type="text" name="nama_closure" placeholder="Contoh: Closure Jl. Sudirman" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Jenis Kabel</label>
                        <select name="jenis_kabel" id="jenis_kabel" onchange="tampilkanTabelCore()" required>
                            <option value="">-- Pilih Jenis Kabel --</option>
                            <option value="4 core">4 Core</option>
                            <option value="8 core">8 Core</option>
                            <option value="12 core">12 Core</option>
                            <option value="24 core">24 Core</option>
                        </select>
                    </div>
                </div>

                <div class="form-section">
                    <h3>Lokasi & Jarak</h3>
                    <div class="form-group">
                        <label>Alamat Fisik</label>
                        <textarea name="alamat_fisik" placeholder="Masukkan alamat lengkap..." required></textarea>
                    </div>

                    <div class="form-group">
                        <label>Koordinat GPS</label>
                        <div class="map-info">
                            📍 Klik pada peta atau drag marker untuk menentukan lokasi closure
                        </div>
                        <input type="text" id="koordinat" name="koordinat" placeholder="-6.208800,106.845600" readonly>
                        <div id="map"></div>
                    </div>

                    <div class="form-group">
                        <label>Jarak ke Tujuan (km)</label>
                        <input type="number" step="0.01" name="jarak_tujuan" placeholder="Contoh: 2.5">
                    </div>
                </div>
            </div>

            <div class="form-card">
                <div class="form-section">
                    <h3>Data Core Fiber</h3>
                    <div id="tabel_core"></div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Simpan Closure</button>
                    <a href="dashboard.php" class="btn btn-secondary">Batal</a>
                </div>
            </div>
        </form>
    </div>
</body>

</html>