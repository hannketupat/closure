<?php
session_start();
// Assuming koneksi.php establishes $conn and handles connection errors
include 'koneksi.php';

// Check if $conn is successfully established before proceeding
if (!isset($conn)) {
    die("Error: Error koneksi database.");
}

$error = null;

if (isset($_POST['login'])) {
    $u = trim($_POST['username']);
    $rawPassword = $_POST['password'];

    // 1. Prepare and execute statement to retrieve user
    $stmt = mysqli_prepare($conn, "SELECT id_admin, username, password FROM admin WHERE username = ?");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "s", $u);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($result && mysqli_num_rows($result) === 1) {
            $user = mysqli_fetch_assoc($result);
            $stored = isset($user['password']) ? $user['password'] : '';
            $ok = false;

            // 2. Check Modern Hash (Preferred)
            if ($stored && (strpos($stored, '$2y$') === 0 || strpos($stored, '$2a$') === 0 || strpos($stored, '$argon2') === 0)) {
                if (password_verify($rawPassword, $stored)) {
                    $ok = true;
                }
            }

            // 3. Check Legacy MD5 Hash (Migration needed)
            if (!$ok && $stored && $stored === md5($rawPassword)) {
                $ok = true;
                // Migrate password to modern hash
                if (isset($user['id'])) {
                    $newHash = password_hash($rawPassword, PASSWORD_DEFAULT);
                    $up = mysqli_prepare($conn, "UPDATE admin SET password = ? WHERE id = ?");
                    if ($up) {
                        mysqli_stmt_bind_param($up, "si", $newHash, $user['id']);
                        mysqli_stmt_execute($up);
                        mysqli_stmt_close($up);
                    }
                }
            }

            // 4. Check Plaintext (Worst-case legacy, Migration needed)
            if (!$ok && $stored && $stored === $rawPassword) {
                $ok = true;
                // Migrate password to modern hash
                if (isset($user['id'])) {
                    $newHash = password_hash($rawPassword, PASSWORD_DEFAULT);
                    $up = mysqli_prepare($conn, "UPDATE admin SET password = ? WHERE id = ?");
                    if ($up) {
                        mysqli_stmt_bind_param($up, "si", $newHash, $user['id']);
                        mysqli_stmt_execute($up);
                        mysqli_stmt_close($up);
                    }
                }
            }

            if ($ok) {
                // Authentication successful
                unset($user['password']); // Never store password hash in session
                $_SESSION['admin'] = $user;
                // Close DB connection before redirect
                mysqli_close($conn); 
                header("Location: dashboard.php");
                exit;
            } else {
                $error = "Username atau password salah!";
            }
        } else {
            $error = "Username atau password salah!";
        }
        mysqli_stmt_close($stmt);
    } else {
        // Handle database preparation error
        $error = "Terjadi kesalahan internal. Silakan coba lagi.";
    }

    // Close DB connection if not already closed by successful redirect
    mysqli_close($conn); 
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistem Manajemen Closure</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    
    <script src="https://cdn.tailwindcss.com"></script>
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
            /* Latar belakang dengan warna utama dashboard, namun sedikit lebih terang */
            background-color: #0d2a63; /* Deep Blue, sedikit diubah dari #0a2353 */
            background-image: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
        }
        /* Aksen biru terang untuk tombol dan focus */
        .accent-blue {
             background-color: #1e3c72; 
        }
        .accent-blue:hover {
             background-color: #102c79ff; 
        }
        .input-focus-blue:focus {
            outline: none;
            border-color: #2563eb !important; 
            box-shadow: 0 0 0 1px #2563eb; 
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4">
    
    <div class="w-full max-w-md bg-white rounded-lg shadow-xl p-8 sm:p-10 border border-gray-100">
        
        <div class="text-center mb-8">
            <img 
                src="assets/rafateklogo.jpeg" 
                alt="Logo PT. Rafa Teknologi Solusi" 
                class="mx-auto h-12 mb-4" 
            /> 
            
            <h1 class="text-2xl font-bold text-gray-900 mb-1">
                Sistem Manajemen Closure
            </h1>
            <p class="text-sm text-gray-500">
                PT. Rafa Teknologi Solusi
            </p>
        </div>
        
        <?php if(isset($error)): ?>
            <div class="bg-red-50 border border-red-300 text-red-700 p-3 rounded-md mb-6 text-sm">
                <p><?= htmlspecialchars($error) ?></p>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-4">
                <label class="block text-gray-700 font-medium text-sm mb-1" for="username">Username</label>
                <input 
                    id="username"
                    type="text" 
                    name="username" 
                    required 
                    autocomplete="username"
                    value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md text-base transition-all duration-200 input-focus-blue placeholder-gray-400"
                    placeholder="Masukkan Username"
                >
            </div>

            <div class="mb-6">
                <label class="block text-gray-700 font-medium text-sm mb-1" for="password">Password</label>
                <input 
                    id="password"
                    type="password" 
                    name="password" 
                    required 
                    autocomplete="current-password"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md text-base transition-all duration-200 input-focus-blue placeholder-gray-400"
                    placeholder="Masukkan Password"
                >
            </div>

            <button 
                type="submit" 
                name="login"
                class="w-full py-2 accent-blue text-white font-semibold rounded-md text-base 
                       transition-colors duration-200 hover:shadow-lg hover:shadow-blue-500/30 active:scale-[0.99] focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-opacity-50"
            >
                LOGIN
            </button>
        </form>
    </div>
</body>
</html>