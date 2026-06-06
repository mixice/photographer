<?php
include ('head.php');

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$row = null;
if ($id) {
    $row_stmt = $conn->prepare("SELECT * FROM standpoint WHERE id = ?");
    $row_stmt->bind_param('i', $id);
    $row_stmt->execute();
    $row = $row_stmt->get_result()->fetch_assoc();
    $row_stmt->close();
}

// upload dir
$upload_dir = '/uploads/standpoint/';
$upload_path = $_SERVER['DOCUMENT_ROOT'] . $upload_dir;
if (!is_dir($upload_path)) mkdir($upload_path, 0755, true);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $content = $_POST['content'] ?? '';
    $comment_enabled = !empty($_POST['comment_enabled']) ? 1 : 0;
    $date_input = trim($_POST['created_at'] ?? '');
    if ($date_input) {
        // 编辑时保留原时分秒，新增时用当前时间
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

    // handle cover upload
    if (isset($_FILES['cover']) && $_FILES['cover']['error'] === 0) {
        $ext = strtolower(pathinfo($_FILES['cover']['name'], PATHINFO_EXTENSION));
        $filename = date('YmdHis') . rand(100, 999) . '.' . $ext;
        if (move_uploaded_file($_FILES['cover']['tmp_name'], $upload_path . $filename)) {
            $cover = $upload_dir . $filename;
        }
    }

    if (empty($title)) {
        echo "<script>Uigg.alert('Title is required !')</script>";
    } elseif ($id) {
        $content = moveTmpFiles($content, 'standpoint');
        cleanUnusedFiles($row['cover'] ?? '', $cover, $row['content'] ?? '', $content);
        $stmt = $conn->prepare("UPDATE standpoint SET title=?, cover=?, content=?, comment_enabled=?, created_at=? WHERE id=?");
        $stmt->bind_param("sssisi", $title, $cover, $content, $comment_enabled, $created_at, $id);
        if ($stmt->execute()) {
            header("Location: standpoint-add.php?id=$id&msg=saved");
            exit();
        }
    } else {
        $content = moveTmpFiles($content, 'standpoint');
        $stmt = $conn->prepare("INSERT INTO standpoint (title, cover, content, comment_enabled, created_at) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssis", $title, $cover, $content, $comment_enabled, $created_at);
        if ($stmt->execute()) {
            header("Location: standpoint-add.php?id=".$stmt->insert_id."&msg=added");
            exit();
        }
    }
    echo "<script>Uigg.alert('Failed: " . addslashes($conn->error) . " !')</script>";
}

$title_val = htmlspecialchars($row['title'] ?? '', ENT_QUOTES, 'UTF-8');
$content_val = $row['content'] ?? '';
$cover_val = $row['cover'] ?? '';
$comment_active = ($row && !$row['comment_enabled']) ? '' : ' active';
$comment_val = ($row && !$row['comment_enabled']) ? 0 : 1;
$date_val = $row ? substr($row['created_at'], 0, 10) : date('Y-m-d');
$heading = $id ? 'standpoint edit' : 'standpoint add';
$msg = $_GET['msg'] ?? '';
if ($msg) $msg_text = $msg === 'added' ? 'Added successfully !' : 'Saved successfully !';
?>

<div class="title"><h4>standpoint</h4></div>
<div class="contant">
    <div class="title"><h5><?php echo $heading; ?></h5></div>
    <div class="item">
        <section class="form">
            <form method="POST" enctype="multipart/form-data">
                <?php if ($id): ?><input type="hidden" name="id" value="<?php echo $id; ?>"><?php endif; ?>
                <li><span>title</span><input class="wide-80" type="text" name="title" value="<?php echo $title_val; ?>" required></li>
                <li><span>cover</span>
                    <div class="upload">
                        <div class="ico upload-group" <?php if ($cover_val): ?>style="background-image: url('<?php echo htmlspecialchars($cover_val); ?>');color: transparent"<?php endif; ?>>
                            <input type="file" name="cover" accept="image/*">
                        </div>
                    </div>
                    <cite>500x500px</cite>
                </li>
                <li><span>date</span><input class="wide-20" type="date" name="created_at" value="<?php echo $date_val; ?>"></li>
                <li><span>comment</span><label><o class="toggle<?php echo $comment_active; ?>"></o><input type="hidden" name="comment_enabled" value="<?php echo $comment_val; ?>"></label></li>
                <li><span>content</span>
                    <textarea class="editor-upload" name="content"><?php echo $content_val; ?></textarea>
                </li>
                <li class="resolve"><button class="btn btn-submit">submit</button></li>
            </form>
        </section>
    </div>
</div>

<script>
    <?php if ($msg): ?>
        Uigg.alert('<?php echo $msg_text; ?>')
        history.replaceState(null,'',location.pathname+location.search.replace(/&?msg=\w+/,''))
    <?php endif; ?>
    $(function(){
        $('o.toggle').click(function(){
            $('input[name=comment_enabled]').val($(this).hasClass('active') ? 1 : 0)
        })
    })
</script>

</section>
</section>
</section>
</body>
</html>
