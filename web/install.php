<?php
$lock = __DIR__ . '/install.lock';
if (file_exists($lock)) { header('Location: index.php'); exit(); }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $host = trim($_POST['host']);
    $name = trim($_POST['name']);
    $user = trim($_POST['user']);
    $pass = isset($_POST['pass']) ? trim($_POST['pass']) : '';
    $title = trim($_POST['title']);
    $account = trim($_POST['account']);
    $pwd = trim($_POST['pwd']);

    if (empty($host) || empty($name) || empty($user) || empty($title) || empty($account) || empty($pwd)) {
        $error = 'All fields are required !';
    } elseif (!file_exists(__DIR__ . '/database.sql')) {
        $error = 'database.sql not found !';
    } else {
        mysqli_report(MYSQLI_REPORT_OFF);
        $conn = @new mysqli($host, $user, $pass);
        if ($conn->connect_error) {
            $error = 'Connection failed !';
        } else {
            $conn->query("CREATE DATABASE IF NOT EXISTS `$name` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
            $conn->select_db($name);

            // import
            $sql = file_get_contents(__DIR__ . '/database.sql');
            $sql = preg_replace("/CREATE DATABASE[^;]*;/i", '', $sql);
            $sql = preg_replace("/USE\s+\S+;/i", '', $sql);
            $conn->multi_query($sql);
            while ($conn->next_result()) { if ($r = $conn->store_result()) $r->free(); }

            // default settings
            $hash = password_hash($pwd, PASSWORD_DEFAULT);
            $conn->query("INSERT INTO settings (title, account, password) VALUES ('" . $conn->real_escape_string($title) . "', '" . $conn->real_escape_string($account) . "', '$hash')");
            $conn->close();

            // write db.php
            $db = file_get_contents(__DIR__ . '/db.php');
            $db = preg_replace('/\$servername\s*=\s*"[^"]*"/', '$servername = "' . addcslashes($host, '"\\') . '"', $db);
            $db = preg_replace('/\$username\s*=\s*"[^"]*"/', '$username = "' . addcslashes($user, '"\\') . '"', $db);
            $db = preg_replace('/\$password\s*=\s*"[^"]*"/', '$password = "' . addcslashes($pass, '"\\') . '"', $db);
            $db = preg_replace('/\$dbname\s*=\s*"[^"]*"/', '$dbname = "' . addcslashes($name, '"\\') . '"', $db);
            file_put_contents(__DIR__ . '/db.php', $db);

            // lock
            file_put_contents($lock, date('Y-m-d H:i:s'));
            header('Location: index.php');
            exit();
        }
    }
}
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Install</title>

<link rel="shortcut icon" href="images/ico.svg">
<link rel="stylesheet" href="//ui.gg/lib/uigg.css">
<link rel="stylesheet" href="styles/styles.css">

<script type="module" src="//ui.gg/lib/uigg.js"></script>

</head>
<body>
<section class="install center">
    <section class="install-main">
        <div class="install-title center"><i class="ico ico-m"></i></div>
        <?php if ($error): ?><div class="bloomer"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
        <form method="POST" class="form">
            <reminder>This system supports PHP and MYSQL</reminder>
            <li><label>host</label><input type="text" name="host" value="localhost" required></li>
            <li><label>db name</label><input type="text" name="name" required></li>
            <li><label>db user</label><input type="text" name="user" required></li>
            <li><label>db password</label><input type="password" name="pass"></li>
            <li><label>title</label><input type="text" name="title" required></li>
            <li><label>account</label><input type="text" name="account" required></li>
            <li><label>password</label><div class="input"><input type="password" name="pwd" required><o class="password"></o></div></li>
            <li><button class="btn" type="submit">install</button></li>
        </form>
    </section>
</section>
</body>
</html>
