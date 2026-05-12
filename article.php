<?php
include ('head.php');

$type = isset($_GET['type']) ? trim($_GET['type']) : '';
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($type === 'photography' && $id > 0) {
    header("Location: album.php?id=$id");
    exit();
}

if ($type !== 'standpoint' || $id <= 0) {
    header("Location: index.php");
    exit();
}

$row = $conn->query("SELECT * FROM $type WHERE id = $id")->fetch_assoc();
if (!$row) {
    header("Location: index.php");
    exit();
}

// prev/next
$prev = $conn->query("SELECT id, title FROM $type WHERE id < $id ORDER BY id DESC LIMIT 1")->fetch_assoc();
$next = $conn->query("SELECT id, title FROM $type WHERE id > $id ORDER BY id ASC LIMIT 1")->fetch_assoc();

// settings
$settings = $conn->query("SELECT * FROM settings LIMIT 1")->fetch_assoc();
$comment_ticket = $settings['comment_ticket'] ?? '';
$comment_enabled = $row['comment_enabled'];

// comment submit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $comment_enabled) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $name = mb_substr($name, 0, 50);
    $content = mb_substr($content, 0, 1000);
    $name = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
    $content = htmlspecialchars($content, ENT_QUOTES, 'UTF-8');

    if (!empty($_POST['email-repeat'])) {
        // bot detected, silently ignore
    } elseif (!empty($name) && !empty($content)) {
        $stmt = $conn->prepare("INSERT INTO comment (target_type, target_id, name, email, content, status) VALUES (?, ?, ?, ?, ?, 0)");
        $stmt->bind_param("sisss", $type, $id, $name, $email, $content);
        if ($stmt->execute()) {
            header("Location: article.php?type=$type&id=$id&msg=commented");
            exit();
        }
    }
}

$msg = $_GET['msg'] ?? '';

// comment list
$page_size = 10;
$comment_page = isset($_GET['cpage']) ? max(1, intval($_GET['cpage'])) : 1;
$total_comments = $conn->query("SELECT COUNT(*) FROM comment WHERE target_type='$type' AND target_id=$id AND status=1")->fetch_row()[0];
$comment_pages = max(1, ceil($total_comments / $page_size));
$comment_offset = ($comment_page - 1) * $page_size;
$comment_list = $conn->query("SELECT * FROM comment WHERE target_type='$type' AND target_id=$id AND status=1 ORDER BY created_at DESC LIMIT $comment_offset, $page_size");
?>

<section class="article">
    <div class="article-title"><h3><?php echo htmlspecialchars($row['title']); ?></h3><span><?php echo substr($row['created_at'], 0, 10); ?></span></div>
    <div class="article-cont">
        <article><?php echo $row['content']; ?></article>
    </div>
    <div class="article-page center">
        <?php if ($prev): ?>
            <a class="center" href="article.php?type=<?php echo $type; ?>&id=<?php echo $prev['id']; ?>"><i class="ico ico-alone-left"></i><h6><?php echo htmlspecialchars($prev['title']); ?></h6></a>
        <?php endif; ?>
        <u></u>
        <?php if ($next): ?>
            <a class="center" href="article.php?type=<?php echo $type; ?>&id=<?php echo $next['id']; ?>"><h6><?php echo htmlspecialchars($next['title']); ?></h6><i class="ico ico-alone-right"></i></a>
        <?php endif; ?>
    </div>
</section>

<?php if ($comment_enabled): ?>
<section class="comment">
    <div class="title"><h3>comment</h3></div>
    <div class="form comment-form">
        <form method="POST" action="?type=<?php echo $type; ?>&id=<?php echo $id; ?>">
            <li><input type="text" name="name" placeholder="name" required></li>
            <li><input type="email" name="email" placeholder="email"></li>
            <li hide><input type="email" name="email-repeat" hide></li>
            <li><textarea name="content" placeholder="comment" required></textarea></li>
            <li><button class="btn" type="submit">send</button></li>
        </form>
    </div>
    <?php if ($comment_ticket): ?><div class="ticket"><?php echo htmlspecialchars($comment_ticket); ?></div><?php endif; ?>
    <ul>
        <?php if ($comment_list->num_rows > 0): ?>
            <?php while ($c = $comment_list->fetch_assoc()): ?>
            <li><div class="comment-txt"><aside><a class="line" href="mailto:<?php echo htmlspecialchars($c['email'] ?? ''); ?>"><h6><?php echo htmlspecialchars($c['name']); ?></h6></a><u></u><span><?php echo substr($c['created_at'], 0, 10); ?></span></aside><p><?php echo htmlspecialchars($c['content']); ?></p></div></li>
            <?php endwhile; ?>
        <?php endif; ?>
    </ul>
    <?php if ($comment_pages > 1): ?>
    <page>
        <?php if ($comment_page > 1): ?>
            <a class="line ico ico-alone-left" href="?type=<?php echo $type; ?>&id=<?php echo $id; ?>&cpage=<?php echo $comment_page - 1; ?>"></a>
        <?php endif; ?>
        <?php if ($comment_page < $comment_pages): ?>
            <a class="line ico ico-alone-right" href="?type=<?php echo $type; ?>&id=<?php echo $id; ?>&cpage=<?php echo $comment_page + 1; ?>"></a>
        <?php endif; ?>
    </page>
    <?php endif; ?>
</section>
<?php endif; ?>

<?php include ('foot.php') ?>

<?php if ($msg === 'commented'): ?>
<script>
    alert('Comment submitted !')
    history.replaceState(null,'',location.pathname+location.search.replace(/&?msg=\w+/,''))
</script>
<?php endif; ?>
