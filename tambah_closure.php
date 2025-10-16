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
            position: sticky;
            top: 0;
            z-index: 100;
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
            max-width: 1000px;
            margin: 0 auto;
            padding: 30px 40px;
        }

        .progress-steps {
            display: flex;
            justify-content: space-between;
            margin-bottom: 40px;
            position: relative;
        }

        .progress-steps::before {
            content: '';
            position: absolute;
            top: 20px;
            left: 0;
            right: 0;
            height: 2px;
            background: #e0e0e0;
            z-index: 0;
        }

        .step {
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
            z-index: 1;
            flex: 1;
        }

        .step-circle {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: white;
            border: 3px solid #e0e0e0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            color: #999;
            margin-bottom: 8px;
        }

        .step.active .step-circle {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-color: #667eea;
            color: white;
        }

        .step-label {
            font-size: 13px;
            color: #999;
            font-weight: 500;
        }

        .step.active .step-label {
            color: #667eea;
            font-weight: 600;
        }

        .form-card {
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 25px;
        }

        .form-section {
            margin-bottom: 35px;
        }

        .form-section:last-child {
            margin-bottom: 0;
        }

        .form-section h3 {
            font-size: 20px;
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 2px solid #f0f0f0;
            display: flex;
            align-items: center;
            gap: 10px;
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

        .form-group label .required {
            color: #ef4444;
            margin-left: 3px;
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
            min-height: 90px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .helper-text {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }

        .core-preview-section {
            background: #f8f9fa;
            border: 2px solid #e0e0e0;
            border-radius: 15px;
            padding: 25px;
            margin-top: 25px;
        }

        .core-preview-section h4 {
            font-size: 16px;
            color: #333;
            margin-bottom: 20px;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .cores-preview-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(110px, 1fr));
            gap: 12px;
            margin-bottom: 25px;
        }

        .core-preview-box {
            background: white;
            border: 2px solid #ddd;
            border-radius: 10px;
            padding: 12px;
            text-align: center;
            transition: transform 0.2s;
        }

        .core-preview-box:hover {
            transform: translateY(-3px);
        }

        .core-color-dot {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            margin: 0 auto 8px;
            border: 2px solid #333;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }

        .core-preview-box .number {
            font-size: 11px;
            font-weight: 700;
            color: #333;
            margin-bottom: 6px;
            text-transform: uppercase;
        }

        .core-preview-box .color-name {
            font-size: 11px;
            color: #666;
            font-weight: 500;
        }

        .core-table-wrapper {
            overflow-x: auto;
        }

        .core-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .core-table th,
        .core-table td {
            padding: 14px;
            border: 1px solid #e0e0e0;
            text-align: left;
        }

        .core-table th {
            background: linear-gradient(to bottom, #f8f9fa, #e9ecef);
            font-weight: 600;
            color: #333;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .core-table td {
            background: white;
        }

        .core-table .core-number-cell {
            text-align: center;
            font-weight: 700;
            color: #667eea;
        }

        .core-table input {
            width: 100%;
            padding: 10px 14px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s;
        }

        .core-table input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .core-table input[readonly] {
            background: #f8f9fa;
            cursor: not-allowed;
            font-weight: 600;
        }

        .btn {
            padding: 14px 30px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
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
            padding-top: 25px;
            border-top: 2px solid #f0f0f0;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-info {
            background: #dbeafe;
            color: #1e40af;
            border-left: 4px solid #3b82f6;
        }

        #core_preview_card {
            display: none;
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
            .cores-preview-grid {
                grid-template-columns: repeat(auto-fill, minmax(90px, 1fr));
            }
            .form-actions {
                flex-direction: column;
            }
            .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
    <script>
        const colorMap = {
            'Biru': '#0066cc',
            'Oranye': '#ff6600',
            'Hijau': '#00cc44',
            'Coklat': '#8b4513',
            'Abu-abu': '#808080',
            'Putih': '#ffffff',
            'Merah': '#cc0000',
            'Hitam': '#000000',
            'Kuning': '#ffcc00',
            'Ungu': '#9933cc',
            'Merah Muda': '#ff69b4',
            'Aqua': '#00cccc'
        };

        function tampilkanWarna() {
            const jenis = document.getElementById("jenis_kabel").value;
            const jumlah = jenis === "12 core" ? 12 : jenis === "24 core" ? 24 : 0;
            const warna12 = ["Biru","Oranye","Hijau","Coklat","Abu-abu","Putih","Merah","Hitam","Kuning","Ungu","Merah Muda","Aqua"];
            
            if (jumlah === 0) {
                document.getElementById("core_preview_card").style.display = "none";
                return;
            }

            document.getElementById("core_preview_card").style.display = "block";

            // Update progress steps
            document.querySelector('.step:nth-child(2)').classList.add('active');

            // Preview visual cores
            let previewHTML = '';
            for (let i = 0; i < jumlah; i++) {
                const w = warna12[i % 12];
                const color = colorMap[w];
                const borderStyle = w === 'Putih' ? 'border: 2px solid #333;' : '';
                previewHTML += `
                    <div class="core-preview-box">
                        <div class="number">Core ${i + 1}</div>
                        <div class="core-color-dot" style="background-color: ${color}; ${borderStyle}"></div>
                        <div class="color-name">${w}</div>
                    </div>
                `;
            }
            document.getElementById("preview_cores").innerHTML = previewHTML;

            // Generate input table
            let tabelHTML = `
                <div class="core-table-wrapper">
                    <table class="core-table">
                        <thead>
                            <tr>
                                <th style="width: 100px;">No Core</th>
                                <th style="width: 180px;">Warna Core</th>
                                <th>Tujuan Core</th>
                            </tr>
                        </thead>
                        <tbody>
            `;
            
            for (let i = 0; i < jumlah; i++) {
                const w = warna12[i % 12];
                tabelHTML += `
                    <tr>
                        <td class="core-number-cell">Core ${i + 1}</td>
                        <td>
                            <input type="text" name="warna_core[]" value="${w}" readonly>
                        </td>
                        <td>
                            <input type="text" name="tujuan_core[]" placeholder="Masukkan tujuan core (contoh: ODP-001)">
                        </td>
                    </tr>
                `;
            }
            
            tabelHTML += `
                        </tbody>
                    </table>
                </div>
            `;
            document.getElementById("tabel_core").innerHTML = tabelHTML;

            // Smooth scroll to preview
            setTimeout(() => {
                document.getElementById("core_preview_card").scrollIntoView({ 
                    behavior: 'smooth', 
                    block: 'start' 
                });
            }, 100);
        }

        function validateForm(e) {
            const jenis = document.getElementById("jenis_kabel").value;
            if (!jenis) {
                e.preventDefault();
                alert('⚠️ Pilih jenis kabel terlebih dahulu!');
                return false;
            }
            
            // Update progress
            document.querySelector('.step:nth-child(3)').classList.add('active');
            return true;
        }
    </script>
</head>
<body>
    <div class="navbar">
        <a href="dashboard.php" class="back-btn">←</a>
        <h1>Tambah Closure Baru</h1>
    </div>



        <form action="proses_simpan.php" method="POST" onsubmit="return validateForm(event)">
            <div class="form-card">
                <div class="form-section">
                    <h3>
                        <span>📋</span>
                        Informasi Dasar Closure
                    </h3>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Kode Closure <span class="required">*</span></label>
                            <input type="text" name="kode_closure" placeholder="Contoh: CLS-001" required>
                            <div class="helper-text">Kode unik untuk identifikasi closure</div>
                        </div>
                        <div class="form-group">
                            <label>Nama Closure <span class="required">*</span></label>
                            <input type="text" name="nama_closure" placeholder="Contoh: Closure Jl. Sudirman" required>
                            <div class="helper-text">Nama atau lokasi closure</div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Jenis Kabel <span class="required">*</span></label>
                        <select name="jenis_kabel" id="jenis_kabel" onchange="tampilkanWarna()" required>
                            <option value="">-- Pilih Jenis Kabel --</option>
                            <option value="12 core">🔌 12 Core</option>
                            <option value="24 core">🔌 24 Core</option>
                        </select>
                        <div class="helper-text">Pilih jumlah core dalam kabel fiber optic</div>
                    </div>
                </div>

                <div class="form-section">
                    <h3>
                        <span>📍</span>
                        Lokasi & Jarak
                    </h3>
                    
                    <div class="form-group">
                        <label>Alamat Fisik <span class="required">*</span></label>
                        <textarea name="alamat_fisik" placeholder="Masukkan alamat lengkap lokasi closure..." required></textarea>
                        <div class="helper-text">Alamat lengkap untuk memudahkan pencarian fisik</div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Koordinat GPS</label>
                            <input type="text" name="koordinat" placeholder="Contoh: -6.971112,107.633221">
                            <div class="helper-text">Format: latitude,longitude</div>
                        </div>
                        <div class="form-group">
                            <label>Jarak ke Tujuan (km)</label>
                            <input type="number" name="jarak_tujuan" step="0.01" placeholder="Contoh: 2.5">
                            <div class="helper-text">Jarak dalam kilometer</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-card" id="core_preview_card">
                <div class="form-section">
                    <h3>
                        <span>🎨</span>
                        Preview Visual Core
                    </h3>
                    <div class="core-preview-section">
                        <h4>
                            <span>🔌</span>
                            Susunan Core Fiber Optic
                        </h4>
                        <div class="cores-preview-grid" id="preview_cores"></div>
                    </div>
                </div>

                <div class="form-section">
                    <h3>
                        <span>✏️</span>
                        Data Tujuan Core
                    </h3>
                    <div class="alert alert-info">
                        <span>💡</span>
                        <span>Isi tujuan untuk setiap core. Biarkan kosong jika core belum digunakan.</span>
                    </div>
                    <div id="tabel_core"></div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <span>💾</span>
                        Simpan Closure
                    </button>
                    <a href="dashboard.php" class="btn btn-secondary">
                        <span>✕</span>
                        Batal
                    </a>
                </div>
            </div>
        </form>
    </div>
</body>
</html>