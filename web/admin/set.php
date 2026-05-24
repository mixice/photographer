<?php
include ('head.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $account = trim($_POST['account']);
    $password = trim($_POST['password']);
    $home_ticket = trim($_POST['home_ticket']);
    $photography_ticket = trim($_POST['photography_ticket']);
    $standpoint_ticket = trim($_POST['standpoint_ticket']);
    $comment_ticket = trim($_POST['comment_ticket']);

    $sql = "UPDATE settings SET title=?, description=?, account=?, home_ticket=?, photography_ticket=?, standpoint_ticket=?, comment_ticket=?";
    $params = [$title, $description, $account, $home_ticket, $photography_ticket, $standpoint_ticket, $comment_ticket];
    $types = "sssssss";

    if (!empty($password)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $sql .= ", password=?";
        $params[] = $hash;
        $types .= "s";
    }

    $sql .= " WHERE id=1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $stmt->close();

    $settings = getSettings($conn, true);
    $saved = true;
} elseif (!isset($settings)) {
    $settings = getSettings($conn);
}
?>

<div class="title"><h4>setting</h4></div>
<div class="contant">
    <div class="title"><h5>site setting</h5></div>
    <div class="item">
        <section class="form">
            <form method="POST" id="setForm">
                <li><span>title</span><input class="wide-30" type="text" name="title" value="<?php echo htmlspecialchars($settings['title'] ?? ''); ?>" required></li>
                <li><span>description</span><input class="wide-70" type="text" name="description" value="<?php echo htmlspecialchars($settings['description'] ?? ''); ?>"></li>
                <li><span>account</span><input class="wide-20" type="text" name="account" value="<?php echo htmlspecialchars($settings['account'] ?? ''); ?>" required></li>
                <li><span>password</span><div class="input wide-20"><input type="password" name="password" placeholder="leave blank to keep" autocomplete="new-password"><o class="password"></o></div></li>
                <li><span>home ticket</span><input class="wide-70" type="text" name="home_ticket" value="<?php echo htmlspecialchars($settings['home_ticket'] ?? ''); ?>" required></li>
                <li><span>photography ticket</span><input class="wide-70" type="text" name="photography_ticket" value="<?php echo htmlspecialchars($settings['photography_ticket'] ?? ''); ?>" required></li>
                <li><span>standpoint ticket</span><input class="wide-70" type="text" name="standpoint_ticket" value="<?php echo htmlspecialchars($settings['standpoint_ticket'] ?? ''); ?>" required></li>
                <li><span>comment ticket</span><input class="wide-70" type="text" name="comment_ticket" value="<?php echo htmlspecialchars($settings['comment_ticket'] ?? ''); ?>" required></li>
                <li class="resolve"><button type="submit" class="btn btn-submit">submit</button></li>
            </form>
            <?php if(!empty($saved)): ?>
            <script>alert('Saved successfully !')</script>
            <?php endif; ?>
        </section>
    </div>
</div>

</section>
</section>
</section>
</body>
</html>
