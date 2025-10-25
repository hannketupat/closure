<?php
session_start();
include 'koneksi.php';
if (!isset($_SESSION['admin'])) header("Location: index.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitasi input
    $kode = mysqli_real_escape_string($conn, $_POST['kode_closure']);
    $nama = mysqli_real_escape_string($conn, $_POST['nama_closure']);
    $jenis = mysqli_real_escape_string($conn, $_POST['jenis_kabel']);
    $alamat = mysqli_real_escape_string($conn, $_POST['alamat_fisik']);
    $koordinat = mysqli_real_escape_string($conn, $_POST['koordinat']);
    $jarak = mysqli_real_escape_string($conn, $_POST['jarak_tujuan']);

    // Query insert utama closure
    $query = "
        INSERT INTO closure 
            (kode_closure, nama_closure, jenis_kabel, alamat_fisik, koordinat, jarak_tujuan, updated_at)
        VALUES 
            ('$kode', '$nama', '$jenis', '$alamat', '$koordinat', '$jarak', NOW())
    ";

    if (mysqli_query($conn, $query)) {
        $id_closure = mysqli_insert_id($conn);

        // Insert core fiber jika tersedia
        if (!empty($_POST['warna_core']) && !empty($_POST['tujuan_core'])) {
            $warna = $_POST['warna_core'];
            $tujuan = $_POST['tujuan_core'];

            for ($i = 0; $i < count($warna); $i++) {
                $w = mysqli_real_escape_string($conn, $warna[$i]);
                $t = mysqli_real_escape_string($conn, $tujuan[$i]);

                $insert_core = "
                    INSERT INTO core_warna (id_closure, warna_core, tujuan_core)
                    VALUES ('$id_closure', '$w', '$t')
                ";
                mysqli_query($conn, $insert_core);
            }
        }

        // Redirect ke halaman detail setelah sukses
        header("Location: detail_closure.php?id=$id_closure");
        exit;
    } else {
        // Error handling yang lebih aman dan informatif
        echo "<h3 style='color:red;font-family:monospace;'>Gagal menyimpan data closure:</h3>";
        echo "<pre>" . htmlspecialchars(mysqli_error($conn)) . "</pre>";
    }
} else {
    header("Location: dashboard.php");
    exit;
}
