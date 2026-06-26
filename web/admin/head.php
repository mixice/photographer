<?php
if (!file_exists(dirname(__DIR__) . '/install.lock')) { header('Location: ../install.php'); exit(); }
require_once('../db.php');

if (isset($_GET['action']) && $_GET['action'] == 'logout') {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit();
}

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit();
}

$settings = getSettings($conn);
$admin_name = $settings['account'] ?? 'admin';
$site_title = htmlspecialchars($settings['title'] ?? 'Admin', ENT_QUOTES, 'UTF-8');
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
<title>Admin</title>

<link rel="shortcut icon" href="../images/ico.svg">
<link rel="stylesheet" href="//ui.gg/lib/uigg.css">
<link rel="stylesheet" href="//ui.gg/uigg/admin/styles/admin.css">
<link rel="stylesheet" href="styles/styles.css">

<script src="//ui.gg/lib/editor/editor.js"></script>
<script type="module" src="//ui.gg/lib/uigg.js"></script>
<script type="module" src="js/admin.js"></script>
</head>

<body>
<load></load>
<section class="admin anime-fade-in flex flex-column">
    <!------------------------------------------head start------------------------------------------>
    <section class="head center">
        <a href="/admin/"><h1 class="logo center anime-zoom-in"><i></i><span>mix<em>ice</em></span></h1></a>
        <div class="head-cont center anime-fade-in"><a class="btn sider-toggle"><i class="ico ico-menu"></i></a></div>
        <u></u>
        <div class="head-cont center anime-fade-in">
            <div class="head-info center">
                <span>welcome<em><?php echo htmlspecialchars($admin_name); ?></em></span>
                <a class="fullscreen co-orange"></a>
                <a href="?action=logout" class="ico ico-close co-red"></a>
            </div>
        </div>
    </section>
    <!------------------------------------------head end------------------------------------------>
    <section class="subject">
        <!------------------------------------------sider start------------------------------------------>
        <section class="sider">
            <div class="sider-search"><input type="text"><button class="ico ico-search"></button></div>
            <fold>
                <h6>basic</h6>
                <fold-group>
                    <fold-title><a href="index.php"><i class="ico ico-home"></i>home</a></fold-title>
                </fold-group>
                <fold-group>
                    <fold-title><a href="set.php"><i class="ico ico-set"></i>setting</a></fold-title>
                </fold-group>
                <h6>integral</h6>
                <fold-group>
                    <fold-title><a href="photography.php"><i class="ico ico-image"></i>photography</a></fold-title>
                </fold-group>
                <fold-group>
                    <fold-title><a href="standpoint.php"><i class="ico ico-emot"></i>standpoint</a></fold-title>
                </fold-group>
                <fold-group>
                    <fold-title><a href="page.php"><i class="ico ico-document"></i>page</a></fold-title>
                </fold-group>
                <h6>other</h6>
                <fold-group>
                    <fold-title><a href="comment.php"><i class="ico ico-talk-info"></i>comment</a></fold-title>
                </fold-group>
                <fold-group>
                    <fold-title><a href="?action=logout"><i class="ico ico-arrow-out"></i>logout</a></fold-title>
                </fold-group>
            </fold>
        </section>
        <!------------------------------------------sider end------------------------------------------>
        <section class="clause">