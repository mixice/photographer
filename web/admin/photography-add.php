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

$upload_dir = '/uploads/photography/';
$upload_path = $_SERVER['DOCUMENT_ROOT'] . $upload_dir;
if (!is_dir($upload_path)) mkdir($upload_path, 0755, true);

function uploadPhotographyImage($file, $upload_path, $upload_dir) {
    if (empty($file) || $file['error'] !== UPLOAD_ERR_OK) return '';
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) return '';
    if (!isValidImageFile($file['tmp_name'])) return '';
    $filename = date('YmdHis') . mt_rand(1000, 9999) . '.' . $ext;
    if (move_uploaded_file($file['tmp_name'], $upload_path . $filename)) {
        return $upload_dir . $filename;
    }
    return '';
}

// POST — must run before head.php to keep JSON output clean
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    ob_clean();
    $row = null;
    if ($id) {
        $row_stmt = $conn->prepare("SELECT * FROM photography WHERE id = ?");
        $row_stmt->bind_param('i', $id);
        $row_stmt->execute();
        $row = $row_stmt->get_result()->fetch_assoc();
        $row_stmt->close();
    }

    $title = trim($_POST['title'] ?? '');
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
    // cover via <images name="cover"> — single File or URL string
    if (isset($_FILES['cover']) && $_FILES['cover']['error'] === UPLOAD_ERR_OK) {
        $new_cover = uploadPhotographyImage($_FILES['cover'], $upload_path, $upload_dir);
        if ($new_cover) $cover = $new_cover;
    }
    if (isset($_POST['cover']) && is_string($_POST['cover']) && $_POST['cover'] !== '') {
        $cover = $_POST['cover'];
    }
    $images = isset($_POST['images']) && is_array($_POST['images']) ? array_values(array_filter($_POST['images'], 'is_string')) : [];
    $old_images = $id ? extractImageUrlsFromJson($row['images'] ?? '') : [];

    if (isset($_FILES['images']) && is_array($_FILES['images']['name'])) {
        foreach ($_FILES['images']['name'] as $index => $name) {
            if ($_FILES['images']['error'][$index] !== UPLOAD_ERR_OK) continue;
            $file = [
                'name' => $name,
                'type' => $_FILES['images']['type'][$index],
                'tmp_name' => $_FILES['images']['tmp_name'][$index],
                'error' => $_FILES['images']['error'][$index],
                'size' => $_FILES['images']['size'][$index],
            ];
            $url = uploadPhotographyImage($file, $upload_path, $upload_dir);
            if ($url) $images[] = $url;
        }
    }

    $images = array_values(array_unique($images));
    if (!$cover && !empty($images)) $cover = $images[0];
    $images_json = json_encode($images, JSON_UNESCAPED_SLASHES);

    if (empty($title)) {
        echo json_encode(['error' => 'Title is required !'], JSON_UNESCAPED_SLASHES);
        exit();
    } elseif ($id) {
        $removed = array_diff(array_merge([$row['cover'] ?? ''], $old_images), array_merge([$cover], $images));
        deleteFilesByUrls($removed);

        try {
            $stmt = $conn->prepare("UPDATE photography SET title=?, cover=?, images=?, comment_enabled=?, created_at=? WHERE id=?");
        } catch (Throwable $e) {
            echo json_encode(['error' => 'Database error: ' . $e->getMessage()], JSON_UNESCAPED_SLASHES);
            exit();
        }
        $stmt->bind_param("sssisi", $title, $cover, $images_json, $comment_enabled, $created_at, $id);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'redirect' => "photography-add.php?id=$id&msg=saved"], JSON_UNESCAPED_SLASHES);
            exit();
        }
    } else {
        try {
            $stmt = $conn->prepare("INSERT INTO photography (title, cover, images, comment_enabled, created_at) VALUES (?, ?, ?, ?, ?)");
        } catch (Throwable $e) {
            echo json_encode(['error' => 'Database error: ' . $e->getMessage()], JSON_UNESCAPED_SLASHES);
            exit();
        }
        $stmt->bind_param("sssis", $title, $cover, $images_json, $comment_enabled, $created_at);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'redirect' => "photography-add.php?id=" . $stmt->insert_id . "&msg=added"], JSON_UNESCAPED_SLASHES);
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
    $row_stmt = $conn->prepare("SELECT * FROM photography WHERE id = ?");
    $row_stmt->bind_param('i', $id);
    $row_stmt->execute();
    $row = $row_stmt->get_result()->fetch_assoc();
    $row_stmt->close();
}

$title_val = htmlspecialchars($row['title'] ?? '', ENT_QUOTES, 'UTF-8');
$cover_val = $row['cover'] ?? '';
$images_val = extractImageUrlsFromJson($row['images'] ?? '');
$comment_enabled_bool = $row ? (bool)$row['comment_enabled'] : true;
$date_val = $row ? substr($row['created_at'], 0, 10) : date('Y-m-d');
$heading = $id ? 'photography edit' : 'photography add';
$msg = $_GET['msg'] ?? '';
if ($msg) $msg_text = $msg === 'added' ? 'Added successfully !' : 'Saved successfully !';
?>

<div class="title"><h4>photography</h4></div>
<div class="contant">
    <div class="title"><h5><?php echo $heading; ?></h5></div>
    <div class="item">
        <form class="form" id="photographyForm" auto>
            <?php if ($id): ?><input type="hidden" name="id" value="<?php echo $id; ?>"><?php endif; ?>
            <item><alia>title</alia>
                <cont><input class="wide-80" type="text" name="title" value="<?php echo $title_val; ?>" required></cont>
            </item>
            <item><alia>date</alia>
                <cont><input class="wide-20" type="date" name="created_at" value="<?php echo $date_val; ?>"></cont>
            </item>
            <item><alia>comment</alia>
                <cont><o class="toggle" name="comment_enabled"></o></cont>
            </item>
            <item><alia>cover</alia>
                <cont><images name="cover"></images></cont>
                <hint>500x500px</hint>
            </item>
            <item><alia>album</alia>
                <cont><images multiple name="images"></images></cont>
            </item>
            <item><alia></alia>
                <cont><a class="btn" submit>submit</a></cont>
            </item>
        </form>
    </div>
</div>

<script type="module">
    const { $, alert } = Uigg
    var message = <?php echo json_encode($msg ? $msg_text : '', JSON_UNESCAPED_SLASHES); ?>;
    ready(() => {
        if (message) {
            alert(message)
            history.replaceState(null, '', location.pathname + location.search.replace(/&?msg=\w+/, ''))
        }

        const f = $('#photographyForm')

        f.setData({
            comment_enabled: <?php echo json_encode($comment_enabled_bool); ?>,
            <?php if ($cover_val): ?>cover: <?php echo json_encode($cover_val, JSON_UNESCAPED_SLASHES); ?>,
            <?php endif; ?>
            <?php if (!empty($images_val)): ?>images: <?php echo json_encode($images_val, JSON_UNESCAPED_SLASHES); ?>,
            <?php endif; ?>
        })

        f.onSubmit = async (data) => {
            const resp = await fetch('photography-add.php', { method: 'POST', body: f.toFormData() })
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
