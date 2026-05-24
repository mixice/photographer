<?php
if (!file_exists(__DIR__ . '/install.lock')) { header('Location: install.php'); exit(); }
require_once('db.php');
date_default_timezone_set('PRC');
header("Content-type: text/html; charset=utf-8");
error_reporting(0);

$settings = getSettings($conn);
$site_title = htmlspecialchars($settings['title'] ?? 'MIXICE', ENT_QUOTES, 'UTF-8');
$script = basename($_SERVER['SCRIPT_NAME']);
$slug = $_GET['slug'] ?? '';
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<meta http-equiv="window-target" content="_top">
<meta name="keywords" content="">
<meta name="description" content="">
<meta name="author" content="">
<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
<meta name="google" content="notranslate">
<title><?php echo $site_title; ?> Photographer</title>

<link rel="shortcut icon" href="images/ico.svg">
<link rel="stylesheet" href="//ui.gg/lib/swiper-bundle.min.css">
<link rel="stylesheet" href="//ui.gg/lib/uigg.css">
<link rel="stylesheet" href="styles/styles.css">

<script src="//ui.gg/lib/jquery.min.js"></script>
<script src="//ui.gg/lib/swiper-bundle.min.js"></script>
<script src="//ui.gg/lib/uigg.js"></script>
</head>

<body>
<load></load>
<section class="head center anime-fade-in-down">
    <a href="/" class="logo anime-fade-in"><i class="ico ico-m"></i></a>
    <h1 class="head-cont"><?php echo $site_title; ?> photographer</h1>
    <u></u>
    <menu>
        <menu-cont>
            <li><a class="line<?php echo $script === 'index.php' ? ' active' : ''; ?>" href="index.php">home</a></li>
            <li><a class="line<?php echo in_array($script, ['photography.php', 'album.php']) ? ' active' : ''; ?>" href="photography.php">photography</a></li>
            <li><a class="line<?php echo $script === 'standpoint.php' ? ' active' : ''; ?>" href="standpoint.php">standpoint</a></li>
            <?php $pages = $conn->query("SELECT id, title, slug FROM page ORDER BY id ASC"); ?>
            <?php while ($p = $pages->fetch_assoc()): ?>
            <li><a class="line<?php echo ($script === 'page.php' && $slug === $p['slug']) ? ' active' : ''; ?>" href="page.php?slug=<?php echo htmlspecialchars($p['slug']); ?>"><?php echo htmlspecialchars($p['title']); ?></a></li>
            <?php endwhile; ?>
        </menu-cont>
    </menu>
</section>
