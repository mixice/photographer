<?php
include ('head.php');
require_once __DIR__ . '/includes/comment.php';

$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';
if (empty($slug)) {
    header("Location: index.php");
    exit();
}

$stmt = $conn->prepare("SELECT id, title, content, comment_enabled FROM page WHERE slug = ?");
$stmt->bind_param('s', $slug);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
if (!$row) {
    header("Location: index.php");
    exit();
}

$settings = getSettings($conn);
$comment_ticket = $settings['comment_ticket'] ?? '';
$comment_enabled = $row['comment_enabled'];

handleCommentSubmission($conn, 'page', $row['id'], $comment_enabled, "page.php?slug=" . urlencode($slug) . "&msg=commented", "page.php?slug=" . urlencode($slug));

$comment_msg = $_GET['msg'] ?? '';
extract(getCommentPagination($conn, 'page', $row['id']));
$comment_form_action = "?slug=" . urlencode($slug);
$comment_pagination_base = "?slug=" . urlencode($slug);
?>

<section class="article">
    <div class="article-title"><h3><?php echo htmlspecialchars($row['title']); ?></h3></div>
    <div class="article-cont">
        <article><?php echo sanitizeHtml($row['content']); ?></article>
    </div>
</section>

<?php include __DIR__ . '/includes/comment-section.php'; ?>

<?php include ('foot.php') ?>
