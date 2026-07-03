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
    echo json_encode(['success' => true], JSON_UNESCAPED_SLASHES);
    exit();
} elseif (!isset($settings)) {
    $settings = getSettings($conn);
}
?>

<div class="title"><h4>setting</h4></div>
<div class="contant">
    <div class="title"><h5>site setting</h5></div>
    <div class="item">
        <form class="form" id="setForm" auto>
            <item><alia>title</alia>
                <cont><input class="wide-30" type="text" name="title" value="<?php echo htmlspecialchars($settings['title'] ?? ''); ?>" required></cont>
            </item>
            <item><alia>description</alia>
                <cont><input class="wide-70" type="text" name="description" value="<?php echo htmlspecialchars($settings['description'] ?? ''); ?>"></cont>
            </item>
            <item><alia>account</alia>
                <cont><input class="wide-20" type="text" name="account" value="<?php echo htmlspecialchars($settings['account'] ?? ''); ?>" required></cont>
            </item>
            <item><alia>password</alia>
                <cont><div class="input wide-20"><input type="password" name="password" placeholder="leave blank to keep" autocomplete="new-password"><o class="password"></o></div></cont>
            </item>
            <item><alia>home ticket</alia>
                <cont><input class="wide-70" type="text" name="home_ticket" value="<?php echo htmlspecialchars($settings['home_ticket'] ?? ''); ?>" required></cont>
            </item>
            <item><alia>photography ticket</alia>
                <cont><input class="wide-70" type="text" name="photography_ticket" value="<?php echo htmlspecialchars($settings['photography_ticket'] ?? ''); ?>" required></cont>
            </item>
            <item><alia>standpoint ticket</alia>
                <cont><input class="wide-70" type="text" name="standpoint_ticket" value="<?php echo htmlspecialchars($settings['standpoint_ticket'] ?? ''); ?>" required></cont>
            </item>
            <item><alia>comment ticket</alia>
                <cont><input class="wide-70" type="text" name="comment_ticket" value="<?php echo htmlspecialchars($settings['comment_ticket'] ?? ''); ?>" required></cont>
            </item>
            <item><alia></alia>
                <cont><a class="btn" submit>submit</a></cont>
            </item>
        </form>
    </div>
</div>

<script type="module">
    const { $, alert } = Uigg
    ready(() => {
        const f = $('#setForm')
        f.onSubmit = async (data) => {
            const resp = await fetch('set.php', { method: 'POST', body: f.toFormData() })
            const result = await resp.json().catch(() => ({ error: 'Server error' }))
            if (result.success) alert('Saved successfully !')
            else alert(result.error || 'Failed')
        }
    })
</script>

</section>
</section>
</section>
</body>
</html>
