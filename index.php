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
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
            max-width: 900px;
            width: 100%;
            display: flex;
        }

        .login-left {
            flex: 1;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 60px 40px;
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .login-left h1 {
            font-size: 32px;
            margin-bottom: 20px;
            font-weight: 700;
        }

        .login-left p {
            font-size: 16px;
            line-height: 1.6;
            opacity: 0.9;
        }

        .fiber-icon {
            width: 80px;
            height: 80px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            margin-bottom: 30px;
        }

        .login-right {
            flex: 1;
            padding: 60px 40px;
        }

        .login-right h2 {
            font-size: 28px;
            margin-bottom: 10px;
            color: #333;
        }

        .login-right .subtitle {
            color: #666;
            margin-bottom: 40px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
            font-size: 14px;
        }

        .form-group input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 15px;
            transition: all 0.3s;
        }

        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .btn-login {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }

        .alert-error {
            background: #fee;
            color: #c33;
            padding: 12px 16px;
            border-radius: 10px;
            margin-bottom: 20px;
            border-left: 4px solid #c33;
        }

        @media (max-width: 768px) {
            .login-container {
                flex-direction: column;
            }
            .login-left {
                padding: 40px 30px;
            }
            .login-right {
                padding: 40px 30px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-left">
            <div class="fiber-icon">🔌</div>
            <h1>Sistem Manajemen Closure</h1>
            <p>Platform terintegrasi untuk mengelola data closure fiber optic, monitoring core kabel, dan dokumentasi infrastruktur jaringan.</p>
        </div>
        <div class="login-right">
            <h2>Selamat Datang</h2>
            <p class="subtitle">Silakan login untuk melanjutkan</p>
            
            <?php if(isset($error)): ?>
                <div class="alert-error"><?= $error ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" required autocomplete="username">
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required autocomplete="current-password">
                </div>
                <button type="submit" name="login" class="btn-login">Login</button>
            </form>
        </div>
    </div>
</body>
</html>