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

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        body {
            font-family: 'Inter', sans-serif;
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
        <div class="overflow-x-auto">
          <table class="w-full border-collapse">
            <thead>
              <tr class="bg-gray-50">
                <th class="px-4 py-3 border border-gray-200 text-left text-sm font-semibold text-gray-900">No Core</th>
                <th class="px-4 py-3 border border-gray-200 text-left text-sm font-semibold text-gray-900">Warna Core</th>
                <th class="px-4 py-3 border border-gray-200 text-left text-sm font-semibold text-gray-900">Tujuan Core</th>
              </tr>
            </thead>
            <tbody>
      `;

            for (let i = 0; i < jumlah; i++) {
                const w = warna12[i % 12];
                const textColor = (w.hex === '#ffffff' || w.hex === '#f8e71c') ? '#000' : '#fff';
                html += `
          <tr class="hover:bg-gray-50 transition-colors">
            <td class="px-4 py-3 border border-gray-200 text-center text-sm text-gray-800 font-medium">${i + 1}</td>
            <td class="px-4 py-3 border border-gray-200 text-sm">
              <div class="flex items-center gap-3">
                <span class="w-4 h-4 rounded-full flex-shrink-0 border border-gray-300" style="background-color:${w.hex}; border-color: ${w.hex === '#ffffff' ? '#999' : 'inherit'};"></span>
                <span class="text-gray-900 font-medium">${w.nama}</span>
                <input type="hidden" name="warna_core[]" value="${w.nama}">
              </div>
            </td>
            <td class="px-4 py-3 border border-gray-200 text-sm">
              <input type="text" name="tujuan_core[]" placeholder="Misal: ODP-001"
              class="w-full px-2.5 py-2 border border-gray-300 rounded text-sm bg-gray-50 focus:outline-none focus:border-black focus:bg-white focus:ring-1 focus:ring-gray-200 transition-colors">
            </td>
          </tr>
        `;
            }

            html += `</tbody></table></div>`;
            tabelDiv.innerHTML = html;
        }

        // === PETA LEAFLET ===
        let map, marker;

        function initMap() {
            const defaultLat = -6.2088;
            const defaultLng = 106.8456;

            map = L.map('map').setView([defaultLat, defaultLng], 13);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors',
                maxZoom: 19
            }).addTo(map);

            

            marker = L.marker([defaultLat, defaultLng], {
                draggable: true
            }).addTo(map);

            marker.setIcon(L.icon({
                iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png',
                shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
                iconSize: [25, 41],
                iconAnchor: [12, 41],
                popupAnchor: [1, -34],
                shadowSize: [41, 41]
            }));

            marker.on('dragend', function() {
                const pos = marker.getLatLng();
                updateKoordinat(pos.lat, pos.lng);
            });

            map.on('click', function(e) {
                marker.setLatLng(e.latlng);
                updateKoordinat(e.latlng.lat, e.latlng.lng);
            });

            updateKoordinat(defaultLat, defaultLng);

            setTimeout(() => map.invalidateSize(), 100);
        }

        function updateKoordinat(lat, lng) {
            const latStr = lat.toFixed(8);
            const lngStr = lng.toFixed(8);

            document.getElementById('coordinateInput').value = `${latStr}, ${lngStr}`;
            document.getElementById('koordinat').value = `${latStr},${lngStr}`;
            document.getElementById('currentCoord').textContent = `${latStr}, ${lngStr}`;
        }

        function updateFromLatLng() {
            const val = document.getElementById('coordinateInput').value.trim();
            if (!val.includes(',')) {
                alert('Format salah. Gunakan format: latitude, longitude');
                return;
            }

            const [latStr, lngStr] = val.split(',').map(v => v.trim());
            const lat = parseFloat(latStr);
            const lng = parseFloat(lngStr);

            if (isNaN(lat) || isNaN(lng) || lat < -90 || lat > 90 || lng < -180 || lng > 180) {
                alert('Koordinat tidak valid. Coba ulangi.');
                return;
            }

            marker.setLatLng([lat, lng]);
            map.setView([lat, lng], 17);
            updateKoordinat(lat, lng);
        }

        function getCurrentLocation() {
            if (!navigator.geolocation) {
                alert('Browser tidak mendukung geolokasi');
                return;
            }

            const loader = document.createElement('div');
            loader.textContent = 'Mendapatkan lokasi...';
            loader.style.cssText = 'position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);background:#000;color:#fff;padding:20px;border-radius:8px;z-index:9999;font-size:14px;';
            document.body.appendChild(loader);

            navigator.geolocation.getCurrentPosition(
                (pos) => {
                    document.body.removeChild(loader);
                    const lat = pos.coords.latitude;
                    const lng = pos.coords.longitude;
                    marker.setLatLng([lat, lng]);
                    map.setView([lat, lng], 17);
                    updateKoordinat(lat, lng);
                },
                (err) => {
                    document.body.removeChild(loader);
                    let msg = 'Gagal mendapatkan lokasi: ';
                    switch (err.code) {
                        case err.PERMISSION_DENIED:
                            msg += 'Izin lokasi ditolak.';
                            break;
                        case err.POSITION_UNAVAILABLE:
                            msg += 'Lokasi tidak tersedia.';
                            break;
                        case err.TIMEOUT:
                            msg += 'Timeout.';
                            break;
                        default:
                            msg += 'Kesalahan tidak diketahui.';
                    }
                    alert(msg);
                }
            );
        }

        window.addEventListener('load', initMap);
    </script>

<body class="bg-white text-gray-900">
    <!-- Navbar -->
    <div class="sticky top-0 z-50 bg-white border-b border-gray-200 px-10 py-4 flex items-center justify-start gap-4">
        <a href="dashboard.php" class="text-2xl text-black hover:translate-x-[-4px] transition-transform">←</a>
        <h1 class="text-xl font-semibold">Tambah Closure Baru</h1>
    </div>
    <div class="px-10 py-3 bg-gray-50 border-b border-gray-200 text-sm text-gray-600">
        <nav class="flex items-center space-x-2">
            <a href="dashboard.php" class="hover:text-blue-700 transition-colors">Dashboard</a>
            <span>/</span>
            <span class="text-gray-900 font-medium">Tambah Closure</span>
        </nav>
    </div>
    <!-- Container -->
    <div class="max-w-3xl mx-auto my-10 px-5">
        <form action="proses_simpan.php" method="POST">
            <!-- Form Card 1: Informasi Dasar & Lokasi -->
            <div class="bg-white border border-gray-200 rounded-xl p-8 mb-8 shadow-sm">
                <!-- Section: Informasi Dasar -->
                <div class="mb-8">
                    <h3 class="text-lg font-semibold mb-5 border-b border-gray-200 pb-2">Informasi Dasar Closure</h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label class="block mb-2 text-sm font-medium">Kode Closure</label>
                            <input type="text" name="kode_closure" placeholder="Contoh: CLS-001" required class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm bg-gray-50 focus:outline-none focus:border-black focus:bg-white transition-colors">
                        </div>
                        <div>
                            <label class="block mb-2 text-sm font-medium">Nama Closure</label>
                            <input type="text" name="nama_closure" placeholder="Contoh: Closure Jl. Sudirman" required class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm bg-gray-50 focus:outline-none focus:border-black focus:bg-white transition-colors">
                        </div>
                    </div>

                    <div class="mt-5">
                        <label class="block mb-2 text-sm font-medium">Jenis Kabel</label>
                        <select name="jenis_kabel" id="jenis_kabel" onchange="tampilkanTabelCore()" required class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm bg-gray-50 focus:outline-none focus:border-black focus:bg-white transition-colors">
                            <option value="">-- Pilih Jenis Kabel --</option>
                            <option value="4 core">4 Core</option>
                            <option value="8 core">8 Core</option>
                            <option value="12 core">12 Core</option>
                            <option value="24 core">24 Core</option>
                        </select>
                    </div>
                </div>

                <!-- Section: Lokasi & Jarak -->
                <div>
                    <h3 class="text-lg font-semibold mb-5 border-b border-gray-200 pb-2">Lokasi & Jarak</h3>

                    <div class="mb-5">
                        <label class="block mb-2 text-sm font-medium">Alamat Fisik</label>
                        <textarea name="alamat_fisik" placeholder="Masukkan alamat lengkap..." required class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm bg-gray-50 focus:outline-none focus:border-black focus:bg-white transition-colors resize-none"></textarea>
                    </div>

                    <div class="mb-5">
                        <label class="block mb-2 text-sm font-medium">Koordinat GPS</label>
                        <div class="bg-blue-50 border-l-4 border-blue-600 text-blue-900 px-4 py-3 rounded text-xs mb-4">
                            <div class="mb-2">Gunakan salah satu cara:</div>
                            <ul class="list-disc list-inside space-y-1">
                                <li>Klik pada peta untuk menempatkan marker</li>
                                <li>Drag marker untuk memindahkan lokasi</li>
                                <li>Input latitude & longitude lalu klik tombol Perbarui Peta</li>
                                <li>Klik Lokasi GPS Saya untuk menggunakan lokasi perangkat</li>
                            </ul>
                        </div>
                        <div class="mb-3">
                            <label class="block text-xs text-gray-600 mb-1">Koordinat GPS (Latitude, Longitude)</label>
                            <input type="text" id="coordinateInput" placeholder="-6.2088, 106.8456" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:outline-none focus:border-black focus:ring-1 focus:ring-blue-200 transition-colors">
                            <p class="text-xs text-gray-500 mt-1">Format: -6.2088, 106.8456 (gunakan koma sebagai pemisah)</p>
                        </div>
                        <div class="flex gap-2 mb-3">
                            <button type="button" onclick="updateFromLatLng()" class="flex-1 px-3 py-2 bg-blue-600 text-white text-xs font-semibold rounded-lg hover:bg-blue-700 transition-colors">
                                Perbarui Peta
                            </button>
                            <button type="button" onclick="getCurrentLocation()" class="flex-1 px-3 py-2 bg-green-600 text-white text-xs font-semibold rounded-lg hover:bg-green-700 transition-colors">
                                Lokasi GPS Saya
                            </button>
                        </div>
                        <div class="mb-3 p-3 bg-gray-100 rounded-lg">
                            <p class="text-xs text-gray-700 font-semibold">Koordinat Saat Ini:</p>
                            <p id="currentCoord" class="text-sm font-mono text-gray-900 mt-1">-6.2088, 106.8456</p>
                        </div>
                        <input type="hidden" id="koordinat" name="koordinat" placeholder="-6.2088,106.8456">
                        <div id="map" class="h-96 w-full rounded-lg border border-gray-200"></div>
                    </div>

                    <div>
                        <label class="block mb-2 text-sm font-medium">Jarak ke Tujuan (km)</label>
                        <input type="number" step="0.01" name="jarak_tujuan" placeholder="Contoh: 2.5" required class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm bg-gray-50 focus:outline-none focus:border-black focus:bg-white transition-colors">
                    </div>
                </div>
            </div>

            <!-- Form Card 2: Data Core Fiber -->
            <div class="bg-white border border-gray-200 rounded-xl p-8 mb-8 shadow-sm">
                <div>
                    <h3 class="text-lg font-semibold mb-5 border-b border-gray-200 pb-2">Data Core Fiber</h3>
                    <div id="tabel_core"></div>
                </div>

                <!-- Form Actions -->
                <div class="flex gap-3 justify-end mt-6 pt-5 border-t border-gray-200">
                    <button type="submit" class="px-6 py-3 bg-blue-900 text-white font-semibold text-sm rounded-lg hover:bg-gray-800 transition-colors">
                        Simpan Closure
                    </button>
                    <a href="dashboard.php" class="px-6 py-3 bg-white text-black border border-black font-semibold text-sm rounded-lg hover:bg-black hover:text-white transition-colors text-center">
                        Batal
                    </a>
                </div>
            </div>
        </form>
    </div>
</body>

</html>