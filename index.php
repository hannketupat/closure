<?php
session_start();
include 'koneksi.php';

if (isset($_POST['login'])) {
    $u = trim($_POST['username']);
    $rawPassword = $_POST['password'];

    $stmt = mysqli_prepare($conn, "SELECT * FROM admin WHERE username = ?");
    mysqli_stmt_bind_param($stmt, "s", $u);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($result && mysqli_num_rows($result) === 1) {
        $user = mysqli_fetch_assoc($result);
        $stored = isset($user['password']) ? $user['password'] : '';
        $ok = false;

        if ($stored && (strpos($stored, '$2y$') === 0 || strpos($stored, '$2a$') === 0 || strpos($stored, '$argon2') === 0)) {
            if (password_verify($rawPassword, $stored)) {
                $ok = true;
            }
        }

        if (!$ok && $stored && $stored === md5($rawPassword)) {
            $ok = true;
            if (isset($user['id'])) {
                $newHash = password_hash($rawPassword, PASSWORD_DEFAULT);
                $up = mysqli_prepare($conn, "UPDATE admin SET password = ? WHERE id = ?");
                mysqli_stmt_bind_param($up, "si", $newHash, $user['id']);
                mysqli_stmt_execute($up);
            }
        }

        if (!$ok && $stored && $stored === $rawPassword) {
            $ok = true;
            if (isset($user['id'])) {
                $newHash = password_hash($rawPassword, PASSWORD_DEFAULT);
                $up = mysqli_prepare($conn, "UPDATE admin SET password = ? WHERE id = ?");
                mysqli_stmt_bind_param($up, "si", $newHash, $user['id']);
                mysqli_stmt_execute($up);
            }
        }

        if ($ok) {
            unset($user['password']);
            $_SESSION['admin'] = $user;
            header("Location: dashboard.php");
            exit;
        } else {
            $error = "Username atau password salah!";
        }
    } else {
        $error = "Username atau password salah!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistem Manajemen Closure Fiber Optic</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-white to-purple-100 min-h-screen flex items-center justify-center p-5">
    <!-- Login Container -->
    <div class="bg-white rounded-3xl shadow-2xl overflow-hidden max-w-4xl w-full flex">
        
        <!-- Left Section - Branding -->
        <div class="hidden md:flex md:w-1/2 bg-gradient-to-br from-blue-900 to-purple-950 text-white flex-col justify-center items-center p-16">
            <h1 class="text-4xl font-bold mb-5">Sistem Manajemen Closure</h1>
            <p class="text-lg opacity-90">PT. Rafa Teknologi Solusi</p>
        </div>

        <!-- Right Section - Login Form -->
        <div class="w-full md:w-1/2 p-16">
            <h2 class="text-3xl font-bold text-gray-900 mb-3">Selamat Datang</h2>
            <p class="text-gray-600 mb-10">Silakan login untuk melanjutkan</p>
            
            <?php if(isset($error)): ?>
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 px-4 py-3 rounded mb-6">
                    <?= $error ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <!-- Username Input -->
                <div class="mb-6">
                    <label class="block text-gray-700 font-semibold text-sm mb-2">Username</label>
                    <input 
                        type="text" 
                        name="username" 
                        required 
                        autocomplete="username"
                        class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl text-base transition-all duration-300 focus:outline-none focus:border-blue-600 focus:ring-4 focus:ring-blue-100"
                    >
                </div>

                <!-- Password Input -->
                <div class="mb-8">
                    <label class="block text-gray-700 font-semibold text-sm mb-2">Password</label>
                    <input 
                        type="password" 
                        name="password" 
                        required 
                        autocomplete="current-password"
                        class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl text-base transition-all duration-300 focus:outline-none focus:border-blue-600 focus:ring-4 focus:ring-blue-100"
                    >
                </div>

                <!-- Login Button -->
                <button 
                    type="submit" 
                    name="login"
                    class="w-full py-3 bg-gradient-to-r from-blue-900 to-purple-950 text-white font-bold rounded-xl text-base transition-all duration-300 hover:shadow-lg hover:translate-y-[-2px]"
                >
                    Login
                </button>
            </form>
        </div>
    </div>
</body>
</html>