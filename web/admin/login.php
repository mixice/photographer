<?php
if (!file_exists(dirname(__DIR__) . '/install.lock')) { header('Location: ../install.php'); exit(); }
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
            session_regenerate_id(true);
            $_SESSION['loggedin'] = true;
            $_SESSION['username'] = $row['account'];
            unset($_SESSION['error']);
            header("Location: index.php");
            exit();
        } else {
            $login_error = true;
        }
    } else {
        $login_error = true;
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
<link rel="stylesheet" href="//ui.gg/uigg/admin/styles/admin.css">
<link rel="stylesheet" href="styles/styles.css">

<script type="module" src="//ui.gg/lib/uigg.js"></script>
<script type="module" src="js/admin.js"></script>
</head>

<body>
<load></load>
<section class="login center">
    <div class="login-main anime-zoom-in">
        <div class="login-title"><h1 class="logo center"><i></i><span>mix<em>ice</em></span></h1></div>
        <div class="login-cont">
            <form class="form login-form" method="POST">
                <item><i class="ico ico-user"></i><cont><input type="text" name="account" required></cont></item>
                <item><i class="ico ico-password"></i><cont><div class="input"><input type="password" name="password" required><o class="password"></o></div></cont></item>
                <item><cont><button type="submit" class="btn">login</button></cont></item>
            </form>
        </div>
    </div>
</section>

<?php if(isset($login_error)): ?>
<script type="module">
if (window.Uigg) Uigg.alert('Account and password is error')
</script>
<?php endif; ?>

</body>
</html>
