<?php
session_start();
require_once('../db.php');
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $input_account = trim($_POST['account']);
    $input_password = $_POST['password'];

    $sql = "SELECT account, password FROM settings LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if ($input_account === $row['account'] && password_verify($input_password, $row['password'])) {
            $_SESSION['loggedin'] = true;
            $_SESSION['username'] = $row['account'];
            unset($_SESSION['error']);
            echo "<script>location.href='index.php'</script>";
            exit();
        } else {
            $_SESSION['error'] = 1;
        }
    } else {
        $_SESSION['error'] = 1;
    }
    $stmt->close();
}

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
<link rel="stylesheet" href="styles/admin.css">

<script src="//ui.gg/lib/jquery.min.js"></script>
<script src="//ui.gg/lib/uigg.js"></script>
<script src="js/admin.js"></script>
</head>

<body>
<load></load>
<section class="login center">
    <div class="login-main anime-zoom-in">
        <div class="login-title"><h1 class="logo center"><i></i><span>mix<em>ice</em></span></h1></div>
        <div class="login-cont">
            <div class="form login-form">
                <form method="POST">
                    <li><i class="ico ico-user"></i><input type="text" name="account" required></li>
                    <li><i class="ico ico-password"></i><div class="input"><input type="password" name="password" required><o class="password"></o></div></li>
                    <div class="bloomer" <?php if(isset($_SESSION['error'])){echo 'show';unset($_SESSION['error']);} ?>>Account and password is error</div>
                    <li><button type="submit" class="btn">login</button></li>
                </form>
            </div>
        </div>
    </div>
</section>

</body>
</html>
