<?php
ob_start();
error_reporting(0);
require_once('../db.php');

// Auth — replicate head.php check for AJAX POST
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        echo json_encode(['error' => 'Unauthorized'], JSON_UNESCAPED_SLASHES);
        exit;
    }
    header('Location: login.php');
    exit;
}

$id = intval($_POST['id'] ?? $_GET['id'] ?? 0);

// upload dir
$upload_dir = '/uploads/standpoint/';
$upload_path = $_SERVER['DOCUMENT_ROOT'] . $upload_dir;
if (!is_dir($upload_path)) mkdir($upload_path, 0755, true);

// POST — must run before head.php to keep JSON output clean
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    ob_clean();
    $row = null;
    if ($id) {
        $row_stmt = $conn->prepare("SELECT * FROM standpoint WHERE id = ?");
        $row_stmt->bind_param('i', $id);
        $row_stmt->execute();
        $row = $row_stmt->get_result()->fetch_assoc();
        $row_stmt->close();
    }

    $title = trim($_POST['title'] ?? '');
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
    $cover = $row['cover'] ?? '';

    // cover via <images name="cover">
    if (isset($_FILES['cover']) && $_FILES['cover']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['cover']['name'], PATHINFO_EXTENSION));
        $filename = date('YmdHis') . rand(100, 999) . '.' . $ext;
        if (move_uploaded_file($_FILES['cover']['tmp_name'], $upload_path . $filename)) {
            $cover = $upload_dir . $filename;
        }
    }
    if (isset($_POST['cover']) && is_string($_POST['cover']) && $_POST['cover'] !== '') {
        $cover = $_POST['cover'];
    }

    if (empty($title)) {
        echo json_encode(['error' => 'Title is required !'], JSON_UNESCAPED_SLASHES);
        exit();
    } elseif ($id) {
        $content = moveTmpFiles($content, 'standpoint');
        cleanUnusedFiles($row['cover'] ?? '', $cover, $row['content'] ?? '', $content);
        $stmt = $conn->prepare("UPDATE standpoint SET title=?, cover=?, content=?, comment_enabled=?, created_at=? WHERE id=?");
        $stmt->bind_param("sssisi", $title, $cover, $content, $comment_enabled, $created_at, $id);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'redirect' => "standpoint-add.php?id=$id&msg=saved"], JSON_UNESCAPED_SLASHES);
            exit();
        }
    } else {
        $content = moveTmpFiles($content, 'standpoint');
        $stmt = $conn->prepare("INSERT INTO standpoint (title, cover, content, comment_enabled, created_at) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssis", $title, $cover, $content, $comment_enabled, $created_at);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'redirect' => "standpoint-add.php?id=" . $stmt->insert_id . "&msg=added"], JSON_UNESCAPED_SLASHES);
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
    $row_stmt = $conn->prepare("SELECT * FROM standpoint WHERE id = ?");
    $row_stmt->bind_param('i', $id);
    $row_stmt->execute();
    $row = $row_stmt->get_result()->fetch_assoc();
    $row_stmt->close();
}

$title_val = htmlspecialchars($row['title'] ?? '', ENT_QUOTES, 'UTF-8');
$content_val = $row['content'] ?? '';
$cover_val = $row['cover'] ?? '';
$comment_enabled_bool = $row ? (bool)$row['comment_enabled'] : true;
$date_val = $row ? substr($row['created_at'], 0, 10) : date('Y-m-d');
$heading = $id ? 'standpoint edit' : 'standpoint add';
$msg = $_GET['msg'] ?? '';
if ($msg) $msg_text = $msg === 'added' ? 'Added successfully !' : 'Saved successfully !';
?>

<div class="title"><h4>standpoint</h4></div>
<div class="contant">
    <div class="title"><h5><?php echo $heading; ?></h5></div>
    <div class="item">
        <form class="form" id="standpointForm" auto>
            <?php if ($id): ?><input type="hidden" name="id" value="<?php echo $id; ?>"><?php endif; ?>
            <item><alia>title</alia>
                <cont><input class="wide-80" type="text" name="title" value="<?php echo $title_val; ?>" required></cont>
            </item>
            <item><alia>cover</alia>
                <cont><images name="cover"></images></cont>
                <hint>500x500px</hint>
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
                <cont><a class="btn" submit>submit</a></cont>
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

        const f = $('#standpointForm')

        f.setData({
            comment_enabled: <?php echo json_encode($comment_enabled_bool); ?>,
            <?php if ($cover_val): ?>cover: <?php echo json_encode($cover_val, JSON_UNESCAPED_SLASHES); ?>,
            <?php endif; ?>
        })

        f.onSubmit = async (data) => {
            if (typeof tinymce !== 'undefined') tinymce.triggerSave()
            const resp = await fetch('standpoint-add.php', { method: 'POST', body: f.toFormData() })
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
