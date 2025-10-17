<?php
session_start();
include 'koneksi.php'; 

// 1. Pastikan pengguna sudah login sebagai admin
if (!isset($_SESSION['admin'])) {
    header("Location: index.php");
    exit;
}

// 2. Ambil ID closure dari URL
$id_closure = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Pastikan ID valid
if ($id_closure <= 0) {
    header("Location: dashboard.php");
    exit;
}

// Mulai transaksi untuk memastikan konsistensi data
mysqli_begin_transaction($conn);

try {
    // 3. Hapus data core terkait (Child records)
    // Walaupun idealnya database memiliki Foreign Key dengan ON DELETE CASCADE, 
    // melakukan penghapusan secara eksplisit di sini menjamin data bersih.
    $q_delete_core = "DELETE FROM core_warna WHERE id_closure = $id_closure";
    if (!mysqli_query($conn, $q_delete_core)) {
        throw new Exception("Gagal menghapus data core: " . mysqli_error($conn));
    }
    
    // 4. Hapus data closure utama (Parent record)
    $q_delete_closure = "DELETE FROM closure WHERE id_closure = $id_closure";
    if (!mysqli_query($conn, $q_delete_closure)) {
        throw new Exception("Gagal menghapus data closure: " . mysqli_error($conn));
    }

    // 5. Commit transaksi jika semua berhasil
    mysqli_commit($conn);

    // 6. Redirect kembali ke dashboard dengan pesan sukses
    // Anda bisa menambahkan parameter GET untuk notifikasi sukses jika diinginkan.
    header("Location: dashboard.php?status=deleted");
    exit;

} catch (Exception $e) {
    // 7. Rollback transaksi jika terjadi kesalahan
    mysqli_rollback($conn);
    
    // Tampilkan pesan error dan redirect
    echo "<script>
            alert('Proses penghapusan gagal: " . $e->getMessage() . "');
            window.location.href='dashboard.php';
          </script>";
    exit;
}

// Menutup koneksi (opsional, tergantung pada bagaimana 'koneksi.php' diimplementasikan)
mysqli_close($conn);

?>