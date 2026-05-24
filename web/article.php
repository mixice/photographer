<?php
include ('head.php');
require_once __DIR__ . '/includes/comment.php';

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

$row = $conn->query("SELECT id, title, content, comment_enabled, created_at FROM standpoint WHERE id = $id")->fetch_assoc();
if (!$row) {
    header("Location: index.php");
    exit();
}

$prev = $conn->query("SELECT id, title FROM standpoint WHERE id < $id ORDER BY id DESC LIMIT 1")->fetch_assoc();
$next = $conn->query("SELECT id, title FROM standpoint WHERE id > $id ORDER BY id ASC LIMIT 1")->fetch_assoc();

$settings = getSettings($conn);
$comment_ticket = $settings['comment_ticket'] ?? '';
$comment_enabled = $row['comment_enabled'];

handleCommentSubmission($conn, $type, $id, $comment_enabled, "article.php?type=$type&id=$id&msg=commented");

$comment_msg = $_GET['msg'] ?? '';
extract(getCommentPagination($conn, $type, $id));
$comment_form_action = "?type=$type&id=$id";
$comment_pagination_base = "?type=$type&id=$id";
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

<?php include __DIR__ . '/includes/comment-section.php'; ?>

<?php include ('foot.php') ?>
