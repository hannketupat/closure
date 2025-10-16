<?php
session_start();
include 'koneksi.php';
if (!isset($_SESSION['admin'])) header("Location: index.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kode = mysqli_real_escape_string($conn, $_POST['kode_closure']);
    $nama = mysqli_real_escape_string($conn, $_POST['nama_closure']);
    $jenis = mysqli_real_escape_string($conn, $_POST['jenis_kabel']);
    $alamat = mysqli_real_escape_string($conn, $_POST['alamat_fisik']);
    $koordinat = mysqli_real_escape_string($conn, $_POST['koordinat']);
    $jarak = mysqli_real_escape_string($conn, $_POST['jarak_tujuan']);

    $query = "INSERT INTO closure (kode_closure, nama_closure, jenis_kabel, alamat_fisik, koordinat, jarak_tujuan)
              VALUES ('$kode','$nama','$jenis','$alamat','$koordinat','$jarak')";
    
    if (mysqli_query($conn, $query)) {
        $id_closure = mysqli_insert_id($conn);

        if (isset($_POST['warna_core']) && isset($_POST['tujuan_core'])) {
            $warna = $_POST['warna_core'];
            $tujuan = $_POST['tujuan_core'];

            for ($i = 0; $i < count($warna); $i++) {
                $w = mysqli_real_escape_string($conn, $warna[$i]);
                $t = mysqli_real_escape_string($conn, $tujuan[$i]);
                mysqli_query($conn, "INSERT INTO core_warna (id_closure, warna_core, tujuan_core) VALUES ('$id_closure','$w','$t')");
            }
        }

        header("Location: detail_closure.php?id=$id_closure");
    } else {
        echo "Error: " . mysqli_error($conn);
    }
} else {
    header("Location: dashboard.php");
}
?>