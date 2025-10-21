<?php
session_start();
include 'koneksi.php'; 

if (!isset($_SESSION['admin'])) {
    header("Location: index.php");
    exit;
}

$id_closure = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id_closure <= 0) {
    header("Location: dashboard.php");
    exit;
}

mysqli_begin_transaction($conn);

try {
    $q_delete_core = "DELETE FROM core_warna WHERE id_closure = $id_closure";
    if (!mysqli_query($conn, $q_delete_core)) {
        throw new Exception("Gagal menghapus data core: " . mysqli_error($conn));
    }
    
    $q_delete_closure = "DELETE FROM closure WHERE id_closure = $id_closure";
    if (!mysqli_query($conn, $q_delete_closure)) {
        throw new Exception("Gagal menghapus data closure: " . mysqli_error($conn));
    }

    mysqli_commit($conn);

    header("Location: dashboard.php?status=deleted");
    exit;

} catch (Exception $e) {
    mysqli_rollback($conn);
    
    echo "<script>
            alert('Proses penghapusan gagal: " . $e->getMessage() . "');
            window.location.href='dashboard.php';
          </script>";
    exit;
}

mysqli_close($conn);

?>