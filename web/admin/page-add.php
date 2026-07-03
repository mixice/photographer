<?php
ob_start();
error_reporting(0);
require_once('../db.php');

$id = intval($_POST['id'] ?? $_GET['id'] ?? 0);

// slug check — before head.php
if (isset($_GET['check']) && $_GET['check']) {
    header('Content-Type: application/json');
    $slug = preg_replace('/[^a-z0-9\-]/', '', strtolower(trim($_GET['check'])));
    if (empty($slug)) { echo json_encode(['ok' => false]); exit; }
    $q = $conn->prepare("SELECT id FROM page WHERE slug = ?");
    $q->bind_param('s', $slug);
    $q->execute();
    $exists = $q->get_result()->num_rows > 0;
    if ($exists && $id) {
        $slug_stmt = $conn->prepare("SELECT slug FROM page WHERE id = ?");
        $slug_stmt->bind_param('i', $id);
        $slug_stmt->execute();
        $row = $slug_stmt->get_result()->fetch_assoc();
        if ($row && $row['slug'] === $slug) $exists = false;
    }
    echo json_encode(['ok' => !$exists]);
    exit;
}

// Auth
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        echo json_encode(['error' => 'Unauthorized'], JSON_UNESCAPED_SLASHES);
        exit;
    }
    header('Location: login.php');
    exit;
}

// POST — before head.php to keep JSON clean
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    ob_clean();
    $row = null;
    if ($id) {
        $row_stmt = $conn->prepare("SELECT * FROM page WHERE id = ?");
        $row_stmt->bind_param('i', $id);
        $row_stmt->execute();
        $row = $row_stmt->get_result()->fetch_assoc();
        $row_stmt->close();
    }

    $title = trim($_POST['title'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $content = $_POST['content'] ?? '';
    $comment_enabled = isset($_POST['comment_enabled']) && $_POST['comment_enabled'] === 'true' ? 1 : 0;
    $date_input = trim($_POST['created_at'] ?? '');
    if ($date_input) {
        if ($id && $row && $row['created_at']) {
            $time_part = substr($row['created_at'], 11, 8);
            $created_at = $date_input . ' ' . ($time_part ?: date('H:i:s'));
        } else {
            $created_at = $date_input . ' ' . date('H:i:s');
        }
    } else {
        $created_at = $id && $row && $row['created_at'] ? $row['created_at'] : date('Y-m-d H:i:s');
    }
    $slug = preg_replace('/[^a-z0-9\-]/', '', strtolower($slug));

    if (empty($title) || empty($slug)) {
        echo json_encode(['error' => 'Title and url are required !'], JSON_UNESCAPED_SLASHES);
        exit();
    } elseif ($id) {
        $content = moveTmpFiles($content, 'page');
        cleanUnusedFiles('', '', $row['content'] ?? '', $content);
        $stmt = $conn->prepare("UPDATE page SET title=?, slug=?, content=?, comment_enabled=?, created_at=? WHERE id=?");
        $stmt->bind_param("sssisi", $title, $slug, $content, $comment_enabled, $created_at, $id);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'redirect' => "page-add.php?id=$id&msg=saved"], JSON_UNESCAPED_SLASHES);
            exit();
        }
    } else {
        $content = moveTmpFiles($content, 'page');
        $stmt = $conn->prepare("INSERT INTO page (title, slug, content, comment_enabled, created_at) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssis", $title, $slug, $content, $comment_enabled, $created_at);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'redirect' => "page-add.php?id=" . $stmt->insert_id . "&msg=added"], JSON_UNESCAPED_SLASHES);
            exit();
        }
    }
    echo json_encode(['error' => 'Failed: ' . addslashes($conn->error)], JSON_UNESCAPED_SLASHES);
    exit();
}

include ('head.php');

// GET: query row for form display
$row = null;
if ($id) {
    $row_stmt = $conn->prepare("SELECT * FROM page WHERE id = ?");
    $row_stmt->bind_param('i', $id);
    $row_stmt->execute();
    $row = $row_stmt->get_result()->fetch_assoc();
    $row_stmt->close();
}

$title_val = htmlspecialchars($row['title'] ?? '', ENT_QUOTES, 'UTF-8');
$slug_val = htmlspecialchars($row['slug'] ?? '', ENT_QUOTES, 'UTF-8');
$content_val = $row['content'] ?? '';
$comment_enabled_bool = $row ? (bool)$row['comment_enabled'] : false;
$date_val = $row ? substr($row['created_at'], 0, 10) : date('Y-m-d');
$heading = $id ? 'page edit' : 'page add';
$msg = $_GET['msg'] ?? '';
if ($msg) $msg_text = $msg === 'added' ? 'Added successfully !' : 'Saved successfully !';
?>

<div class="title"><h4>page</h4></div>
<div class="contant">
    <div class="title"><h5><?php echo $heading; ?></h5></div>
    <div class="item">
        <form class="form" id="pageForm" auto>
            <?php if ($id): ?><input type="hidden" name="id" value="<?php echo $id; ?>"><?php endif; ?>
            <item><alia>title</alia>
                <cont><input class="wide-80" type="text" name="title" value="<?php echo $title_val; ?>" required></cont>
            </item>
            <item><alia>url</alia>
                <cont><input class="wide-40" type="text" name="slug" value="<?php echo $slug_val; ?>" required></cont>
                <hint id="slug-tip" class="co-red" style="display:none"></hint>
            </item>
            <item><alia>date</alia>
                <cont><input class="wide-20" type="date" name="created_at" value="<?php echo $date_val; ?>"></cont>
            </item>
            <item><alia>comment</alia>
                <cont><o class="toggle" name="comment_enabled"></o></cont>
            </item>
            <item><alia>content</alia>
                <cont><textarea class="editor-upload" upload="upload.php" name="content"><?php echo $content_val; ?></textarea></cont>
            </item>
            <item><alia></alia>
                <cont><a class="btn" submit id="btn-submit">submit</a></cont>
            </item>
        </form>
    </div>
</div>

<script type="module">
    const { $, alert } = Uigg
    ready(() => {
        <?php if ($msg): ?>
        alert('<?php echo $msg_text; ?>')
        history.replaceState(null, '', location.pathname + location.search.replace(/&?msg=\w+/, ''))
        <?php endif; ?>

        var slugOk = true
        const f = $('#pageForm')

        f.setData({
            comment_enabled: <?php echo json_encode($comment_enabled_bool); ?>,
        })

        $('input[name=slug]').addEventListener('blur', function () {
            var v = this.value.trim()
            if (!v) return
            var tip = document.getElementById('slug-tip')
            tip.style.display = 'none'
            slugOk = true
            fetch('page-add.php?check=' + encodeURIComponent(v))
                .then(function (r) { return r.json() })
                .then(function (r) {
                    if (!r.ok) {
                        tip.textContent = 'This url already exists !'
                        tip.style.display = ''
                        slugOk = false
                    } else {
                        tip.style.display = 'none'
                        slugOk = true
                    }
                })
        })

        f.onSubmit = async (data) => {
            if (!slugOk) { alert('This url already exists !'); return }
            if (typeof tinymce !== 'undefined') tinymce.triggerSave()
            const resp = await fetch('page-add.php', { method: 'POST', body: f.toFormData() })
            const result = await resp.json().catch(() => ({ error: 'Server error' }))
            if (result.success) location.href = result.redirect
            else alert(result.error || 'Failed')
        }
    })
</script>

</section>
</section>
</section>
</body>
</html>
