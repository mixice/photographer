<?php
include ('head.php');

$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';
if (empty($slug)) {
    header("Location: index.php");
    exit();
}

$row = $conn->query("SELECT * FROM page WHERE slug = '" . $conn->real_escape_string($slug) . "'")->fetch_assoc();
if (!$row) {
    header("Location: index.php");
    exit();
}

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
        $stmt = $conn->prepare("INSERT INTO comment (target_type, target_id, name, email, content, status) VALUES ('page', ?, ?, ?, ?, 0)");
        $stmt->bind_param("isss", $row['id'], $name, $email, $content);
        if ($stmt->execute()) {
            header("Location: page.php?slug=".urlencode($slug)."&msg=commented");
            exit();
        }
    }
}

$msg = $_GET['msg'] ?? '';

// comment list
$page_size = 10;
$comment_page = isset($_GET['cpage']) ? max(1, intval($_GET['cpage'])) : 1;
$total_comments = $conn->query("SELECT COUNT(*) FROM comment WHERE target_type='page' AND target_id={$row['id']} AND status=1")->fetch_row()[0];
$comment_pages = max(1, ceil($total_comments / $page_size));
$comment_offset = ($comment_page - 1) * $page_size;
$comment_list = $conn->query("SELECT * FROM comment WHERE target_type='page' AND target_id={$row['id']} AND status=1 ORDER BY created_at DESC LIMIT $comment_offset, $page_size");
?>

<section class="article">
    <div class="article-title"><h3><?php echo htmlspecialchars($row['title']); ?></h3></div>
    <div class="article-cont">
        <article><?php echo $row['content']; ?></article>
    </div>
</section>

<?php if ($comment_enabled): ?>
<section class="comment">
    <div class="title"><h3>comment</h3></div>
    <div class="form comment-form">
        <form method="POST" action="?slug=<?php echo urlencode($slug); ?>">
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
            <a class="line ico ico-alone-left" href="?slug=<?php echo urlencode($slug); ?>&cpage=<?php echo $comment_page - 1; ?>"></a>
        <?php endif; ?>
        <?php if ($comment_page < $comment_pages): ?>
            <a class="line ico ico-alone-right" href="?slug=<?php echo urlencode($slug); ?>&cpage=<?php echo $comment_page + 1; ?>"></a>
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