<?php
require_once('../db.php');

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// slug check (before head.php, no HTML output)
if (isset($_GET['check']) && $_GET['check']) {
    header('Content-Type: application/json');
    $slug = preg_replace('/[^a-z0-9\-]/', '', strtolower(trim($_GET['check'])));
    if (empty($slug)) { echo json_encode(['ok' => false]); exit; }
    $q = $conn->prepare("SELECT id FROM page WHERE slug = ?");
    $q->bind_param('s', $slug);
    $q->execute();
    $exists = $q->get_result()->num_rows > 0;
    if ($exists && $id) {
        $row = $conn->query("SELECT slug FROM page WHERE id = $id")->fetch_assoc();
        if ($row && $row['slug'] === $slug) $exists = false;
    }
    echo json_encode(['ok' => !$exists]);
    exit;
}

include ('head.php');

$row = $id ? $conn->query("SELECT * FROM page WHERE id = $id")->fetch_assoc() : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $content = $_POST['content'] ?? '';
    $comment_enabled = !empty($_POST['comment_enabled']) ? 1 : 0;
    $date_input = trim($_POST['created_at'] ?? '');
    $created_at = $date_input ? $date_input . ' ' . date('H:i:s') : date('Y-m-d H:i:s');
    $slug = preg_replace('/[^a-z0-9\-]/', '', strtolower($slug));

    if (empty($title) || empty($slug)) {
        echo "<script>alert('Title and url are required !')</script>";
    } elseif ($id) {
        $content = moveTmpFiles($content, 'page');
        cleanUnusedFiles('', '', $row['content'] ?? '', $content);
        $stmt = $conn->prepare("UPDATE page SET title=?, slug=?, content=?, comment_enabled=?, created_at=? WHERE id=?");
        $stmt->bind_param("sssisi", $title, $slug, $content, $comment_enabled, $created_at, $id);
        if ($stmt->execute()) {
            header("Location: page-add.php?id=$id&msg=saved");
            exit();
        }
    } else {
        $content = moveTmpFiles($content, 'page');
        $stmt = $conn->prepare("INSERT INTO page (title, slug, content, comment_enabled, created_at) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssis", $title, $slug, $content, $comment_enabled, $created_at);
        if ($stmt->execute()) {
            header("Location: page-add.php?id=".$stmt->insert_id."&msg=added");
            exit();
        }
    }
    echo "<script>alert('Failed: " . addslashes($conn->error) . " !')</script>";
}

$title_val = htmlspecialchars($row['title'] ?? '', ENT_QUOTES, 'UTF-8');
$slug_val = htmlspecialchars($row['slug'] ?? '', ENT_QUOTES, 'UTF-8');
$content_val = $row['content'] ?? '';
$comment_active = ($row && $row['comment_enabled']) ? ' active' : '';
$comment_val = ($row && $row['comment_enabled']) ? 1 : 0;
$date_val = $row ? substr($row['created_at'], 0, 10) : date('Y-m-d');
$heading = $id ? 'page edit' : 'page add';
$msg = $_GET['msg'] ?? '';
if ($msg) $msg_text = $msg === 'added' ? 'Added successfully !' : 'Saved successfully !';
?>

<div class="title"><h4>page</h4></div>
<div class="contant">
    <div class="title"><h5><?php echo $heading; ?></h5></div>
    <div class="item">
        <section class="form">
            <form method="POST">
                <?php if ($id): ?><input type="hidden" name="id" value="<?php echo $id; ?>"><?php endif; ?>
                <li><span>title</span><input class="wide-80" type="text" name="title" value="<?php echo $title_val; ?>" required></li>
                <li><span>url</span><input class="wide-40" type="text" name="slug" value="<?php echo $slug_val; ?>" required><cite id="slug-tip" class="co-red" style="display:none"></cite></li>
                <li><span>date</span><input class="wide-20" type="date" name="created_at" value="<?php echo $date_val; ?>"></li>
                <li><span>comment</span><label><o class="toggle<?php echo $comment_active; ?>"></o><input type="hidden" name="comment_enabled" value="<?php echo $comment_val; ?>"></label></li>
                <li><span>content</span>
                    <textarea class="editor-upload" name="content"><?php echo $content_val; ?></textarea>
                </li>
                <li class="resolve"><button class="btn btn-submit" id="btn-submit">submit</button></li>
            </form>
        </section>
    </div>
</div>

<script>
    <?php if ($msg): ?>
        alert('<?php echo $msg_text; ?>')
        history.replaceState(null,'',location.pathname+location.search.replace(/&?msg=\w+/,''))
    <?php endif; ?>
    $(function(){
        let slugOk = true
        $('o.toggle').click(function(){
            $('input[name=comment_enabled]').val($(this).hasClass('active') ? 1 : 0)
        })
        $('input[name=slug]').on('blur',function(){
            let v = $(this).val().trim()
            if(!v) return
            let tip = $('#slug-tip')
            tip.hide()
            slugOk = true
            $.get('page-add.php?check='+encodeURIComponent(v),function(r){
                if(!r.ok){
                    tip.text('This url already exists !').show()
                    slugOk = false
                }else{
                    tip.hide()
                    slugOk = true
                }
            })
        })
        $('form').on('submit',function(){
            if(!slugOk){
                alert('This url already exists !')
                return false
            }
        })
    })
</script>

</section>
</section>
</section>
</body>
</html>
